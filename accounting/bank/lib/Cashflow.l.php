<?php
namespace bank;

class CashflowLib extends CashflowCrud {

	public static function applySearch(\Search $search): CashflowModel {

		return Cashflow::model()
			->whereImport('=', $search->get('import'), if: $search->has('import'))
			->whereDate('LIKE', '%'.$search->get('date').'%', if: $search->get('date'))
			->whereDate('>=', fn() => $search->get('financialYear')['startDate'], if: $search->has('financialYear'))
			->whereDate('<=', fn() => $search->get('financialYear')['endDate'], if: $search->has('financialYear'))
			->whereFitid('LIKE', '%'.$search->get('fitid').'%', if: $search->get('fitid'))
			->whereMemo('LIKE', '%'.mb_strtolower($search->get('memo') ?? '').'%', if: $search->get('memo'))
			->whereCreatedAt('<=', $search->get('createdAt'), if: $search->get('createdAt'))
			->whereStatus('=', $search->get('status'), if: $search->get('status'))
			->where('amount BETWEEN '.$search->get('amountMin').' AND '.$search->get('amountMax'), if: ($search->has('amountMin') and $search->has('amountMax')))
		;

	}

	public static function getAll(\Search $search, bool $hasSort): \Collection {

		return self::applySearch($search)
			->select(Cashflow::getSelection())
			->sort($hasSort === TRUE ? $search->buildSort() : ['date' => SORT_DESC, 'fitid' => SORT_DESC])
			->getCollection();

	}

	public static function countByStatus(\Search $search): \Collection {

		$searchWithoutStatus = new \Search($search->getFiltered(['status']));

			return self::applySearch($searchWithoutStatus)
				->select(['count' => new \Sql('COUNT(*)'), 'status'])
				->group(['status'])
				->getCollection(NULL, NULL, 'status');
	}

	public static function insertMultiple(array $cashflows): array {

		$alreadyImported = [];
		$noFinancialYear = [];
		$imported = [];
		$invalidDate = [];
		$cFinancialYear = \account\FinancialYearLib::getAll();

		foreach($cashflows as $cashflow) {

			$isAlreadyImportedTransaction = (Cashflow::model()->whereFitid($cashflow['fitid'])->count() > 0);

			if($isAlreadyImportedTransaction === TRUE) {
				$alreadyImported[] = $cashflow['fitid'];
				continue;
			}

			$type = match($cashflow['type']) {
				'DEBIT' => CashflowElement::DEBIT,
				'CREDIT' => CashflowElement::CREDIT,
				default => $cashflow['amount'] > 0 ? CashflowElement::CREDIT : CashflowElement::DEBIT,
			};
			$date = substr($cashflow['date'], 0, 4).'-'.substr($cashflow['date'], 4, 2).'-'.substr($cashflow['date'], 6, 2);

			if(\account\FinancialYearLib::isDateLinkedToFinancialYear($date, $cFinancialYear) === FALSE) {
				$noFinancialYear[] = $cashflow['fitid'];
				continue;
			}

			if(\util\DateLib::isValid($date) === FALSE) {
				$invalidDate[] = $cashflow['fitid'];
				continue;
			}

			$eCashflow = new Cashflow(
				array_merge(
					$cashflow,
					[
						'type' => $type,
						'date' => $date
					]
				)
			);

			Cashflow::model()->insert($eCashflow);
			$imported[] = $cashflow['fitid'];

		}

		return ['alreadyImported' => $alreadyImported, 'invalidDate' => $invalidDate, 'imported' => $imported, 'noFinancialYear' => $noFinancialYear];

	}


	public static function attach(Cashflow $eCashflow, array $operations, \Collection $cPaymentMethod): void {

		Cashflow::model()->beginTransaction();

		if($eCashflow['status'] !== Cashflow::WAITING or \journal\OperationLib::countByCashflow($eCashflow) > 0) {
			throw new \NotExpectedAction('Cashflow #'.$eCashflow['id'].' already attached');
		}

		$updated = \journal\OperationLib::attachIdsToCashflow($eCashflow, $operations, $cPaymentMethod);
		if($updated !== count($operations)) {
			throw new \NotExpectedAction($updated.' operations updated instead of '.count($operations).' expected. Cashflow #'.$eCashflow['id'].' not attached.');
		}

		$properties = ['status', 'updatedAt'];
		$eCashflow['status'] = Cashflow::ALLOCATED;
		$eCashflow['updatedAt'] = Cashflow::model()->now();

		Cashflow::model()
			->select($properties)
			->whereId($eCashflow['id'])
			->update($eCashflow->extracts(['status', 'updatedAt']));

		Cashflow::model()->commit();

		\account\LogLib::save('attach', 'cashflow', ['id' => $eCashflow['id'], 'operations' => $operations]);
	}
}
?>
