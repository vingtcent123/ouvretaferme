<?php
namespace farm;

Class AccountingLib {

	private static function getDocument(\selling\Sale $eSale): string {

		if($eSale['invoice']->empty()) {
			return $eSale['document'];
		}
		return $eSale['invoice']['name'];

	}
	private static function getDocumentDate(\selling\Sale $eSale): string {

		if($eSale['invoice']->empty()) {
			return $eSale['deliveredAt'];
		}
		return $eSale['invoice']['date'];

	}

	private static function getPaymentMethod(\selling\Sale $eSale): string {

		if($eSale['cPayment']->notEmpty()) {
			return $eSale['cPayment']->first()['method']['name'];
		}

		return '';
	}

	/**
	 * TODO : améliorer
	 * - la date de règlement (3è colonne en partant de la fin)
	 * - la date de validation (16è colonne)
	 */
	public static function getFec(\farm\Farm $eFarm, string $from, string $to): array {

		$cAccount = \account\AccountLib::getAll();
		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$cSale = \selling\SaleLib::filterForAccounting($eFarm, new \Search(['from' => $from, 'to' => $to]))
      ->select([
        'id',
        'document',
        'type', 'profile', 'marketParent',
        'customer' => ['id', 'name'],
        'deliveredAt',
        'invoice' => ['name', 'date'],
        'cPayment' => \selling\Payment::model()
					->select(\selling\Payment::getSelection())
					->or(
						fn() => $this->whereOnlineStatus(NULL),
						fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
					)
					->delegateCollection('sale', 'id'),
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
			$documentDate = self::getDocumentDate($eSaleReference);
			$paymentMethod = self::getPaymentMethod($eSale);
			$compAuxLib = $eSaleReference['customer']['name'];

			$date = $eSaleReference['deliveredAt'];

			foreach($ccItem as $accountId => $cItem) {

				foreach($cItem as $vatRate => $eItem) {

					$vatRate /= 100; // Multiplié par 100 dans le SQL pour l'index entier.

					$eAccountDefault = new \account\Account(['class' => '', 'description' => '', 'vatRate' => $eItem['vatRate'], 'vatAccount' => $eAccountVatDefault]);

					if($accountId) {
						$eAccount = $cAccount->offsetGet($accountId);
					} else {
						$eAccount = new \account\Account();
					}
					if($eAccount->empty()) { // Si les données n'ont pas été redressées on prend la classe de TVA par défaut
						$eAccount = $eAccountDefault;
					}

					$amountExcludingVat = $eItem['priceStats'];

					if(round($vatRate, 2) !== 0.0) {
						$amountVat = round($amountExcludingVat * $vatRate / 100, 2);
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
						$compAuxLib,
						$document,
						date('Ymd', strtotime($documentDate)),
						'',
						$amountExcludingVat > 0 ? $amountExcludingVat : 0,
						$amountExcludingVat < 0 ? abs($amountExcludingVat) : 0,
						'',
						'',
						'',
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
							$compAuxLib,
							$document,
							date('Ymd', strtotime($documentDate)),
							'',
							$amountVat > 0 ? $amountVat : 0,
							$amountVat < 0 ? abs($amountVat) : 0,
							'',
							'',
							'',
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
