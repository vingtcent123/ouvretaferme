<?php
namespace securing;

class SignatureLib {

	/**
	 * Vérifications
	 * - Statut
	 * - Montant
	 * - Moyens de paiement
	 */
	public static function getSaleData(\selling\Sale $eSale, ?\Collection $cItem = NULL, ?\Collection $cPayment = NULL): array {

		// Les données peuvent être parfois déjà présentes
		$cPayment ??= \selling\PaymentLib::getBySale($eSale);
		$cItem ??= \selling\SaleLib::getItems($eSale);

		$paymentMethods = [];

		foreach((clone $cPayment)->sort('methodName') as $ePayment) {
			$paymentMethods[] = [
				'amountIncludingVat' => $ePayment['amountIncludingVat'],
				'methodName' => $ePayment['methodName']
			];
		}

		$items = [];

		foreach((clone $cItem)->sort('id') as $eItem) {

			$items[] = [
				'name' => $eItem['name'],
				'quantity' => $eItem['number'] * ($eItem['packaging'] ?? 1),
				'unitPrice' => $eItem['unitPrice'],
				'price' => $eItem['price'],
				'vatRate' => $eItem['vatRate']
			];

		}

		return [
			'id' => $eSale['id'],
			'status' => $eSale['preparationStatus'],
			'amountIncludingVat' => $eSale['priceIncludingVat'],
			'transactionDate' => $eSale['securedAt'],
			'paymentDate' => $eSale['paidAt'],
			'paymentStatus' => $eSale['paymentStatus'],
			'paymentMethods' => $paymentMethods,
			'items' => $items
		];

	}

	public static function signSale(\selling\Sale $eSale, ?\Collection $cItem = NULL, ?\Collection $cPayment = NULL): void {

		self::sign($eSale['farm'], Signature::SALE, $eSale['id'], self::getSaleData($eSale, $cItem, $cPayment));

	}

	private static function sign(\farm\Farm $eFarm, string $source, int $reference, array $data): void {
return;
		\farm\FarmLib::connectDatabase($eFarm);

		Signature::model()->beginTransaction();

			$dataBefore = Signature::model()
				->whereSource($source)
				->whereReference($reference)
				->sort([
					'id' => SORT_DESC
				])
				->getValue('data');

			$e = new Signature([
				'source' => $source,
				'reference' => $reference,
				'data' => serialize($data),
			]);

			if($dataBefore !== $e['data']) {

				$e['key'] = count(\main\MainSetting::$hmacKeys) - 1;

				$e['hmacChained'] = Signature::model()
					->sort([
						'id' => SORT_DESC
					])
					->getValue('hmac');

				$e['hmac'] = self::getHmac($e);

				Signature::model()->insert($e);

			}

		Signature::model()->commit();


	}

	public static function getHmac(Signature $e) {

		$e->expects(['data', 'hmacChained', 'key']);

		return hash_hmac(
			'sha256',
			$e['hmacChained'].':'.$e['data'],
			\main\MainSetting::$hmacKeys[$e['key']]
		);

	}

}
