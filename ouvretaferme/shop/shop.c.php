<?php
Privilege::register('shop', [
	'admin' => FALSE,
	'access' => FALSE,
]);

Setting::register('shop', [
	'domain' => fn() => \shop\Shop::isEmbed() ? Setting::get('shop\embed') : Setting::get('shop\base'),
	'base' => 'boutique.'.Lime::getDomain(),
	'embed' => 'embed.'.Lime::getDomain(),
]);
?>
