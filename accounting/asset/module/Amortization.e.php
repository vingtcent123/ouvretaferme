<?php
namespace asset;

class Amortization extends AmortizationElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'financialYear' => \account\FinancialYear::getSelection(),
			];

	}
}
?>
