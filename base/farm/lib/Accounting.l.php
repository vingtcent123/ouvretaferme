<?php
namespace farm;

Class AccountingLib {

	const FEC_COLUMN_ACCOUNT_LABEL = 4;
	const FEC_COLUMN_PAYMENT_METHOD = 19;
	const FEC_COLUMN_DEBIT = 11;
	const FEC_COLUMN_CREDIT = 12;
	const FEC_COLUMN_DEVISE_AMOUNT = 16;

	public static function generateFec(\farm\Farm $eFarm, string $from, string $to, \Collection $cFinancialYear): array {

		$cAccount = \account\AccountLib::getAll();

		// Par caisse
		$saleMarketFec = self::extractMarket($eFarm, $from, $to, $cFinancialYear, $cAccount);

		// Par factures
		$invoiceFec = self::extractInvoice($eFarm, $from, $to, $cFinancialYear, $cAccount);

		// Tout le reste des ventes (ni caisse, ni factures)
		$salesFec = self::extractSales($eFarm, $from, $to, $cFinancialYear, $cAccount);

		return array_merge($saleMarketFec, $invoiceFec, $salesFec);

	}

	/**
	 * Extrait les données FEC de toutes les ventes qui ne sont
	 * - ni dans une facture
	 * - ni dans un marché
	 */

	private static function extractSales(\farm\Farm $eFarm, string $from, string $to, \Collection $cFinancialYear, \Collection $cAccount): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$cSale = \selling\SaleLib::filterForAccounting($eFarm, new \Search(['from' => $from, 'to' => $to]))
			->select([
			  'id',
			  'document',
			  'type', 'profile', 'marketParent',
			  'customer' => ['id', 'name'],
			  'deliveredAt',
			  'cPayment' => \selling\Payment::model()
					->select(\selling\Payment::getSelection())
					->or(
						fn() => $this->whereOnlineStatus(NULL),
						fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
					)
					->delegateCollection('sale'),
				'cItem' => \selling\Item::model()
					->select(['id', 'price', 'priceStats', 'vatRate', 'account'])
					->delegateCollection('sale')
			])
			->sort('deliveredAt')
			->whereInvoice(NULL)
			->whereProfile('NOT IN', [\selling\Sale::SALE_MARKET, \selling\Sale::MARKET])
			->getCollection();


		$fecData = [];
		foreach($cSale as $eSale) {

			$hasVat = TRUE;
			if($cFinancialYear->notEmpty()) {
				$eFinancialYear = $cFinancialYear->find(
					fn($e) => $e['startDate'] <= $eSale['deliveredAt'] and $eSale['deliveredAt'] <= $e['endDate']
				)->first();
				if($eFinancialYear->notEmpty() and $eFinancialYear['hasVat'] === FALSE) {
					$hasVat = FALSE;
				}
			}

			$document = $eSale['document'];
			$documentDate = $eSale['deliveredAt'];
			$compAuxLib = ($eSale['customer']['name'] ?? '');
			$compAuxNum = '';

			$items = []; // groupement par accountlabel, moyen de paiement

			$payments = self::explodePaymentsRatio($eSale['cPayment']);

			foreach($eSale['cItem'] as $eItem) {

				$eAccountDefault = new \account\Account(['class' => '', 'description' => '', 'vatRate' => $eItem['vatRate'], 'vatAccount' => $eAccountVatDefault]);

				if($eItem['account']->empty() or $cAccount->offsetExists($eItem['account']['id']) === FALSE) {
					$eAccount = $eAccountDefault;
				} else {
					$eAccount = $cAccount->offsetGet($eItem['account']['id']);
				}

				foreach($payments as $payment) {

					$amountExcludingVat = round(($eItem['priceStats'] * ($payment['rate'] ?? 100) / 100), 2);

					if(round($eItem['vatRate'], 2) !== 0.0) {
						$amountVat = round($amountExcludingVat * $eItem['vatRate'] / 100, 2);
					} else {
						$amountVat = 0.0;
					}

					// Si on n'est pas redevable de la TVA => On enregistre TTC
					if($hasVat === FALSE) {
						$amountExcludingVat += $amountVat;
						$amountVat = 0.0;
					}


					// Montant HT
					$fecDataExcludingVat = [
						$eAccount['journalCode']['code'] ?? '',
						$eAccount['journalCode']['name'] ?? '',
						'',
						date('Ymd', strtotime($eSale['deliveredAt'])),
						$eAccount['class'] === '' ? '' : \account\AccountLabelLib::pad($eAccount['class']),
						$eAccount['description'],
						$compAuxNum,
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
						date('Ymd', strtotime($eSale['deliveredAt'])),
						$payment['label'] ?? '',
						''
					];

					self::mergeFecLineIntoItemData($items, $fecDataExcludingVat);

					// TVA
					if($hasVat and $amountVat !== 0.0) {

						$eAccountVat = $eAccount['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						$fecDataVat = [
							$eAccount['journalCode']['code'] ?? '',
							$eAccount['journalCode']['name'] ?? '',
							'',
							date('Ymd', strtotime($eSale['deliveredAt'])),
							$eAccountVat['class'] === '' ? '' : \account\AccountLabelLib::pad($eAccountVat['class']),
							$eAccountVat['description'],
							$compAuxNum,
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
							date('Ymd', strtotime($eSale['deliveredAt'])),
							$payment['label'] ?? 0,
							''
						];

						self::mergeFecLineIntoItemData($items, $fecDataVat);

					}

				}

			}

			$fecData = array_merge($fecData, $items);

		}

		return $fecData;

	}

	/**
	 * Extrait les données FEC de toutes les ventes affectées à une facture
	 * Caractéristique : 1 facture = 1 moyen de paiement
	 *
	 */
	private static function extractInvoice(\farm\Farm $eFarm, string $from, string $to, \Collection $cFinancialYear, \Collection $cAccount): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$cInvoice = \selling\Invoice::model()
			->select([
				'id', 'date', 'name',
				'customer' => ['id', 'name'],
				'paymentMethod' => ['name'],
				'cSale' => \selling\Sale::model()
					->select([
						'id',
						'cItem' => \selling\Item::model()
							->select(['id', 'price', 'priceStats', 'vatRate', 'account'])
							->delegateCollection('sale')
					])
					->delegateCollection('invoice'),
			])
			->whereFarm($eFarm)
			->where('date BETWEEN '.\selling\Invoice::model()->format($from).' AND '.\selling\Invoice::model()->format($to))
			->getCollection();

		$fecData = [];
		foreach($cInvoice as $eInvoice) {

			$document = $eInvoice['name'];
			$documentDate = $eInvoice['date'];
			$compAuxLib = ($eInvoice['customer']['name'] ?? '');
			$compAuxNum = '';

			$hasVat = TRUE;
			if($cFinancialYear->notEmpty()) {
				$eFinancialYear = $cFinancialYear->find(
					fn($e) => $e['startDate'] <= $eInvoice['date'] and $eInvoice['date'] <= $e['endDate']
				)->first();
				if($eFinancialYear->notEmpty() and $eFinancialYear['hasVat'] === FALSE) {
					$hasVat = FALSE;
				}
			}

			$items = []; // groupement par accountlabel
			$payment = ($eInvoice['paymentMethod']['name'] ?? '');

			foreach($eInvoice['cSale'] as $eSale) {

				foreach($eSale['cItem'] as $eItem) {

					$eAccountDefault = new \account\Account(['class' => '', 'description' => '', 'vatRate' => $eItem['vatRate'], 'vatAccount' => $eAccountVatDefault]);

					if($eItem['account']->empty() or $cAccount->offsetExists($eItem['account']['id']) === FALSE) {
						$eAccount = $eAccountDefault;
					} else {
						$eAccount = $cAccount->offsetGet($eItem['account']['id']);
					}

					$amountExcludingVat = $eItem['priceStats'];

					if(round($eItem['vatRate'], 2) !== 0.0) {
						$amountVat = round($amountExcludingVat * $eItem['vatRate'] / 100, 2);
					} else {
						$amountVat = 0.0;
					}

					// Si on n'est pas redevable de la TVA => On enregistre TTC
					if($hasVat === FALSE) {
						$amountExcludingVat += $amountVat;
						$amountVat = 0.0;
					}

					// Montant HT
					$fecDataExcludingVat = [
						$eAccount['journalCode']['code'] ?? '',
						$eAccount['journalCode']['name'] ?? '',
						'',
						date('Ymd', strtotime($eInvoice['date'])),
						$eAccount['class'] === '' ? '' : \account\AccountLabelLib::pad($eAccount['class']),
						$eAccount['description'],
						$compAuxNum,
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
						date('Ymd', strtotime($eInvoice['date'])),
						$payment,
						''
					];

					self::mergeFecLineIntoItemData($items, $fecDataExcludingVat);

					// TVA
					if($hasVat and $amountVat !== 0.0) {

						$eAccountVat = $eAccount['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						$fecDataVat = [
							$eAccount['journalCode']['code'] ?? '',
							$eAccount['journalCode']['name'] ?? '',
							'',
							date('Ymd', strtotime($eInvoice['date'])),
							$eAccountVat['class'] === '' ? '' : \account\AccountLabelLib::pad($eAccountVat['class']),
							$eAccountVat['description'],
							$compAuxNum,
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
							date('Ymd', strtotime($eInvoice['date'])),
							$payment,
							''
						];

						self::mergeFecLineIntoItemData($items, $fecDataVat);

					}

				}
			}

			$fecData = array_merge($fecData, $items);

		}

		return $fecData;

	}

	/**
	 * Extrait les données FEC des ventes rattachées à des marchés.
	 *
	 */
	private static function extractMarket(\farm\Farm $eFarm, string $from, string $to, \Collection $cFinancialYear, \Collection $cAccount): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$saleModule = new \selling\SaleModel();

		$cSale = \selling\SaleLib::filterForAccounting($eFarm, new \Search(['from' => $from, 'to' => $to]))
			->select([
			  'id',
			  'document',
			  'type', 'profile', 'marketParent',
			  'customer' => ['id', 'name'],
			  'deliveredAt',
				'cSale' => $saleModule
					->select([
						'id',
					  'cPayment' => \selling\Payment::model()
							->select(\selling\Payment::getSelection())
							->or(
								fn() => $this->whereOnlineStatus(NULL),
								fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
							)
							->delegateCollection('sale'),
						'cItem' => \selling\Item::model()
							->select(['id', 'price', 'priceStats', 'vatRate', 'account'])
							->delegateCollection('sale')
					])
					->delegateCollection('marketParent'),
			])
			->sort('deliveredAt')
			->whereProfile(\selling\Sale::MARKET)
			->getCollection(NULL, NULL, 'id');

		$fecData = [];
		foreach($cSale as $eSale) {

			$hasVat = TRUE;
			if($cFinancialYear->notEmpty()) {
				$eFinancialYear = $cFinancialYear->find(
					fn($e) => $e['startDate'] <= $eSale['deliveredAt'] and $eSale['deliveredAt'] <= $e['endDate']
				)->first();
				if($eFinancialYear->notEmpty() and $eFinancialYear['hasVat'] === FALSE) {
					$hasVat = FALSE;
				}
			}

			$document = $eSale['document'];
			$documentDate = $eSale['deliveredAt'];
			$compAuxLib = ($eSale['customer']['name'] ?? '');
			$compAuxNum = '';

			$items = []; // groupement par accountlabel, moyen de paiement
			foreach($eSale['cSale'] as $eSaleMarket) {

				// Il faut déterminer le montant par moyen de paiement pour en extraire un prorata
				$payments = self::explodePaymentsRatio($eSaleMarket['cPayment']);

				foreach($eSaleMarket['cItem'] as $eItem) {

					$eAccountDefault = new \account\Account(['class' => '', 'description' => '', 'vatRate' => $eItem['vatRate'], 'vatAccount' => $eAccountVatDefault]);

					if($eItem['account']->empty() or $cAccount->offsetExists($eItem['account']['id']) === FALSE) {
						$eAccount = $eAccountDefault;
					} else {
						$eAccount = $cAccount->offsetGet($eItem['account']['id']);
					}

					foreach($payments as $payment) {

						$amountExcludingVat = round(($eItem['priceStats'] * $payment['rate'] / 100), 2);

						if(round($eItem['vatRate'], 2) !== 0.0) {
							$amountVat = round($amountExcludingVat * $eItem['vatRate'] / 100, 2);
						} else {
							$amountVat = 0.0;
						}

						// Si on n'est pas redevable de la TVA => On enregistre TTC
						if($hasVat === FALSE) {
							$amountExcludingVat += $amountVat;
							$amountVat = 0.0;
						}


						// Montant HT
						$fecDataExcludingVat = [
							$eAccount['journalCode']['code'] ?? '',
							$eAccount['journalCode']['name'] ?? '',
							'',
							date('Ymd', strtotime($eSale['deliveredAt'])),
							$eAccount['class'] === '' ? '' : \account\AccountLabelLib::pad($eAccount['class']),
							$eAccount['description'],
							$compAuxNum,
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
							date('Ymd', strtotime($eSale['deliveredAt'])),
							$payment['label'],
							''
						];

						self::mergeFecLineIntoItemData($items, $fecDataExcludingVat);

						// TVA
						if($hasVat and $amountVat !== 0.0) {

							$eAccountVat = $eAccount['vatAccount'];
							if($eAccountVat->empty()) {
								$eAccountVat = $eAccountVatDefault;
							}

							$fecDataVat = [
								$eAccount['journalCode']['code'] ?? '',
								$eAccount['journalCode']['name'] ?? '',
								'',
								date('Ymd', strtotime($eSale['deliveredAt'])),
								$eAccountVat['class'] === '' ? '' : \account\AccountLabelLib::pad($eAccountVat['class']),
								$eAccountVat['description'],
								$compAuxNum,
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
								date('Ymd', strtotime($eSale['deliveredAt'])),
								$payment['label'],
								''
							];

							self::mergeFecLineIntoItemData($items, $fecDataVat);

						}

					}

				}


			}

			// Tout remettre dans fecData
			$fecData = array_merge($fecData, $items);

		}

		return $fecData;

	}

	private static function explodePaymentsRatio(\Collection $cPayment): array {

		$payments = [];
		$totalAmount = $cPayment->sum('amountIncludingVat');

		if($totalAmount === 0) {
			return [];
		}

		foreach($cPayment as $ePayment) {
			$payments[$ePayment['method']['id']] = [
				'label' => $ePayment['method']['name'],
				'rate' => round($ePayment['amountIncludingVat'] / $totalAmount * 100, 2),
			];
		}

		return $payments;
	}

	private static function mergeFecLineIntoItemData(array &$items, array $fecLine): void {

		// merge into item list
		$added = FALSE;
		foreach($items as &$item) {
			if(
				$item[self::FEC_COLUMN_ACCOUNT_LABEL] === $fecLine[self::FEC_COLUMN_ACCOUNT_LABEL]
				and $item[self::FEC_COLUMN_PAYMENT_METHOD] === $fecLine[self::FEC_COLUMN_PAYMENT_METHOD]
			) {
				$item[self::FEC_COLUMN_DEBIT] = round($item[self::FEC_COLUMN_DEBIT] + $fecLine[self::FEC_COLUMN_DEBIT], 2);
				$item[self::FEC_COLUMN_CREDIT] = round($item[self::FEC_COLUMN_CREDIT] + $fecLine[self::FEC_COLUMN_CREDIT], 2);
				$item[self::FEC_COLUMN_DEVISE_AMOUNT] = round($item[self::FEC_COLUMN_DEVISE_AMOUNT] + $fecLine[self::FEC_COLUMN_DEVISE_AMOUNT], 2);
				$added = TRUE;
				break;
			}
		}

		if($added === FALSE) {
			$items[] = $fecLine;
		}
	}

}

?>
