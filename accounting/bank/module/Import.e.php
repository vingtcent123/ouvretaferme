<?php
namespace bank;

class Import extends ImportElement {

	public function acceptUpdateAccount(): bool {

		return $this['account']->empty() and $this['status'] !== Import::NONE;

	}

	public function acceptDelete(): bool {

		return (\bank\Cashflow::model()
			->whereStatus(\bank\Cashflow::ALLOCATED)
			->whereImport($this)
			->count() === 0);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('account.check', function (BankAccount $eBankAccount): bool {

				return $eBankAccount->notEmpty();

			});

		parent::build($properties, $input, $p);

	}
}
?>
