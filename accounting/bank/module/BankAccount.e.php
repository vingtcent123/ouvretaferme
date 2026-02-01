<?php
namespace bank;

class BankAccount extends BankAccountElement {

	public static function getSelection(): array {

		return parent::getSelection() + ['account' => \account\Account::getSelection()];

	}

	public function acceptDelete(): bool {
		return (
			Cashflow::model()->whereAccount($this)->whereStatus('NOT IN', [Cashflow::WAITING, Cashflow::DELETED])->exists() === FALSE
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		parent::build($properties, $input, $p);

	}

}
?>
