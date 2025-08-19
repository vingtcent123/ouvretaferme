<?php
Privilege::register('company', [
	'admin' => FALSE,
	'access' => FALSE,
]);

Setting::register('company', [
	'mindeeApiKey' => '',

	'accountingBetaTesterFarms' => [
		7, // Jardins de Tallende
		1375, // Jardin Ouroboros
	]
]);
?>
