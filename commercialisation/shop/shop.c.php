<?php
namespace shop;

class ShopSetting extends \Settings {

	public static string $domain = '';
}

ShopSetting::$domain = 'boutique.'.\Lime::getDomain();

ShopSetting::setPrivilege('admin', FALSE);
ShopSetting::setPrivilege('access', FALSE);

?>
