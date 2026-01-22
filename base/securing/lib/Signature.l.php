<?php
namespace securing;

class SignatureLib {

	public static function signSale(\selling\Sale $eSale, ?\Collection $cItem = NULL, ?\Collection $cPayment = NULL): void {

		// Les données peuvent être parfois déjà présentes
		$cPayment ??= \selling\PaymentLib::getBySale($eSale);
		$cItem ??= \selling\SaleLib::getItems($eSale);

		$paymentMethods = [];

		foreach($cPayment as $ePayment) {
			$paymentMethods[] = [
				'amountIncludingVat' => $ePayment['amountIncludingVat'],
				'method' => $ePayment['methodName']
			];
		}

		$data = [
			'id' => $eSale['id'],
			'amountIncludingVat' => $eSale['priceIncludingVat'],
			'transactionDate' => $eSale['securedAt'],
			'paymentDate' => $eSale['paidAt'],
			'paymentMethods' => $paymentMethods
		];

		self::sign(new Signature([
			'source' => Signature::SALE,
			'data' => $data,
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
