<?php
Privilege::register('shop', [
	'admin' => FALSE,
	'access' => FALSE,
]);

Setting::register('shop', [
	'dnsIP' => '51.83.98.183',
	'domain' => 'boutique.'.Lime::getDomain(),
]);
?>
