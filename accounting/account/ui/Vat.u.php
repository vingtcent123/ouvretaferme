<?php
namespace account;

/**
 * Alert messages
 *
 */
class VatUi {

	public function getVatTranslation(): string {

		return s("TVA");

	}

	public function getVatBalanceTranslation(string $accountLabel, FinancialYear $eFinancialYear): string {
		return s("Transfert solde TVA (4456/4457) vers {accountLabel} – exercice {financialYear}", [
			'accountLabel' => $accountLabel,
			'financialYear' => FinancialYearUi::getYear($eFinancialYear)
		]);
	}

	public function getVatLabel(string $account): string {

		return match($account) {
			AccountSetting::VAT_BUY_CLASS_PREFIX => s("TVA versée"),
			AccountSetting::VAT_SELL_CLASS_PREFIX => s("TVA / ventes"),
			AccountSetting::VAT_SELL_CLASS_ACCOUNT => s("TVA / ventes"),
			default => throw new \NotExpectedAction('Unknown account for Vat Label'),
		};

	}

}
?>
