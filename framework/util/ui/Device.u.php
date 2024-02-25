<?php
namespace util;

/**
 * Device handling
 */
class DeviceUi {

	public static function getName(string $fqn): string {

		switch($fqn) {

			case 'web' :
				return s("Web");

			case 'app' :
				return s("App");

			case 'mobile-web' :
				return s("Web mobile");

			case 'tablet-web' :
				return s("Web tablette");

			case 'crawler' :
				return s("Crawler");

		}

	}

}

?>
