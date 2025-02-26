<?php
Privilege::register('selling', [
	'admin' => FALSE,
]);

Setting::register('selling', [

	'unitDefaultId' => 1,

	'exampleSalePro' => 1736,
	'exampleSalePrivate' => 3133,

	'vatRates' => [

		// France
		0 => 0,
		1 => 2.1,
		2 => 5.5,
		3 => 10,
		4 => 20

	],

	'defaultVatRate' => 20,

	'documentExpires' => 15, // Délai d'expiration des documents avant suppression de la base de données (en mois)
	'compositionLocked' => 30, // Nombre de jours qui permet de créer, modifier ou supprimer une composition dans le passé

	'remoteKey' => fn() => throw new Exception('Undefined remote key')

]);
?>
