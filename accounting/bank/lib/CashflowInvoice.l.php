<?php
namespace bank;

class CashflowInvoiceLib extends CashflowInvoice {

	const DELAY_ASSOCIATE_INVOICES = 2; // En heures
	const AMOUNT_MARGIN = 1; // En euros

	public static function delegateByInvoice(): CashflowInvoiceModel {

		return CashflowInvoice::model()
			->select(CashflowInvoice::getSelection())
			->delegateCollection('cashflow', 'id');

	}

	public static function associateInvoiceToCashflow(\selling\Invoice $eInvoice): void {

		$eInvoice->expects(['farm', 'customer']);

		$eFarm = $eInvoice['farm'];

		if($eFarm->hasAccounting() === FALSE) {
			return;
		}

		\company\CompanyLib::connectSpecificDatabaseAndServer($eInvoice['farm']);

		$cCashflow = self::searchCashflows($eInvoice);

		if($cCashflow->empty()) {
			return;
		}

		foreach($cCashflow as $eCashflow) {

			$eCashflowInvoice = new CashflowInvoice([
				'cashflow' => $eCashflow,
				'invoice' => $eInvoice,
			]);

			CashflowInvoice::model()->option('add-replace')->insert($eCashflowInvoice);

		}

	}

	public static function associateInvoicesToCashflow(): void {

		// Boucle sur toutes les fermes qui ont la comptabilité
		$cCompany = \company\CompanyLib::getList();

		foreach($cCompany as $eCompany) {

			$eFarm = $eCompany['farm'];
			\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);

			// On récupère toutes les opérations bancaires importées il y a moins de 2h

			$cThirdParty = \account\ThirdPartyLib::getAll(new \Search());

			$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYears()->first();
			$search = new \Search([
				'financialYear' => $eFinancialYear,
				'status' => Cashflow::WAITING,
				'createdAt' => new \Sql('NOW() - INTERVAL '.self::DELAY_ASSOCIATE_INVOICES.' HOUR'),
				'type' => Cashflow::CREDIT,
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

	private static function searchCashflows(\selling\Invoice $eInvoice): \Collection {

		$eThirdParty = \account\ThirdPartyLib::getByCustomer($eInvoice['customer']);

		$search = new \Search([
			'status' => Cashflow::WAITING,
			'type' => Cashflow::CREDIT,
			'amountMin' => $eInvoice['priceIncludingVat'] - self::AMOUNT_MARGIN,
			'amountMax' => $eInvoice['priceIncludingVat'] + self::AMOUNT_MARGIN,
		]);

		$cCashflowRaw = CashflowLib::getAll($search, FALSE);

		$cCashflow = new \Collection();

		foreach($cCashflowRaw as $eCashflow) {

			$weight = \account\ThirdPartyLib::extractWeightByCashflow($eThirdParty, $eCashflow);

			if($weight > 0) {
				$cCashflow->append($eCashflow);
			}

		}


		return $cCashflow;

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
