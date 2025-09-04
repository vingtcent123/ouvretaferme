<?php
namespace main;

class CryptLib {

	const METHOD = 'AES256';

	public static function encrypt(string $data, string $secret): string {

		$passphrase = MainSetting::$crypt[$secret] ?? throw new \Exception('Missing secret');

		$ivLength = openssl_cipher_iv_length(self::METHOD);
		$iv = openssl_random_pseudo_bytes($ivLength);

		return base64_encode($iv.openssl_encrypt($data, self::METHOD, $passphrase, iv: $iv));

	}

	public static function decrypt(string $data, string $secret): ?string {

		$passphrase = MainSetting::$crypt[$secret] ?? throw new \Exception('Missing secret');
		$decodedData = base64_decode($data);

		$ivLength = openssl_cipher_iv_length(self::METHOD);
		$iv = substr($decodedData, 0, $ivLength);

		$decryptedData = openssl_decrypt(substr($decodedData, $ivLength), self::METHOD, $passphrase, iv: $iv);

		if($decryptedData === FALSE) {
			return NULL;
		} else {
			return $decryptedData;
		}

	}

}
?>
