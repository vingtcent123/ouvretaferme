<?php
namespace journal;

class VatDeclaration extends VatDeclarationElement {
	public static function getSelection(): array {

		return parent::getSelection() + [
				'financialYear' => \account\FinancialYear::getSelection(),
			];

	}
}
?>
