<?php
namespace company;

class CompanySetting extends \Settings {

	public static $mindeeApiKey;

	public static $accountingBetaTestFarms = [];

	public static $inseeApiKey;

	const CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL = 1000;
	const CATEGORIE_JURIDIQUE_SOCIETE_ANONYME = ['from' => 5410, 'to' => 5710];

}

CompanySetting::$mindeeApiKey = fn() => throw new \Exception("No Mindee Api Key set.");

function setAccountingBetaTestFarms(): void {

	if(LIME_HOST and str_starts_with(LIME_HOST, 'demo.')) {
		CompanySetting::$accountingBetaTestFarms = [1];
		return;
	}

	CompanySetting::$accountingBetaTestFarms = [
		7, // Jardins de Tallende
		1679, // Aëlle Le Gall
		1608, // Asso OTF
		368, // Tomates & Potirons
	];
}

setAccountingBetaTestFarms();

?>
