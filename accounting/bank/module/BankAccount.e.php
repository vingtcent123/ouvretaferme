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

		$p
			->setCallback('description.duplicate', function (?string $description): bool {

				if(empty($description)) {
					return TRUE;
				}

				return BankAccount::model()
					->whereDescription($description)
					->where(fn() => 'id != '.$this['id'], if: isset($this['id']))
					->exists() === FALSE;

			});
		parent::build($properties, $input, $p);

	}

}
?>
