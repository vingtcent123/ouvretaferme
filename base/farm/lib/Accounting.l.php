<?php
namespace farm;

Class AccountingLib {

	private static function getDocument(\selling\Sale $eSale): string {

		if($eSale['invoice']->empty()) {
			return $eSale['document'];
		}
		return $eSale['invoice']['name'];

	}

	private static function getPaymentMethod(\selling\Sale $eSale): string {

		if($eSale['cPayment']->notEmpty()) {
			return $eSale['cPayment']->first()['method']['name'];
		}

		return '';
	}

	public static function getFec(\farm\Farm $eFarm, string $from, string $to): array {

		\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);

		$cAccount = \account\AccountLib::getAll();
		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$cSale = \selling\SaleLib::filterForAccounting($eFarm, new \Search(['from' => $from, 'to' => $to]))
      ->select([
        'id',
        'document',
        'items', 'discount',
        'type', 'profile', 'marketParent',
        'customer' => ['name'],
        'priceIncludingVat', 'priceExcludingVat', 'vat',
        'shop' => ['name'],
        'deliveredAt',
        'invoice' => ['name'],
        'cPayment' => \selling\Payment::model()
					->select(\selling\Payment::getSelection())
					->or(
						fn() => $this->whereOnlineStatus(NULL),
						fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
					)
					->delegateCollection('sale', 'id'),
        /*'cItem' => \selling\Item::model()
                       ->select(['id', 'price', 'vatRate', 'account'])
                       ->group(['account', 'vatRate'])
                       ->delegateCollection('sale'),*/
      ])
      ->sort('deliveredAt')
      ->getCollection(NULL, NULL, 'id');

		$headers = \account\FecLib::getHeader();

		$fecData = [$headers];

		foreach($cSale as $eSale) {

			if($eSale->isMarket()) {
				continue;
			}

			if($eSale->isMarketSale()) {
				$eSaleReference = $cSale->offsetGet($eSale['marketParent']['id']);
			} else {
				$eSaleReference = $eSale;
			}

			// Groupement des articles par classe de compte
			$ccItem = \selling\Item::model()
				->select(['account', 'price' => new \Sql('SUM(price)'), 'priceStats' => new \Sql('SUM(priceStats)'), 'vatRate' => new \Sql('vatRate * 100', 'int'), 'account'])
				->whereSale($eSale)
				->group(['account', 'vatRate'])
				->getCollection(NULL, NULL, ['account', 'vatRate']);

			$document = self::getDocument($eSaleReference);
			$paymentMethod = self::getPaymentMethod($eSale);

			$date = $eSaleReference['deliveredAt'];

			$customerId = $eSaleReference['customer']['id'];
			$customerName = $eSaleReference['customer']['name'];

			$cAccountSale = new \Collection();

			foreach($ccItem as $accountId => $cItem) {

				$eAccount = $cAccount->offsetGet($accountId);
				if($eAccount->empty()) { // Si les données n'ont pas été redressées on prend la classe de TVA par défaut
					$eAccount = $eAccountDefault;
				}

				foreach($cItem as $vatRate => $eItem) {

					$vatRate /= 100; // Multiplié par 100 dans le SQL pour l'index entier.

					$eAccountDefault = new \account\Account(['id' => NULL, 'vatRate' => $eItem['vatRate'], 'vatAccount' => $eAccountVatDefault]);

					$amountExcludingVat = $eItem['priceStats'];

					if(round($vatRate, 2) !== 0.0) {
						$amountVat = round($amountExcludingVat * (1 + $vatRate / 100), 2);
					} else {
						$amountVat = 0;
					}

					// Montant HT
					$fecData[] = [
						'',
						'',
						'',
						date('Ymd', strtotime($date)),
						\account\AccountLabelLib::pad($eAccount['class']),
						$eAccount['description'],
						'',
						'',
						$document,
						date('Ymd', strtotime($date)),
						'',
						$amountExcludingVat > 0 ? $amountExcludingVat : 0,
						$amountExcludingVat < 0 ? abs($amountExcludingVat) : 0,
						'',
						'',
						date('Ymd', strtotime($date)),
						$amountExcludingVat,
						'EUR',
						date('Ymd', strtotime($date)),
						$paymentMethod,
						''
					];

					// TVA
					if($amountVat !== 0.0) {

						$eAccountVat = $eAccount['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						$fecData[] = [
							'',
							'',
							'',
							date('Ymd', strtotime($date)),
							\account\AccountLabelLib::pad($eAccountVat['class']),
							$eAccountVat['description'],
							'',
							'',
							$document,
							date('Ymd', strtotime($date)),
							'',
							$amountVat > 0 ? $amountVat : 0,
							$amountVat < 0 ? abs($amountVat) : 0,
							'',
							'',
							date('Ymd', strtotime($date)),
							$amountVat,
							'EUR',
							date('Ymd', strtotime($date)),
							$paymentMethod,
							''
						];

					}

				}

			}

		}
		return $fecData;

	}
}

?>
