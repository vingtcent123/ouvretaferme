<?php
namespace cash;

class CashLib extends CashCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Cash $e) {

			// La date doit être vérifiée en amont
			$e->expects(['date']);

			$properties = ['type', 'amountIncludingVat'];

			if(
				$e->requireAssociateAccount() or
				$e->requireAccount()
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

		return function(Cash $e) {

			return array_diff(CashLib::getPropertiesCreate()($e), ['type', 'date']);

		};

	}

	public static function fill(Cash $eCash): void {

		if($eCash->requireAssociateAccount()) {

			$eCash['cAccount'] = \account\AccountLib::getAssociates();

		}

		if(
			$eCash->exists() === FALSE and
			$eCash->requireAccount()
		) {

			$eCash['account'] = match($eCash['source']) {

				Cash::OTHER => match($eCash['type']) {
					Cash::CREDIT => \account\AccountLib::getByClass(\account\AccountSetting::PRODUCT_OTHER_CLASS),
					Cash::DEBIT => \account\AccountLib::getByClass(\account\AccountSetting::CHARGES_OTHER_CLASS),
				},

				Cash::BANK_MANUAL => $eCash['register']['bankAccount']->notEmpty() ?
					$eCash['register']['bankAccount'] :
					\account\AccountLib::getByClass(\account\AccountSetting::BANK_ACCOUNT_CLASS),

				default => new \account\Account()

			};

		}

	}

	public static function getByRegister(Register $eRegister, ?int $page = NULL, \Search $search = new \Search()): \Collection {

		$number = ($page === NULL) ? NULL : 200;
		$position = ($page === NULL) ? NULL : $page * $number;

		$ccCash = Cash::model()
			->select(Cash::getSelection() + [
				'cSaleMarket' => \selling\Sale::model()
					->select(\selling\SaleElement::getSelection())
					->whereFarm(\farm\Farm::getConnected())
					->whereProfile(\selling\Sale::SALE_MARKET)
					->wherePriceIncludingVat('>=', CashSetting::AMOUNT_THRESHOLD)

					->delegateCollection('marketParent', propertyParent: 'sale')
			])
			->option('count')
			->whereRegister($eRegister)
			->whereType($search->get('type'), if: $search->get('type'))
			->whereStatus('!=', Cash::DELETED)
			->whereParent(NULL)
			->sort(self::getOrder())
			->getCollection($position, $number, index: ['status', NULL]);

		if($ccCash->offsetExists(Cash::DRAFT)) {

			$balance = $eRegister['balance'];
			$balanceNegative = FALSE;

			foreach($ccCash[Cash::DRAFT]->reverse() as $eCash) {

				$balance += match($eCash['type']) {
					Cash::DEBIT => -1,
					Cash::CREDIT => 1
				} * $eCash['amountIncludingVat'];
				$balance = round($balance, 2);

				if($balance < 0) {
					$balanceNegative = TRUE;
				}

				$eCash['balance'] = $balance;
				$eCash['balanceNegative'] = $balanceNegative;

			}

		}

		return $ccCash;

	}

	private static function getOrder(): array {

		return [
			'status' => new \Sql('FIELD(status, "'.Cash::DRAFT.'", "'.Cash::VALID.'")'),
			'position' => SORT_DESC,
			'date' => SORT_DESC,
			'type' => new \Sql('FIELD(type, "'.Cash::DEBIT.'", "'.Cash::CREDIT.'")'),
			'id' => SORT_DESC
		];

	}

	private static function getReverseOrder(): array {

		return [
			'status' => new \Sql('FIELD(status, "'.Cash::VALID.'", "'.Cash::DRAFT.'")'),
			'position' => SORT_ASC,
			'date' => SORT_ASC,
			'type' => new \Sql('FIELD(type, "'.Cash::CREDIT.'", "'.Cash::DEBIT.'")'),
			'id' => SORT_ASC
		];

	}

	public static function create(Cash $e): void {

		$e->expects([
			'register',
			'type', 'source', 'date', 'financialYear'
		]);

		Cash::model()->beginTransaction();

			$eRegister = $e['register'];

			Register::model()
				->select('balance', 'operations', 'closedAt')
				->get($eRegister);

			// La première opération est nécessairement le solde initial
			if($e['source'] === Cash::INITIAL) {

				if($eRegister['operations'] > 0) {
					Cash::model()->rollBack();
					return;
				}

			} else {

				if(
					// IL n'y a pas encore de solde initial
					$eRegister['operations'] === 0 or
					// L'opération est placée avant la dernière opération validée
					$eRegister->isClosedByDate($e['date'])
				) {
					Cash::model()->rollBack();
					return;
				}

			}

			match($e['source']) {

				Cash::INITIAL => self::createInitial($e),
				Cash::BALANCE => self::createBalance($e),
				Cash::PRIVATE => self::createPrivate($e),
				Cash::BANK_MANUAL, Cash::BANK_CASHFLOW => self::createWithoutVat($e),
				Cash::OTHER, Cash::BUY_MANUAL, Cash::SELL_MANUAL => self::createWithVat($e),
				Cash::SELL_INVOICE, Cash::SELL_SALE => self::createTransaction($e),

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
			if($e['status'] === Cash::VALID) {
				self::validateInternal($e);
			}

			// Signature de l'opération
			\securing\SignatureLib::signCash($e);

		Cash::model()->commit();

	}

	private static function createInitial(Cash $e): void {

		$e->expects(['amountIncludingVat']);

		$e['amountExcludingVat'] = NULL;
		$e['hasVat'] = NULL;
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['description'] = NULL;
		$e['status'] = Cash::VALID;

	}

	private static function createBalance(Cash $e): void {

		$e->expects(['amountIncludingVat']);

		$e['amountExcludingVat'] = NULL;
		$e['hasVat'] = NULL;
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['status'] = Cash::DRAFT;

		if($e['financialYear']->isAccounting()) {

			$e['account'] = \account\AccountLib::getByClass(match($e['type']) {
				Cash::DEBIT => \account\AccountSetting::CHARGES_OTHER_CLASS,
				Cash::CREDIT => \account\AccountSetting::PRODUCT_OTHER_CLASS,
			});

		}

	}

	private static function createPrivate(Cash $e): void {

		self::createWithoutVat($e);

		if($e['financialYear']->isIndividual()) {
			$e['account'] = \account\AccountLib::getByClass(\account\AccountSetting::FARMER_S_ACCOUNT_CLASS);
		}

	}

	private static function createWithoutVat(Cash $e): void {

		$e->expects(['amountIncludingVat']);

		$e['amountExcludingVat'] = NULL;
		$e['hasVat'] = NULL;
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['status'] = Cash::DRAFT;

	}

	private static function createWithVat(Cash $e): void {

		$e['status'] = Cash::DRAFT;

		if($e->requireVat() === FALSE) {
			$e['amountExcludingVat'] = $e['amountIncludingVat'];
			$e['hasVat'] = FALSE;
			$e['vat'] = 0.0;
			$e['vatRate'] = 0.0;
		} else {
			$e['hasVat'] = TRUE;
		}

	}

	private static function createTransaction(Cash $e): void {

		$e['status'] = Cash::DRAFT;

	}

	public static function validateUntil(Cash $eCashUntil): void {

		$eCashUntil->expects(['register']);

		do {

			// Chaque validation est indépendante
			Cash::model()->beginTransaction();

				$eCash = Cash::model()
					->select(Cash::getSelection())
					->whereRegister($eCashUntil['register'])
					->whereStatus(Cash::DRAFT)
					->sort(self::getReverseOrder())
					->get();

				$eRegister = $eCash['register'];

				if($eCash->empty()) {
					return;
				}

				Register::model()
					->select('balance', 'operations', 'closedAt')
					->get($eRegister);

				$eCash['status'] = Cash::VALID;
				self::update($eCash, ['status']);

				self::validateInternal($eCash);

			Cash::model()->commit();

		} while($eCashUntil->is($eCash) === FALSE);

	}

	private static function validateInternal(Cash $e): void {

		$eRegister = $e['register'];

		$additional = match($e['type']) {
			Cash::CREDIT => $e['amountIncludingVat'],
			Cash::DEBIT => -1 * $e['amountIncludingVat']
		};

		$eRegister['balance'] += $additional;
		$eRegister['closedAt'] = date('Y-m-d', strtotime($e['date'].' - 1 DAY'));
		$eRegister['operations']++;

		if($eRegister['balance'] < 0) {
			throw new \Exception('Balance is negative');
		}

		Register::model()
			->select('balance', 'closedAt', 'operations')
			->update($eRegister);

		$e['balance'] = $eRegister['balance'];
		$e['position'] = $eRegister['operations'];

		Cash::model()
			->select('balance', 'position')
			->update($e);

		if($e['source'] === Cash::SELL_SALE) {
			self::validateSale($e['sale']);
		}

	}

	private static function validateSale(\selling\Sale $e): void {

		\selling\Sale::model()
			->select(\selling\SaleElement::getSelection())
			->get($e);

		if($e->isMarket()) {

			$cSaleMarket = \selling\Sale::model();


		}

	}

	public static function update(Cash $e, array $properties): void {

		if(in_array('date', $properties)) {
			$properties[] = 'financialYear';
		}

		Cash::model()->beginTransaction();

			parent::update($e, $properties);

			// Signature de l'opération
			\securing\SignatureLib::signCash($e);

		Cash::model()->commit();

	}

	public static function deleteWaiting(Register $e): void {

		$cCash = Cash::model()
			->select(Cash::getSelection())
			->whereStatus(Cash::DRAFT)
			->whereRegister($e)
			->getCollection();

		foreach($cCash as $eCash) {
			self::delete($eCash);
		}


	}

	public static function delete(Cash $e): void {

		Cash::model()->beginTransaction();

			$affected = Cash::model()
				->whereStatus(Cash::DRAFT)
				->update($e, [
					'status' => Cash::DELETED
				]);

			if($affected > 0) {
				\securing\SignatureLib::signDeletedCash($e);
			}

		Cash::model()->commit();

	}

}
?>
