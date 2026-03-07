<?php
namespace farm;

class FarmSetting extends \Settings {

	const SEASON_BEGIN = '01-01';

	const INVITE_DELAY = 7;

	const CATEGORIES_LIMIT = 5;

	const NEW_SEASON = 10;

	const CALENDAR_LIMIT = 20;


	const CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL = 1000;
	const CATEGORIE_JURIDIQUE_SOCIETE_ANONYME = ['from' => 5410, 'to' => 5710];
	const CATEGORIE_GAEC = 6533;


	public static $mainActions;

	public static $featureTime;
	public static $featureStock;

	public static function getDatabaseName(\farm\Farm $eFarm): string {

		if(OTF_DEMO) {
			return 'demo_ouvretaferme';
		}

		return (LIME_ENV === 'dev' ? 'dev_' : '').'farm_'.$eFarm['id'];
	}

	public static function getStartPackages(): array {
		return ['securing'];
	}

	public static function getAccountingPackages(): array {
		return ['account', 'asset', 'bank', 'journal', 'overview', 'preaccounting', 'invoicing', 'vat', 'cash', 'pdp'];
	}

	public static function getPackages(): array {
		return array_merge(self::getStartPackages(), self::getAccountingPackages());
	}

}

?>
