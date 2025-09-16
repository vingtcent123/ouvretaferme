<?php
namespace company;

class CompanySetting extends \Settings {

	public static $mindeeApiKey;

	const ACCOUNTING_BETA_TEST_FARMS = [
		7, // Jardins de Tallende
		1679, // Aëlle Le Gall
		1608, // Asso OTF
		368, // Tomates & Potirons
	];

}

CompanySetting::$mindeeApiKey = fn() => throw new \Exception("No Mindee Api Key set.");

?>
