<?php
namespace selling;

class SellingSetting extends \Settings {

	const CUSTOMER = 'C';
	const ORDER_FORM = 'DE';
	const DELIVERY_NOTE = 'BL';
	const INVOICE = 'FA';

	const UNIT_DEFAULT_ID = 1;

	const EXAMPLE_SALE_PRO = 1736;
	const EXAMPLE_SALE_PRIVATE = 3133;

	public static function getStandardVat(\farm\Farm $eFarm): float {

		$eFarm->expects(['legalCountry']);

		$eCountry = $eFarm['legalCountry'];

		if($eCountry['id'] === \user\UserSetting::FR) {
			return 4;
		} else if($eCountry['id'] === \user\UserSetting::BE) {
			return 103;
		} else {
			return 999;
		}

	}

	public static function getStandardVatRate(\farm\Farm $eFarm): float {

		return self::getVatRate($eFarm, self::getStandardVat($eFarm));

	}

	public static function getVatRates(\farm\Farm $eFarm): array {

		$eFarm->expects(['legalCountry']);

		$eCountry = $eFarm['legalCountry'];

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
		return SellingSetting::getVatRates($eFarm)[$rate] ?? throw new \Exception('Unknown rate '.$rate.' for farm '.$eFarm['id']);
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

	const COMPOSITION_LOCKED = 30; // Nombre de jours qui permet de créer, modifier ou supprimer une composition dans le passé

	public static $remoteKey;
}

SellingSetting::$remoteKey = fn() => throw new \Exception('Undefined remote key');

?>
