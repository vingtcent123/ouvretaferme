<?php
Privilege::register('selling', [
	'admin' => FALSE,
]);

Setting::register('selling', [

	'exampleSale' => 1282,

	'vatRates' => [

		// France
		1 => 2.1,
		2 => 5.5,
		3 => 10,
		4 => 20

	],

	'defaultVatRate' => 20,

	'documentExpires' => 15, // Délai d'expiration des documents avant suppression de la base de données (en mois)

	'remoteKey' => fn() => throw new Exception('Undefined remote key')

]);
?>
