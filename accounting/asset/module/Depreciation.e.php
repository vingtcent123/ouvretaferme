<?php
namespace asset;

class Depreciation extends DepreciationElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'financialYear' => \account\FinancialYear::getSelection(),
			];

	}
}
?>
