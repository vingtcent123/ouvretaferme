<?php
namespace util;

/**
 * Device handling
 *
 * @author Bruno Sabot
 */
class DeviceLib {

	private static $isMobile = NULL;

	/**
	 * Return TRUE if the client is browsing using Safari iOS.
	 *
	 * @return bool
	 */
	public static function iOS(): bool {
		return self::isDevice(['iphone', 'ipod', 'ipad', 'CFNetwork']);
	}

	/**
	 * Returns device name (mobile, web...) of the client
	 * - tablet
	 * - mobile
	 * - app
	 * - crawler (for GoogleBot & Cie)
	 * - web
	 *
	 * @return string
	 */
	public static function get(): string {

		require_once __DIR__.'/CrawlerDetect.php';

		$crawler = new \Jaybizzle\CrawlerDetect\CrawlerDetect();

		if($crawler->isCrawler()) {
			return 'crawler';
		}

		if(\Route::getRequestedWith() === 'app') {
			return 'app';
		}

		if(self::isTablet()) {
			return 'tablet-web';
		} else if(self::isMobile()) {
			return 'mobile-web';
		} else {
			return 'web';
		}

	}

	/**
	 * Returns device version
	 *
	 * @return int
	 */
	public static function version() {

		return SERVER('HTTP_X_APP_VERSION', '?int');

	}

	/**
	 * Return TRUE if the client is using a tablet
	 *
	 * @return bool
	 */
	public static function isTablet(): bool {

		$isTablet = FALSE;

		// Check iPad
		if(self::isDevice(['ipad']) === TRUE) {
			$isTablet = TRUE;
		}

		// Check Android tablets
		if(self::isDevice(['android']) === TRUE and self::isDevice(['mobile']) === FALSE) {
			$isTablet = TRUE;
		}

		return $isTablet;

	}

	/**
	 * Return TRUE if the client is using a mobile phone
	 *
	 * @return bool
	 */
	public static function isMobile(): bool {

		if(self::$isMobile !== NULL) {
			return self::$isMobile;
		}

		if(server_exists('HTTP_USER_AGENT') === FALSE) {
			return FALSE;
		}

		if(self::isTablet()) {
			return FALSE;
		}

		$userAgent = strtolower(SERVER('HTTP_USER_AGENT'));

		$isMobile = FALSE;

		$device = ['up.browser', 'up.link', 'mmp', 'symbian', 'smartphone', 'midp', 'wap', 'phone', 'iphone', 'ipod', 'android', 'xoom', 'CFNetwork'];

		if(self::isDevice($device)) {
			$isMobile = TRUE;
		}

		if(strpos(strtolower(SERVER('HTTP_ACCEPT', 'string', '')), 'application/vnd.wap.xhtml+xml') !== FALSE) {
			$isMobile = TRUE;
		}

		if(server_exists('HTTP_X_WAP_PROFILE')) {
			$isMobile = TRUE;
		}

		if(server_exists('HTTP_PROFILE')) {
			$isMobile = TRUE;
		}

		$mobileUA = substr($userAgent, 0, 4);
		$mobileAgents = [
			'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
			'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
			'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
			'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
			'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
			'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
			'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
			'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
			'wapr','webc','winw','winw','xda','xda-'
		];

		if(in_array($mobileUA, $mobileAgents)) {
			$isMobile = TRUE;
		}

		// Check a little more opera because of the desktop version
		if($mobileUA === 'oper' and strpos($userAgent, 'opera mini') === FALSE and strpos($userAgent, 'opera mobi') === FALSE) {
			$isMobile = FALSE;
		}

		// Check Firefox mobile (aka Fennec)
		if(strpos($userAgent, 'fennec') !== FALSE) {
			$isMobile = TRUE;
		}

		if(strpos(strtolower(SERVER('ALL_HTTP', 'string', '')), 'operamini') !== FALSE) {
			$isMobile = TRUE;
		}

		// Pre-final check to reset everything if the user is on Windows
		if(strpos($userAgent, 'windows') !== FALSE) {
			$isMobile = FALSE;
		}

		// But WP7 is also Windows, with a slightly different characteristic
		if(strpos($userAgent, 'windows phone') !== FALSE or strpos($userAgent, 'windows mobile') !== FALSE) {
			$isMobile = TRUE;
		}

		self::$isMobile = $isMobile;

		return $isMobile;

	}


	private static function isDevice(array $name): bool {

		if(empty($name)) {
			return FALSE;
		}

		$names = implode('|', $name);

		return (bool)preg_match(
			'/('.$names.')/i',
			strtolower(SERVER('HTTP_USER_AGENT', 'string', ''))
		);

	}

}

?>
