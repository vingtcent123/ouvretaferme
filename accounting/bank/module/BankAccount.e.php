<?php
namespace bank;

class BankAccount extends BankAccountElement {

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('label.numbers', function(string $label): bool {

				return \account\ClassLib::isFromClass($label, \account\AccountSetting::BANK_ACCOUNT_CLASS);

			});

		parent::build($properties, $input, $p);

	}

}
?>
