<?php
namespace cash;

class CashLib extends CashCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Cash $e) {

			return match($e['origin']) {

				Cash::BALANCE_INITIAL => ['date', 'amountIncludingVat']

			};

		};

	}

	public static function getPropertiesUpdate(): array {
		return [];
	}

	public static function getByRegister(Register $eRegister, ?int $page = NULL, \Search $search = new \Search()): \Collection {

		$number = ($page === NULL) ? NULL : 200;
		$position = ($page === NULL) ? NULL : $page * $number;

		return Cash::model()
			->select(Cash::getSelection())
			->option('count')
			->whereRegister($eRegister)
			->whereType($search->get('type'), if: $search->get('type'))
			->whereStatus('!=', Cash::DELETED)
			->sort([
				'date' => SORT_DESC,
				'id' => SORT_DESC
			])
			->getCollection($position, $number);

	}

	public static function create(Cash $e): void {

		$e->expects([
			'register',
			'origin', 'date'
		]);

		Cash::model()->beginTransaction();

			$eRegister = $e['register'];

			Register::model()
				->select('balance', 'lines')
				->get($eRegister);

			// La première opération est nécessairement le solde initial
			if($e['origin'] === Cash::BALANCE_INITIAL) {

				if($eRegister['lines'] > 0) {
					Cash::model()->rollBack();
					return;
				}

			} else {

				if($eRegister['lines'] === 0) {
					Cash::model()->rollBack();
					return;
				}

			}

			match($e['origin']) {

				Cash::BALANCE_INITIAL => self::createBalanceInitial($e)

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
				'originBankAccount' => new \bank\BankAccount(),
				'originCashflow' => new \bank\Cashflow(),
				'originSale' => new \selling\Sale(),
				'originInvoice' => new \selling\Invoice(),
			]);

			// Ajout de l'opération
			parent::create($e);

			// Mise à jour de la balance et du nombre de lignes
			if($e['status'] === Cash::VALID) {
				self::synchronize($e);
			}

			// Signature de l'opération
			\securing\SignatureLib::signCash($e);

		Cash::model()->commit();

	}

	private static function createBalanceInitial(Cash $e): void {

		$e->expects(['amountIncludingVat']);

		$e['type'] = Cash::CREDIT;
		$e['amountExcludingVat'] = $e['amountIncludingVat'];
		$e['vat'] = NULL;
		$e['vatRate'] = NULL;
		$e['description'] = CashUi::getInitial();
		$e['status'] = Cash::VALID;

	}

	private static function synchronize(Cash $e): void {

		$eRegister = $e['register'];

		$additional = match($e['type']) {
			Cash::CREDIT => $e['amountIncludingVat'],
			Cash::DEBIT => -1 * $e['amountIncludingVat']
		};

		$eRegister['balance'] += $additional;
		$eRegister['lines']++;

		Register::model()
			->select('balance', 'lines')
			->update($eRegister);

		$e['balance'] = $eRegister['balance'];

		Cash::model()
			->select('balance')
			->update($e);

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
