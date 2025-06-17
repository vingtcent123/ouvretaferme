<?php
namespace util;

/**
 * Http
 */
class HttpUi {

	/**
	 * Remove an argument from a query string
	 *
	 * @param string $request
	 * @param string $argument
	 * @return string
	 */
	public static function removeArgument(string $request, string $argument): string {

		$request = preg_replace('/([\&\?])'.$argument.'(=[a-z0-9\%\:\-]*)*/si', '\\1', $request);
		$request = str_replace('?&', '?', $request);
		$request = str_replace('&&', '&', $request);

		if(
			substr($request, -1) === '?' ||
			substr($request, -1) === '&'
		) {
			$request = substr($request, 0, -1);
		}

		return $request;

	}

	/**
	 * Set an argument from a query string (replace the existing one)
	 *
	 * @param string $request
	 * @param string $argument
	 * @param string $value
	 * @param bool $encode
	 * @return string
	 */
	public static function setArgument(string $request, string $argument, $value = NULL, bool $encode = TRUE): string {

		if($value === NULL) {

			$encodedValue = $argument;

		} else {

			if($encode) {
				$encodedValue = $argument.'='.rawurlencode($value);
			} else {
				$encodedValue = $argument.'='.$value;
			}

		}

		if(preg_match('/([\&\?])'.$argument.'(=[a-z0-9\%\:\-]*)*/si', $request) > 0) {

			$request = preg_replace('/([\&\?])'.$argument.'=([a-z0-9\%\:\-]*)*/si', '\\1'.$encodedValue, $request);

		} else {

			$request .= (strpos($request, '?') === FALSE ? '?' : '&');
			$request .= $encodedValue;

		}

		return $request;

	}

}
?>
