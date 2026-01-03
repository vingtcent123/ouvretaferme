<?php
namespace bank;

class BankAccount extends BankAccountElement {

	public static function getSelection(): array {

		return parent::getSelection();

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('label.duplicate', function(string $label) use($p): bool {

				if($p === 'update') {
					return BankAccount::model()->whereLabel($label)->whereId('!=', $p)->count() === 0;
				} else {
					return BankAccount::model()->whereLabel($label)->count() === 0;
				}

			})
			->setCallback('label.numbers', function(string $label): bool {

				return \account\AccountLabelLib::isFromClass($label, \account\AccountSetting::BANK_ACCOUNT_CLASS);

			});

		parent::build($properties, $input, $p);

	}

}
?>
