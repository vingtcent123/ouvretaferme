<?php
namespace util;

/**
 * Facilitates CURL requests for HTTP/HTTPs
 */
class CurlLib {

	/**
	 * Info for last CURL request
	 *
	 * @var array
	 */
	protected ?array $infos = NULL;

	/**
	 * Execute an HTTP request using CURL
	 *
	 */
	public function exec(string $url, $params, string $mode = 'GET', array $options = []) {

		$curl = $this->prepare($url, $params, $mode, $options);

		$content = curl_exec($curl);

		$this->infos = [
			'httpinfo' => (int)curl_getinfo($curl, CURLINFO_HTTP_CODE),
			'headersize' => curl_getinfo($curl, CURLINFO_HEADER_SIZE),
			'headerout' => curl_getinfo($curl, CURLINFO_HEADER_OUT)
		];

		$errno = curl_errno($curl);

		if($errno !== 0) {
			throw new \Exception(curl_error($curl), $errno);
		}

		curl_close($curl);

		return $content;

	}

	/**
	 * Get infos for the last CURL request
	 */
	public function getLastInfos(): ?array {
		return $this->infos;
	}

	/**
	 * Prepare a CURL query
	 *
	 */
	protected function prepare(string $url, $params, $mode, array $options) {

		$curl = curl_init();

		if($mode === 'POST') {
			if(is_array($params)) {
				foreach($params as $value) {
					if(is_array($value) or (is_string($value) and substr($value, 0, 1) === '@')) {
						$params = http_build_query($params);
						break;
					}
				}
			}

			curl_setopt_array($curl, [
				CURLOPT_POST => TRUE,
				CURLOPT_POSTFIELDS => $params
			]);
		} else {
			$url .= (strpos($url, '?') === FALSE ? '?' : '&').(is_array($params) ? http_build_query($params) : $params);
		}

		curl_setopt_array($curl, $options + [
			CURLOPT_URL => $url,
			CURLOPT_HEADER => FALSE,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_REFERER => \Lime::getUrl().'/',
			CURLOPT_TIMEOUT => 30,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_FAILONERROR => FALSE
		]);

		return $curl;

	}

}

?>
