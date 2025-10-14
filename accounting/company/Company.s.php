<?php
namespace company;

class CompanySetting extends \Settings {

	public static $mindeeApiKey;

	public static $accountingBetaTestFarms = [];

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
