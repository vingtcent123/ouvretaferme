<?php
namespace receipts;

class LineLib extends LineCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Line $e) {

			$properties = ['type', 'amountIncludingVat'];

			if(
				$e->acceptAccount() or
				$e->acceptAssociateAccount()
			) {
				$properties[] = 'account';
			}

			if($e->requireVat()) {
				$properties[] = 'amountExcludingVat';
				$properties[] = 'vatRate';
				$properties[] = 'vat'; // En dernier pour les contrôles de cohérence
			}

			if($e['source'] !== NULL) {
				$properties[] = 'description';
			}

			return $properties;

		};

	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Line $e) {

			switch($e['status']) {

				case Line::DRAFT :
					return array_diff(LineLib::getPropertiesCreate()($e), ['type', 'date']);

				case Line::VALID :
					if(
						$e->requireAssociateAccount() or
						$e->requireAccount()
					) {
						return ['account'];
					} else {
						return [];
					}

			}

		};

	}

	public static function fill(Line $eLine): void {

		if($eLine->requireAssociateAccount()) {

			$eLine['cAccount'] = \account\AccountLib::getAssociates();

		}

		if(
			$eLine->exists() === FALSE and
			$eLine->requireAccount()
		) {

			$eLine['account'] = match($eLine['source']) {

				Line::OTHER => match($eLine['type']) {
					Line::CREDIT => \account\AccountLib::getByClass(\account\AccountSetting::PRODUCT_OTHER_CLASS),
					Line::DEBIT => \account\AccountLib::getByClass(\account\AccountSetting::CHARGES_OTHER_CLASS),
				},

				Line::BANK_MANUAL => $eLine['book']['bankAccount']->notEmpty() ?
					$eLine['book']['bankAccount'] :
					\account\AccountLib::getByClass(\account\AccountSetting::BANK_ACCOUNT_CLASS),

				default => new \account\Account()

			};

		}

	}

	public static function getByBook(Book $eBook, ?int $page = NULL, \Search $search = new \Search()): \Collection {

		$number = ($page === NULL) ? NULL : 200;
		$position = ($page === NULL) ? NULL : $page * $number;

		if($search->get('source')) {

			$sources = match($search->get('source')) {

				'balance' => [Line::BALANCE],
				'private' => [Line::PRIVATE],
				'bank' => [Line::BANK_MANUAL, Line::BANK_CASHFLOW],
				'other' => [Line::OTHER],
				'buy' => [Line::BUY_MANUAL],
				'sell' => [Line::SELL_MANUAL, Line::SELL_SALE, Line::SELL_INVOICE],

			};

			Line::model()->whereSource('IN', $sources);

		}

		if($search->get('account')) {

			Line::model()->whereSource('IN', array_merge(ReceiptsSetting::SOURCE_ACCOUNTS, ReceiptsSetting::SOURCE_PRIVATE_ACCOUNTS));

			switch($search->get('account')) {

				case 'with' :
					Line::model()->whereAccount('!=', NULL);
					break;

				case 'without' :
					Line::model()->whereAccount(NULL);
					break;

			}

		}

		$ccLine = Line::model()
			->select(Line::getSelection() + [
				'cSaleMarket' => \selling\Sale::model()
					->select(\selling\SaleElement::getSelection())
					->whereFarm(\farm\Farm::getConnected())
					->whereProfile(\selling\Sale::SALE_MARKET)
					->wherePriceIncludingVat('>=', ReceiptsSetting::AMOUNT_THRESHOLD)

					->delegateCollection('marketParent', propertyParent: 'sale')
			])
			->option('count')
			->whereBook($eBook)
			->whereType($search->get('type'), if: $search->get('type'))
			->whereStatus('!=', Line::DELETED)
			->sort(self::getOrder())
			->getCollection($position, $number, index: ['status', NULL]);

		if($ccLine->offsetExists(Line::DRAFT)) {

			$balance = $eBook['balance'];
			$balanceNegative = FALSE;

			foreach($ccLine[Line::DRAFT]->reverse() as $eLine) {

				$balance += match($eLine['type']) {
					Line::DEBIT => -1,
					Line::CREDIT => 1
				} * $eLine['amountIncludingVat'];
				$balance = round($balance, 2);

				if($balance < 0) {
					$balanceNegative = TRUE;
				}

				$eLine['balance'] = $balance;
				$eLine['balanceNegative'] = $balanceNegative;

			}

		}

		return $ccLine;

	}

	private static function getOrder(): array {

		return [
			'status' => new \Sql('FIELD(status, "'.Line::DRAFT.'", "'.Line::VALID.'")'),
			'position' => SORT_DESC,
			'date' => SORT_DESC,
			'type' => new \Sql('FIELD(type, "'.Line::DEBIT.'", "'.Line::CREDIT.'")'),
			'id' => SORT_DESC
		];

	}

	private static function getReverseOrder(): array {

		return [
			'status' => new \Sql('FIELD(status, "'.Line::VALID.'", "'.Line::DRAFT.'")'),
			'position' => SORT_ASC,
			'date' => SORT_ASC,
			'type' => new \Sql('FIELD(type, "'.Line::CREDIT.'", "'.Line::DEBIT.'")'),
			'id' => SORT_ASC
		];

	}

	public static function create(Line $e): void {

		$e->expects([
			'book',
			'type', 'source', 'date', 'financialYear'
		]);

		Line::model()->beginTransaction();

			$eBook = $e['book'];

			Book::model()
				->select('balance', 'operations', 'closedAt')
				->get($eBook);

			// La première opération est nécessairement le solde initial
			if($e['source'] === Line::INITIAL) {

				if($eBook['operations'] > 0) {
					Line::model()->rollBack();
					return;
				}

			} else {

				if(
					// IL n'y a pas encore de solde initial
					$eBook['operations'] === 0 or
					// L'opération est placée avant la dernière opération validée
					$eBook->isClosedByDate($e['date'])
				) {
					Line::model()->rollBack();
					return;
				}

			}

			match($e['source']) {

				Line::INITIAL => self::createInitial($e),
				Line::BALANCE => self::createBalance($e),
				Line::PRIVATE => self::createPrivate($e),
				Line::BANK_MANUAL, Line::BANK_CASHFLOW => self::createWithoutVat($e),
				Line::OTHER, Line::BUY_MANUAL, Line::SELL_MANUAL => self::createWithVat($e),
				Line::SELL_INVOICE, Line::SELL_SALE => self::createTransaction($e),

			};

			// Propriétés requises
			$e->expects([
				'amountIncludingVat', 'amountExcludingVat',
				'hasVat', 'vat', 'vatRate',
				'type', 'status'
			]);

			// Propriétés requises pour signatures et complétées si besoin
			$e->add([
				'description' => NULL,
				'cashflow' => new \bank\Cashflow(),
				'sale' => new \selling\Sale(),
				'invoice' => new \selling\Invoice(),
			]);

			// Ajout de l'opération
			parent::create($e);

			// Mise à jour de la balance et du nombre de lignes
			if($e['status'] === Line::VALID) {
				self::validateInternal($e);
			}

			// Signature de l'opération
			\securing\SignatureLib::signLine($e);

		Line::model()->commit();

	}

	private static function createInitial(Line $e): void {

		$e->expects(['amountIncludingVat']);

		$e['amountExcludingVat'] = NULL;
		$e['hasVat'] = NULL;
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['description'] = NULL;
		$e['status'] = Line::VALID;

	}

	private static function createBalance(Line $e): void {

		$e->expects(['amountIncludingVat']);

		$e['amountExcludingVat'] = NULL;
		$e['hasVat'] = NULL;
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['status'] = Line::DRAFT;

		if($e['financialYear']->isAccounting()) {

			$e['account'] = \account\AccountLib::getByClass(match($e['type']) {
				Line::DEBIT => \account\AccountSetting::CHARGES_OTHER_CLASS,
				Line::CREDIT => \account\AccountSetting::PRODUCT_OTHER_CLASS,
			});

		}

	}

	private static function createPrivate(Line $e): void {

		self::createWithoutVat($e);

		if($e['financialYear']->isIndividual()) {
			$e['account'] = \account\AccountLib::getByClass(\account\AccountSetting::FARMER_S_ACCOUNT_CLASS);
		}

	}

	private static function createWithoutVat(Line $e): void {

		$e->expects(['amountIncludingVat']);

		$e['amountExcludingVat'] = NULL;
		$e['hasVat'] = NULL;
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['status'] = Line::DRAFT;

	}

	private static function createWithVat(Line $e): void {

		$e['status'] = Line::DRAFT;

		if($e->requireVat() === FALSE) {
			$e['amountExcludingVat'] = $e['amountIncludingVat'];
			$e['hasVat'] = FALSE;
			$e['vat'] = 0.0;
			$e['vatRate'] = 0.0;
		} else {
			$e['hasVat'] = TRUE;
		}

	}

	private static function createTransaction(Line $e): void {

		$e['status'] = Line::DRAFT;

	}

	public static function validateUntil(Line $eLineUntil): void {

		$eLineUntil->expects(['book']);

		do {

			// Chaque validation est indépendante
			Line::model()->beginTransaction();

				$eLine = Line::model()
					->select(Line::getSelection())
					->whereBook($eLineUntil['book'])
					->whereStatus(Line::DRAFT)
					->sort(self::getReverseOrder())
					->get();

				$eBook = $eLine['book'];

				if($eLine->empty()) {
					return;
				}

				Book::model()
					->select('balance', 'operations', 'closedAt')
					->get($eBook);

				$eLine['status'] = Line::VALID;
				self::update($eLine, ['status']);

				self::validateInternal($eLine);

			Line::model()->commit();

		} while($eLineUntil->is($eLine) === FALSE);

	}

	private static function validateInternal(Line $e): void {

		$eBook = $e['book'];

		$additional = match($e['type']) {
			Line::CREDIT => $e['amountIncludingVat'],
			Line::DEBIT => -1 * $e['amountIncludingVat']
		};

		$eBook['balance'] += $additional;
		$eBook['closedAt'] = date('Y-m-d', strtotime($e['date'].' - 1 DAY'));
		$eBook['operations']++;

		if($eBook['balance'] < 0) {
			throw new \Exception('Balance is negative');
		}

		Book::model()
			->select('balance', 'closedAt', 'operations')
			->update($eBook);

		$e['balance'] = $eBook['balance'];
		$e['position'] = $eBook['operations'];

		Line::model()
			->select('balance', 'position')
			->update($e);

	}

	public static function update(Line $e, array $properties): void {

		if(in_array('date', $properties)) {
			$properties[] = 'financialYear';
		}

		Line::model()->beginTransaction();

			parent::update($e, $properties);

			// Signature de l'opération
			\securing\SignatureLib::signLine($e);

		Line::model()->commit();

	}

	public static function deleteWaiting(Book $e): void {

		$cLine = Line::model()
			->select(Line::getSelection())
			->whereStatus(Line::DRAFT)
			->whereBook($e)
			->getCollection();

		foreach($cLine as $eLine) {
			self::delete($eLine);
		}


	}

	public static function delete(Line $e): void {

		Line::model()->beginTransaction();

			$affected = Line::model()
				->whereStatus(Line::DRAFT)
				->update($e, [
					'status' => Line::DELETED
				]);

			if($affected > 0) {
				\securing\SignatureLib::signDeletedLine($e);
			}

		Line::model()->commit();

	}

}
?>
