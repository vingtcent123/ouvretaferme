<?php
namespace journal;

class ThirdParty extends ThirdPartyElement {

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
