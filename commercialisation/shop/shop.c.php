<?php
Privilege::register('shop', [
	'admin' => FALSE,
	'access' => FALSE,
]);

Setting::register('shop', [
	'domain' => 'boutique.'.Lime::getDomain(),
]);
?>
