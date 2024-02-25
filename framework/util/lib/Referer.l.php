<?php
namespace util;

/**
 * Referer handling
 *
 */
class RefererLib {

	/**
	 * Detect referal origin
	 * - SEO (google, bing)
	 * - Facebook
	 * - Mail (gmail, yahoomail)
	 * - Twitter
	 *
	 * @return string [seo, facebook, mail, unknown]
	 */
	public static function detect(): string {

		$url = SERVER('HTTP_REFERER');

		if($url === '') {
			return 'unknown';
		}

		$host = parse_url($url, PHP_URL_HOST);

		if($host === NULL) {
			return 'unknown';
		}

		switch($host) {

			case 'twitter.com' :
			case 't.co' :
				return 'twitter';

			case 'facebook.com' :
			case 'www.facebook.com' :
				return 'facebook';

			default :

				if(preg_match('/(\.|^)facebook\.[a-z]+$/', $host)) {
					return 'facebook';
				}

				if(preg_match('/(\.|^)google\.[a-z]+$/', $host) or preg_match('/(\.|^)bing\.[a-z]+$/', $host)) {
					return 'seo';
				}

				if(
					strpos($host, 'webmail') !== FALSE or
					strpos($host, 'mail.') === 0 or
					$host === 'outlook.com' or $host === 'www.outlook.com'
				) {
					return 'mail';
				}

				return 'unknown';

		}

	}

}

?>
