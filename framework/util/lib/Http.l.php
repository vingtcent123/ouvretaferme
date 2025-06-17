<?php
namespace util;

/**
 * Http
 */
class HttpLib  {

	/**
	 * Gets current browser as a string
	 *
	 * @param bool $withVersion
	 *
	 * @return string or null
	 */
	public static function getBrowserString(bool $withVersion = TRUE) {

		$browser = self::getBrowser($withVersion);

		if($browser['browser'] === NULL or $withVersion === FALSE) {
			return $browser['browser'];
		}

		return $browser['browser'].' '.$browser['version'];
	}

	/**
	 * Gets if the used browser is obsolete or not
	 *
	 * @return boolean
	 */
	public static function isObsolete(): bool {

		$browser = self::getBrowser();

		switch($browser['browser']) {

			case 'MSIE':
				return ($browser['version'] < 16);

			case 'Opera':
				return ($browser['version'] < 44);

			case 'Firefox':
				return ($browser['version'] < 54);

			case 'Safari':
				return ($browser['version'] < 11);

			case 'Chrome':
				return ($browser['version'] < 58);

			default:
				return FALSE;

		}
	}

	/**
	 * Get current browser
	 *
	 * @param bool $withVersion
	 * @return array
	 */
	public static function getBrowser(bool $withVersion = TRUE): array {

		// Get browser
		if(server_exists('HTTP_USER_AGENT')) {

			$browser = '';
			$version = '';

			$browsers = [
				'MSIE' => 'MSIE',
				'Opera' => 'Opera',
				'Firefox' => 'Firefox',
				'FxiOS' => 'Firefox',
				'Safari' => 'Safari',
				'Chrome' => 'Chrome',
				'CriOS' => 'Chrome',
			];

			$userAgent = SERVER('HTTP_USER_AGENT');

			foreach($browsers as $browserCheck => $browserName) {

				if(strrpos($userAgent, $browserCheck) !== FALSE) {

					$browser = $browserName;

					if($browserCheck !== 'Safari') {
						$regexp = '@.*('.$browserCheck.'[/ ]?)([\d]+)@';
					} else {
						$regexp = '@.*(Version/)([\d]+)@';
					}

					if($withVersion and preg_match($regexp, $userAgent, $matches)) {
						$version = (int)$matches[2];
					}
				}

			}

		} else {

			$browser = NULL;
			$version = NULL;

		}

		return ['browser' => $browser, 'version' => $version];

	}

}
?>
