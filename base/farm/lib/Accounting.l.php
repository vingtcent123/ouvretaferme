<?php
namespace farm;

Class AccountingLib {

	const FEC_COLUMN_DATE = 3;
	const FEC_COLUMN_ACCOUNT_LABEL = 4;
	const FEC_COLUMN_PAYMENT_METHOD = 19;
	const FEC_COLUMN_DOCUMENT = 8;
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

		$contentFec = array_merge($saleMarketFec, $invoiceFec, $salesFec);

		// Tri par date puis numéro de document
		usort($contentFec, function($entry1, $entry2) {
			if($entry1[self::FEC_COLUMN_DATE] < $entry2[self::FEC_COLUMN_DATE]) {
				return -1;
			}
			if($entry1[self::FEC_COLUMN_DATE] > $entry2[self::FEC_COLUMN_DATE]) {
				return 1;
			}
			return $entry1[self::FEC_COLUMN_DOCUMENT] < $entry2[self::FEC_COLUMN_DOCUMENT] ? -1 : 1;
		});

		return array_merge([\account\FecLib::getHeader()], $contentFec);

	}

	/**
	 * Extrait les données FEC de toutes les ventes qui ne sont
	 * - ni dans une facture
	 * - ni dans un marché
	 */

	public static function extractSales(\farm\Farm $eFarm, string $from, string $to, \Collection $cFinancialYear, \Collection $cAccount, bool $forImport = FALSE): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$cSale = \selling\SaleLib::filterForAccounting($eFarm, new \Search(['from' => $from, 'to' => $to]))
			->select([
			  'id',
			  'document',
			  'type', 'profile', 'marketParent',
			  'customer' => [
					'id', 'name',
				  'thirdParty' => \account\ThirdParty::model()
						->select('id', 'clientAccountLabel')
						->delegateElement('customer')
			  ],
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
			->whereAccountingHash(NULL, if: $forImport === TRUE)
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
			$compAuxNum = ($eSale['customer']['thirdParty']['clientAccountLabel'] ?? '');

			$items = []; // groupement par accountlabel, moyen de paiement

			$payments = self::explodePaymentsRatio($eSale['cPayment']);

			foreach($eSale['cItem'] as $eItem) {

				$eAccountDefault = self::getDefaultAccount($eItem['vatRate'], $eAccountVatDefault);

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
					$fecDataExcludingVat = self::getFecLine(
						eAccount    : $eAccount,
						date        : $eSale['deliveredAt'],
						eCode       : $eAccount['journalCode'],
						document    : $document,
						documentDate: $documentDate,
						amount      : $amountExcludingVat,
						payment     : $payment['label'] ?? '',
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
					);

					self::mergeFecLineIntoItemData($items, $fecDataExcludingVat);

					// TVA
					if($hasVat and $amountVat !== 0.0) {

						$eAccountVat = $eAccount['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						$fecDataVat = self::getFecLine(
							eAccount    : $eAccountVat,
							date        : $eSale['deliveredAt'],
							eCode       : $eAccount['journalCode'],
							document    : $document,
							documentDate: $documentDate,
							amount      : $amountVat,
							payment     : $payment['label'],
							compAuxNum  : $compAuxNum,
							compAuxLib  : $compAuxLib,
						);

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
	public static function extractInvoice(\farm\Farm $eFarm, string $from, string $to, \Collection $cFinancialYear, \Collection $cAccount, bool $forImport = FALSE): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$cInvoice = \selling\Invoice::model()
			->select([
				'id', 'date', 'name',
				'customer' => [
					'id', 'name',
					'thirdParty' => \account\ThirdParty::model()
						->select('id', 'clientAccountLabel')
						->delegateElement('customer')

				],
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
			->whereAccountingHash(NULL, if: $forImport === TRUE)
			->where('date BETWEEN '.\selling\Invoice::model()->format($from).' AND '.\selling\Invoice::model()->format($to))
			->getCollection();

		$fecData = [];
		foreach($cInvoice as $eInvoice) {

			$document = $eInvoice['name'];
			$documentDate = $eInvoice['date'];
			$compAuxLib = ($eInvoice['customer']['name'] ?? '');
			$compAuxNum = ($eInvoice['customer']['thirdParty']['clientAccountLabel'] ?? '');

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

					$eAccountDefault = self::getDefaultAccount($eItem['vatRate'], $eAccountVatDefault);

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
					$fecDataExcludingVat = self::getFecLine(
						eAccount    : $eAccount,
						date        : $eInvoice['date'],
						eCode       : $eAccount['journalCode'],
						document    : $document,
						documentDate: $documentDate,
						amount      : $amountExcludingVat,
						payment     : $payment,
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
					);

					self::mergeFecLineIntoItemData($items, $fecDataExcludingVat);

					// TVA
					if($hasVat and $amountVat !== 0.0) {

						$eAccountVat = $eAccount['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						$fecDataVat = self::getFecLine(
							eAccount    : $eAccountVat,
							date        : $eInvoice['date'],
							eCode       : $eAccount['journalCode'],
							document    : $document,
							documentDate: $documentDate,
							amount      : $amountVat,
							payment     : $payment,
							compAuxNum  : $compAuxNum,
							compAuxLib  : $compAuxLib,
						);

						self::mergeFecLineIntoItemData($items, $fecDataVat);

					}

				}
			}

			$fecData = array_merge($fecData, $items);

		}

		return $fecData;

	}

	private static function getMarkets(\farm\Farm $eFarm, string $from, string $to, bool $forImport = FALSE): \Collection {

		$saleModule = clone \selling\Sale::model();

		return \selling\SaleLib::filterForAccounting($eFarm, new \Search(['from' => $from, 'to' => $to]))
			->select([
			  'id',
			  'document',
			  'type', 'profile', 'marketParent',
			  'customer' => [
					'id', 'name',
				  'thirdParty' => \account\ThirdParty::model()
						->select('id', 'clientAccountLabel')
						->delegateElement('customer')
			  ],
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
					->wherePreparationStatus(\selling\Sale::DELIVERED)
					->delegateCollection('marketParent'),
			])
			->sort('deliveredAt')
			->whereProfile(\selling\Sale::MARKET)
			->whereAccountingHash(NULL, if: $forImport === TRUE)
			->getCollection(NULL, NULL, 'id');
	}
	/**
	 * Extrait les données FEC des ventes rattachées à des marchés.
	 *
	 */
	public static function extractMarket(\farm\Farm $eFarm, string $from, string $to, \Collection $cFinancialYear, \Collection $cAccount, bool $forImport = FALSE): array {

		$cSale = self::getMarkets($eFarm, $from, $to, $forImport);

		return self::generateMarketFec($cSale, $cFinancialYear, $cAccount);

	}

	public static function generateMarketFec(\Collection $cSale, \Collection $cFinancialYear, \Collection $cAccount): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

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
			$compAuxNum = ($eSale['customer']['thirdParty']['clientAccountLabel'] ?? '');

			$items = []; // groupement par accountlabel, moyen de paiement

			foreach($eSale['cSale'] as $eSaleMarket) {

				// Il faut déterminer le montant par moyen de paiement pour en extraire un prorata
				$payments = self::explodePaymentsRatio($eSaleMarket['cPayment']);
				if(empty($payments)) { // pas de paiement enregistré !
					$payments = [['rate' => 100, 'label' => '']];
				}

				foreach($eSaleMarket['cItem'] as $eItem) {

					$eAccountDefault = self::getDefaultAccount($eItem['vatRate'], $eAccountVatDefault);

					if($eItem['account']->empty() or $cAccount->offsetExists($eItem['account']['id']) === FALSE) {
						$eAccount = $eAccountDefault;
					} else {
						$eAccount = $cAccount->offsetGet($eItem['account']['id']);
					}

					foreach($payments as $payment) {

						$amountExcludingVat = round(($eItem['priceStats'] * $payment['rate'] / 100), 2);

						if(round($eItem['vatRate'], 2) !== 0.0) {
							$amountVat = round(($eItem['price'] - $eItem['priceStats']) * $payment['rate'] / 100, 2);
						} else {
							$amountVat = 0.0;
						}

						// Si on n'est pas redevable de la TVA => On enregistre TTC
						if($hasVat === FALSE) {
							$amountExcludingVat += $amountVat;
							$amountVat = 0.0;
						}

						// Montant HT
						$fecDataExcludingVat = self::getFecLine(
							eAccount    : $eAccount,
							date        : $eSale['deliveredAt'],
							eCode       : $eAccount['journalCode'],
							document    : $document,
							documentDate: $documentDate,
							amount      : $amountExcludingVat,
							payment     : $payment['label'],
							compAuxNum  : $compAuxNum,
							compAuxLib  : $compAuxLib,
						);

						self::mergeFecLineIntoItemData($items, $fecDataExcludingVat);

						// TVA
						if($hasVat and $amountVat !== 0.0) {

							$eAccountVat = $eAccount['vatAccount'];
							if($eAccountVat->empty()) {
								$eAccountVat = $eAccountVatDefault;
							}

							$fecDataVat = self::getFecLine(
								eAccount    : $eAccountVat,
								date        : $eSale['deliveredAt'],
								eCode       : $eAccount['journalCode'],
								document    : $document,
								documentDate: $documentDate,
								amount      : $amountVat,
								payment     : $payment['label'],
								compAuxNum  : $compAuxNum,
								compAuxLib  : $compAuxLib,
							);

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

	private static function getDefaultAccount(float $vatRate, \account\Account $eAccountVatDefault): \account\Account {

		return new \account\Account([
			'class' => '',
			'description' => '',
			'vatRate' => $vatRate,
			'vatAccount' => $eAccountVatDefault,
			'journalCode' => new \journal\JournalCode()
		]);
	}

	private static function getFecLine(
		\account\Account $eAccount, string $date, \journal\JournalCode $eCode,
		string $document, string $documentDate, float $amount, string $payment, string $compAuxNum, string $compAuxLib
	): array {

		return [
			$eCode['code'] ?? '',
			$eCode['name'] ?? '',
			'',
			date('Ymd', strtotime($date)),
			$eAccount['class'] === '' ? '' : \account\AccountLabelLib::pad($eAccount['class']),
			$eAccount['description'],
			$compAuxNum,
			$compAuxLib,
			$document,
			date('Ymd', strtotime($documentDate)),
			'',
			$amount < 0 ? abs($amount) : 0,
			$amount > 0 ? $amount : 0,
			'',
			'',
			'',
			$amount,
			'EUR',
			date('Ymd', strtotime($date)),
			$payment,
			''
		];
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
