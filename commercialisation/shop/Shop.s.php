<?php
namespace shop;

class ShopSetting extends \Settings {

	public static string $domain = '';
}

ShopSetting::$domain = 'boutique.'.\Lime::getDomain();

?>
