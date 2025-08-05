<?php
namespace bank;

class CashflowInvoiceLib extends CashflowInvoice {

	public static function delegateByInvoice(): CashflowInvoiceModel {

		return CashflowInvoice::model()
			->select(CashflowInvoice::getSelection())
			->delegateCollection('cashflow', 'id');

	}

	public static function associateInvoicesToCashflow(): void {

		// Boucle sur toutes les fermes qui ont la comptabilité
		$cCompany = \company\CompanyLib::getList();

		foreach($cCompany as $eCompany) {

			$eFarm = $eCompany['farm'];
			\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);

			$cThirdParty = \account\ThirdPartyLib::getAll(new \Search());

			$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYears()->first();
			$search = new \Search([
				'financialYear' => $eFinancialYear,
				'status' => Cashflow::WAITING,
				]);
			$cCashflow = CashflowLib::getAll($search, FALSE);

			foreach($cCashflow as $eCashflow) {

				$cInvoice = self::searchInvoices($cThirdParty, $eCashflow);

				foreach($cInvoice as $eInvoice) {

					$eCashflowInvoice = new CashflowInvoice([
						'cashflow' => $eCashflow,
						'invoice' => $eInvoice,
					]);

					CashflowInvoice::model()->option('add-replace')->insert($eCashflowInvoice);

				}
			}

		}

	}

	private static function searchInvoices(\Collection $cThirdParty, Cashflow $eCashflow): \Collection {

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

}
