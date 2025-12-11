<?php
namespace selling;

class SellingSetting extends \Settings {

	const UNIT_DEFAULT_ID = 1;

	const EXAMPLE_SALE_PRO = 1736;
	const EXAMPLE_SALE_PRIVATE = 3133;

	public static function getStandardVatRate(\farm\Farm $eFarm): float {

		$eCountry = $eFarm->getConf('taxCountry');

		if($eCountry['id'] === \user\UserSetting::FR) {
			return self::getVatRate($eFarm, 4);
		} else if($eCountry['id'] === \user\UserSetting::BE) {
			return self::getVatRate($eFarm, 103);
		} else {
			return self::getVatRate($eFarm, 9999);
		}

	}

	public static function getVatRates(\farm\Farm $eFarm): array {

		$eCountry = $eFarm->getConf('taxCountry');

		if($eCountry['id'] === \user\UserSetting::FR) {

			return [
				0 => 0,
				1 => 2.1,
				2 => 5.5,
				3 => 10,
				4 => 20,
			];

		} else if($eCountry['id'] === \user\UserSetting::BE) {

			return [
				100 => 0,
				101 => 6,
				102 => 12,
				103 => 21,
			];

		} else {

			return [
				9999 => 0
			];

		}

	}

	public static function getVatRate(\farm\Farm $eFarm, int $rate): float {
		return SellingSetting::getVatRates($eFarm)[$rate] ?? throw new \Exception('Unknown rate');
	}

	public static function getStartVat(\user\Country $eCountry): int {

		if($eCountry['id'] === \user\UserSetting::FR) {
			return 2;
		} else if($eCountry['id'] === \user\UserSetting::BE) {
			return 101;
		} else {
			return 9999;
		}

	}

	const DOCUMENT_EXPIRES = 15; // Délai d'expiration des documents avant suppression de la base de données (en mois)
	const COMPOSITION_LOCKED = 30; // Nombre de jours qui permet de créer, modifier ou supprimer une composition dans le passé

	public static $remoteKey;
}

SellingSetting::$remoteKey = fn() => throw new \Exception('Undefined remote key');

?>
