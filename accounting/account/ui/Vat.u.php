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

	public function getVatLabel(string $account): string {

		return match($account) {
			\Setting::get('account\vatBuyClassPrefix') => s("TVA versée"),
			\Setting::get('account\vatSellClassPrefix') => s("TVA / ventes"),
			default => throw new \NotExpectedAction('Unknown account for Vat Label'),
		};

	}

}
?>
