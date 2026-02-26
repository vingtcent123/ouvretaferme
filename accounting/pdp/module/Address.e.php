<?php
namespace pdp;

class Address extends AddressElement {

	public function acceptDelete(): bool {

		$this->expects(['isReplyTo']);

		return ($this['isReplyTo'] === FALSE and AddressLib::countValidAddresses() > 1);

	}

	public static function checkScheme(string $identifier, \user\Country $eCountry): bool {

		return in_array($identifier, AddressLib::getSchemesByCountry($eCountry));

	}

	public static function checkElectronicAddress(string $address, string $siret): bool {

		$siren = mb_substr($siret, 0, 9);

		preg_match('/([0-9]{9})(\_[a-zA-Z0-9]+)?(\_[a-zA-Z0-9]+)?/m', $address, $matches);

		$parts = str_contains($address, '_') + 1;

		if($parts === 1) {
			return $matches[1] === $siren and $matches[1] === $address;
		}

		if($parts === 2) {
			return $matches[1] === $siren;
		}

		if($parts === 3) {
			return $matches[1] === $siren and $matches[2] === $siret;
		}
		return FALSE;

	}

	public function formatElectronicAddress(bool $withScheme): string {

		$this->expects(['electronicAddress']);

		if($withScheme) {
			return $this['electronicAddress'];
		}

		return last(explode(':', $this['electronicAddress']));

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('electronicAddress.prepare', function(?string &$electronicAddress): bool {

				if($electronicAddress === NULL) {
					return FALSE;
				}

				$eFarm = \farm\Farm::getConnected();

				if(LIME_ENV === 'dev') {
					$electronicAddress = '315143296_103_'.uniqid();
					return TRUE;
				}

				return Address::checkElectronicAddress($electronicAddress, $eFarm['siret']);

			})
			->setCallback('electronicAddress.check', function(?string &$electronicAddress) use($p): bool {

				$electronicAddress = AddressLib::formatElectronicAddress($electronicAddress);

				return Address::model()->whereElectronicAddress($electronicAddress)->count() === 0;

			});

		parent::build($properties, $input, $p);

	}

}
?>
