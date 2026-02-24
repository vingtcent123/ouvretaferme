<?php
namespace pdp;

class UserObserverLib {

	public static function checkElectronicAddress(string $eAddress, string $siret): bool {

		return Address::checkElectronicAddress($eAddress, $siret);

	}

	public static function getCountrySchemes(\user\Country $eCountry): array {

		return AddressLib::getSchemesByCountry($eCountry);

	}

}
