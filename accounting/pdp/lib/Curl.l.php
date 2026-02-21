<?php
namespace pdp;

Class CurlLib {

	public static function send(string $accessToken, string $url, mixed $params, string $mode): ?array {

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$accessToken,
				'Content-Type: application/json',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec($url, $params, $mode, $options), TRUE);

		return $data;
	}

}
