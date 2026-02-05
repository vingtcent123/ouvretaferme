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
			'type', 'source', 'date', 'financialYear'
		]);

		Cash::model()->beginTransaction();

			$eRegister = $e['register'];

			Register::model()
				->select('balance', 'lines')
				->get($eRegister);

			// La première opération est nécessairement le solde initial
			if($e['source'] === Cash::INITIAL) {

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
				self::synchronize($e);
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

	public static function update(Cash $e, array $properties): void {

		if(in_array('date', $properties)) {
			$properties[] = 'financialYear';
		}

		parent::update($e, $properties);

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
