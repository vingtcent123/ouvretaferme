<?php
namespace pdp;

class Address extends AddressElement {

	public function acceptDelete(): bool {

		$this->expects(['isReplyTo']);

		return $this['isReplyTo'] === FALSE;

	}

	public function getIdentifier(bool $withScheme): string {

		$this->expects(['identifier']);

		if($withScheme) {
			return $this['identifier'];
		}

		return last(explode(':', $this['identifier']));

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('identifier.prepare', function(?string &$identifier): bool {

				if($identifier === NULL) {
					return FALSE;
				}

				$eFarm = \farm\Farm::getConnected();
				$siret = $eFarm['siret'];
				$siren = mb_substr($eFarm['siret'], 0, 9);

				preg_match('/([0-9]{0, 9})(\_[a-zA-Z0-9]+)?(\_[a-zA-Z0-9]+)?/m', $identifier, $matches);

				$parts = str_contains($identifier, '_') + 1;

				if(LIME_ENV === 'dev') {
					$identifier = '315143296_103_'.uniqid();
					return TRUE;
				}

				if($parts === 1) {
					return $matches[1] === $siren;
				}

				if($parts === 2) {
					return $matches[1] === $siren;
				}

				if($parts === 3) {
					return $matches[1] === $siren and $matches[2] === $siret;
				}

				return FALSE;

			})
			->setCallback('identifier.check', function(?string &$identifier) use($p): bool {

				$identifier = AddressLib::formatIdentifier($identifier);

				return Address::model()->whereIdentifier($identifier)->count() === 0;

			});

		parent::build($properties, $input, $p);

	}

}
?>
