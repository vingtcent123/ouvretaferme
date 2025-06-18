<?php
namespace accounting;

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
			\Setting::get('accounting\vatBuyClassPrefix') => s("TVA versée"),
			\Setting::get('accounting\vatSellClassPrefix') => s("TVA / ventes"),
			default => throw new \NotExpectedAction('Unknown account for Vat Label'),
		};

	}

}
?>
