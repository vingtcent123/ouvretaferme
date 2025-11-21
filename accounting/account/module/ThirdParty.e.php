<?php
namespace account;

class ThirdParty extends ThirdPartyElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'customer' => \selling\Customer::getSelection(),
			];

	}

	public function hasOperations(): bool {
		return (\journal\Operation::model()
			->whereThirdParty($this)
			->count() > 0);
	}
	public function canDelete(): bool {

		return ($this->exists() === TRUE and $this->hasOperations() === FALSE);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('clientAccountLabel.format', function(?string $clientAccountLabel): bool {

				return AccountLabelLib::isFromClass($clientAccountLabel, AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS);

			})
			->setCallback('clientAccountLabel.duplicate', function(?string $clientAccountLabel): bool {

				return (ThirdParty::model()
					->whereClientAccountLabel($clientAccountLabel)
					->whereId('!=', fn() => $this['id'], if: $this->exists())
					->count() === 0);

			})
			->setCallback('supplierAccountLabel.format', function(?string $clientAccountLabel): bool {

				return AccountLabelLib::isFromClass($clientAccountLabel, AccountSetting::THIRD_ACCOUNT_SUPPLIER_DEBT_CLASS);

			})
			->setCallback('supplierAccountLabel.duplicate', function(?string $supplierAccountLabel): bool {

				return (ThirdParty::model()
					->whereSupplierAccountLabel($supplierAccountLabel)
					->whereId('!=', fn() => $this['id'], if: $this->exists())
					->count() === 0);

			})
			->setCallback('name.duplicate', function(?string $name): bool {

				return (ThirdParty::model()
					->whereName($name)
					->whereId('!=', fn() => $this['id'], if: $this->exists())
					->count() === 0);

			});

		parent::build($properties, $input, $p);

	}
}
?>
