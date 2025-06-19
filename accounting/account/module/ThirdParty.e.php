<?php
namespace account;

class ThirdParty extends ThirdPartyElement {

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
			->setCallback('name.duplicate', function(?string $name): bool {

				$eThirdParty = ThirdPartyLib::getByName($name);

				return $eThirdParty->exists() === FALSE;

			});

		parent::build($properties, $input, $p);

	}
}
?>
