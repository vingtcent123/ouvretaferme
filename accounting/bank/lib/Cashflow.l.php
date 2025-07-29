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
			->whereStatus('=', $search->get('status'), if: $search->get('status'));

	}

	public static function searchInvoices(\Collection $cThirdParty, Cashflow $eCashflow): \Collection {

		// On regarde si on trouve un tiers qui correspond ainsi que des factures
		$cThirdPartyFiltered = \account\ThirdPartyLib::filterByCashflow($cThirdParty, $eCashflow)->find(fn($e) => $e['weight'] > 0);
		$cInvoice = new \Collection();

		if($cThirdPartyFiltered->notEmpty()) {

			foreach($cThirdPartyFiltered as $eThirdParty) {

				if($eThirdParty['customer']->notEmpty()) {
					// On va chercher des factures en attente de ces clients dont le montant correspond à 1€ près
					$cInvoice->appendCollection(\selling\InvoiceLib::getByCustomer($eThirdParty['customer'])->find(fn($e) => $e['paymentStatus'] === \selling\Invoice::NOT_PAID and abs($e['priceIncludingVat'] - $eCashflow['amount']) < 1));
				}

			}

		}

		return $cInvoice;

	}

	public static function getAll(\Search $search, bool $hasSort): \Collection {

			$cCashflow = self::applySearch($search)
				->select(Cashflow::getSelection())
				->sort($hasSort === TRUE ? $search->buildSort() : ['date' => SORT_DESC, 'fitid' => SORT_DESC])
				->getCollection();

			$cThirdParty = \account\ThirdPartyLib::getAll(new \Search());

			foreach($cCashflow as &$eCashflow) {
				if($eCashflow['status'] === Cashflow::WAITING) {
					$eCashflow['cInvoice'] = self::searchInvoices($cThirdParty, $eCashflow);
				} else {
					$eCashflow['cInvoice'] = new \Collection();
				}
			}

			return $cCashflow;

	}

	public static function countByStatus(\Search $search): \Collection {

		$searchWithoutStatus = new \Search($search->getFiltered(['status']));

			return self::applySearch($searchWithoutStatus)
				->select(['count' => new \Sql('COUNT(*)'), 'status'])
				->group(['status'])
				->getCollection(NULL, NULL, 'status');
	}

	public static function insertMultiple(array $cashflows, \farm\Farm $eFarm): array {

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

			if(
				$eFarm['company']->isAccrualAccounting()
				and \account\FinancialYearLib::isDateLinkedToFinancialYear($date, $cFinancialYear) === FALSE
			) {
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
