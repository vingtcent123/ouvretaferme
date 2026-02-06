<?php
namespace cash;

class CashLib extends CashCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Cash $e) {

			return match($e['source']) {

				Cash::INITIAL => ['type', 'date', 'amountIncludingVat'],
				Cash::PRIVATE => ['type', 'date', 'amountIncludingVat', 'account', 'description'],

			};

		};

	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Cash $e) {

			return array_diff(CashLib::getPropertiesCreate()($e), ['type', 'date']);

		};

	}

	public static function fill(Cash $eCash): void {

		switch($eCash['source']) {

			case \cash\Cash::PRIVATE :

				if($eCash->requireAssociateAccount()) {

					$eCash['cAccount'] = \account\AccountLib::getAssociates();

				}

				break;

		}

	}

	public static function getByRegister(Register $eRegister, ?int $page = NULL, \Search $search = new \Search()): \Collection {

		$number = ($page === NULL) ? NULL : 200;
		$position = ($page === NULL) ? NULL : $page * $number;

		$ccCash = Cash::model()
			->select(Cash::getSelection())
			->option('count')
			->whereRegister($eRegister)
			->whereType($search->get('type'), if: $search->get('type'))
			->whereStatus('!=', Cash::DELETED)
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
			'date' => SORT_DESC,
			'type' => new \Sql('FIELD(type, "'.Cash::DEBIT.'", "'.Cash::CREDIT.'")'),
			'id' => SORT_DESC
		];

	}

	private static function getReverseOrder(): array {

		return [
			'status' => new \Sql('FIELD(status, "'.Cash::VALID.'", "'.Cash::DRAFT.'")'),
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
				->select('balance', 'operations', 'lastOperation')
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
					$e['date'] < $eRegister['lastOperation']
				) {
					Cash::model()->rollBack();
					return;
				}

			}

			match($e['source']) {

				Cash::INITIAL => self::createInitial($e),
				Cash::PRIVATE => self::createPrivate($e),

			};

			// Propriétés requises
			$e->expects([
				'amountIncludingVat', 'amountExcludingVat',
				'vat', 'vatRate',
				'type', 'status'
			]);

			// Propriétés requises pour signatures et complétées si besoin
			$e->add([
				'description' => NULL,
				'sourceBankAccount' => new \bank\BankAccount(),
				'sourceCashflow' => new \bank\Cashflow(),
				'sourceSale' => new \selling\Sale(),
				'sourceInvoice' => new \selling\Invoice(),
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

		$e['amountExcludingVat'] = $e['amountIncludingVat'];
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['description'] = NULL;
		$e['status'] = Cash::VALID;

	}

	private static function createPrivate(Cash $e): void {

		$e->expects(['amountIncludingVat']);

		$e['amountExcludingVat'] = $e['amountIncludingVat'];
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['status'] = Cash::DRAFT;

	}

	public static function validateUntil(Cash $eCashUntil): void {

		$eCashUntil->expects(['register']);

		do {

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
					->select('balance', 'operations', 'lastOperation')
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
		$eRegister['lastOperation'] = $e['date'];
		$eRegister['operations']++;

		if($eRegister['balance'] < 0) {
			throw new \Exception('Balance is negative');
		}

		Register::model()
			->select('balance', 'lastOperation', 'operations')
			->update($eRegister);

		$e['balance'] = $eRegister['balance'];

		Cash::model()
			->select('balance')
			->update($e);

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
