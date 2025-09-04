<?php
namespace selling;

class SellingSetting extends \Settings {

	const UNIT_DEFAULT_ID = 1;

	const EXAMPLE_SALE_PRO = 1736;
	const EXAMPLE_SALE_PRIVATE = 3133;

	const VAT_RATES = [

		// France
		0 => 0,
		1 => 2.1,
		2 => 5.5,
		3 => 10,
		4 => 20

	];

	const DEFAULT_VAT_RATE = 20;

	const DOCUMENT_EXPIRES = 15; // Délai d'expiration des documents avant suppression de la base de données (en mois)
	const COMPOSITION_LOCKED = 30; // Nombre de jours qui permet de créer, modifier ou supprimer une composition dans le passé

	public static $remoteKey;
}

SellingSetting::$remoteKey = fn() => throw new \Exception('Undefined remote key');

?>
