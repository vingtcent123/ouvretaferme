<?php
namespace dev;

/**
 * System management
 */
class SystemLib {

	/**
	 * Execute a command in the given app
	 *
	 * @param string $app
	 * @param string $command
	 * @return array Command output
	 */
	public static function command(string $app, string $command): array {

		exec(\Setting::get('php').' '.LIME_DIRECTORY.'/framework/lime.php -a '.$app.' '.$command, $output);

		return $output;

	}

	/**
	 * Gets the list of servers in /etc/hosts file
	 *
	 * @param string $prefix
	 * @param string $type
	 */
	public static function getHosts(string $prefix, string $type = 'web'): array {

		$handle = fopen('/etc/hosts', 'r');

		$hosts = [];

		if($handle) {

			while(($line = fgets($handle)) !== false) {

				$results = [];
				$count = preg_match('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)\\s('.$prefix.'-'.$type.'[0-9]+)/', trim($line), $results);

				if($count > 0) {
					$hosts[$results[1]] = $results[2];
				}

			}

			fclose($handle);
		}

		return $hosts;
	}

}
?>
