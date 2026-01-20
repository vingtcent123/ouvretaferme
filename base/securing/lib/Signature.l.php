<?php
namespace securing;

class SignatureLib {

	public static function signSale(\selling\Sale $eSale): void {

		$data = [
			'id' => $eSale['id'],
			'date' => $eSale['deliveredAt'],
		];

		self::sign(new Signature([
			'source' => Signature::SALE,
		]));

	}

	private static function sign(Signature $e): void {

		Signature::model()->beginTransaction();

		$e['key'] = count(\main\MainSetting::$hmacKeys) - 1;
		$hmac = last(\main\MainSetting::$hmacKeys);

		Signature::model()->commit();


	}

	public static function getHmac(string $payload, int $key) {

		return hash_hmac('sha256', $payload, \main\MainSetting::$hmacKeys[$key]);

	}

}
