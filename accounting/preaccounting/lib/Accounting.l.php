<?php
namespace preaccounting;

Class AccountingLib {

	const FEC_COLUMN_JOURNAL_CODE = 0;
	const FEC_COLUMN_JOURNAL_TEXT = 1;
	const FEC_COLUMN_NUMBER = 2;
	const FEC_COLUMN_DATE = 3;
	const FEC_COLUMN_ACCOUNT_LABEL = 4;
	const FEC_COLUMN_ACCOUNT_DESCRIPTION = 5;
	const FEC_COLUMN_DOCUMENT = 8;
	const FEC_COLUMN_DOCUMENT_DATE = 9;
	const FEC_COLUMN_DESCRIPTION = 10;
	const FEC_COLUMN_DEBIT = 11;
	const FEC_COLUMN_CREDIT = 12;
	const FEC_COLUMN_DEVISE_AMOUNT = 16;
	const FEC_COLUMN_PAYMENT_DATE = 18;
	const FEC_COLUMN_PAYMENT_METHOD = 19;
	const FEC_COLUMN_OPERATION_NATURE = 20;
	const EXTRA_FEC_COLUMN_IS_SUMMED = 21; /* Colonne pour savoir si on compte la valeur dans le total (affiché en précompta) */
	const EXTRA_FEC_COLUMN_ORIGIN = 22; /* Colonne pour savoir d'où vient l'écriture */

	public static function generateCashFec(\Collection $cCash, \Collection $cFinancialYear, \Collection $cAccount, \Search $search): array {

		$eAccountFilter = $search->get('account');
		$cRegister = $search->get('cRegister');
		$cMethod = $search->get('cMethod');

		$fecData = [];
		$nCash = 0;

		foreach($cCash as $eCash) {

			$items = [];
			$eRegister = $cRegister->offsetGet($eCash['register']['id']);
			$eRegister['paymentMethod'] = $cMethod->offsetGet($eRegister['paymentMethod']['id']);

			//source: enum(OTHER, SELL_INVOICE, SELL_SALE)
			$fecLines = [];

			switch($eCash['source']) {

				case \cash\Cash::BANK:
					$fecLines = self::generateCashBankLines($eCash, $eRegister, $cAccount);
					break;

				case \cash\Cash::PRIVATE:
					$fecLines = self::generateCashPrivateLines($eCash, $eRegister, $cAccount);
					break;

				case \cash\Cash::BALANCE:
					if($eCash['type'] === \cash\Cash::DEBIT) {
						$eAccount = $cAccount->find(fn($e) => $e['class'] === \account\AccountSetting::CHARGES_OTHER_CLASS)->first();
					} else if($eCash['type'] === \cash\Cash::CREDIT) {
						$eAccount = $cAccount->find(fn($e) => $e['class'] === \account\AccountSetting::PRODUCT_OTHER_CLASS)->first();
					}
					$fecLines = self::generateCashLines($eCash, $eRegister, $eAccount);
					break;

				case \cash\Cash::BUY_MANUAL:
				case \cash\Cash::SELL_MANUAL:
				case \cash\Cash::OTHER:
					if($eCash['account']->empty()) {
						break;
					}
					$eAccount = $cAccount->find(fn($e) => $e['id'] === $eCash['account']['id'])->first();
					$fecLines = self::generateCashLines($eCash, $eRegister, $eAccount);
					break;

				case \cash\Cash::SELL_INVOICE:
				case \cash\Cash::SELL_SALE:
			}

			if(count($fecLines) > 0) {
				foreach($fecLines as $fecLine) {
					self::mergeFecLineIntoItemData($items, $fecLine);
				}
			}

			if(count($items) > 0) {
				$nCash++;
			}
			$fecData = array_merge($fecData, $items);

		}

		return [$fecData, $nCash];

	}

	/**
	 * écritures en 108 ou 455 / Caisse
	 */
	public static function generateCashPrivateLines(\cash\Cash $eCash, \cash\Register $eRegister, \Collection $cAccount): array {

		if($eCash['account']->notEmpty()) {

			$eAccount = $cAccount->find(fn($e) => $e['id'] === $eCash['account']['id'])->first();

		} else {

			$eFinancialYear = \account\FinancialYearLib::getFinancialYearForDate($eCash['date'], new \Collection());
			if($eFinancialYear->notEmpty()) {

				if($eFinancialYear['legalCategory'] === \company\CompanySetting::CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL) {

					$eAccount = $cAccount->find(fn($e) => $e['class'] === \account\AccountSetting::FARMER_S_ACCOUNT_CLASS)->first();

				} else {

					$eAccount = $cAccount->find(fn($e) => $e['class'] === \account\AccountSetting::ASSOCIATE_ACCOUNT_CLASS)->first();

				}

			} else {

				$eAccount = new \account\Account();

			}

		}

		return self::generateCashLines($eCash, $eRegister, $eAccount);

	}

	/**
	 * écritures en 512 / Caisse
	 */
	public static function generateCashBankLines(\cash\Cash $eCash, \cash\Register $eRegister, \Collection $cAccount): array {

		// On cherche la contrepartie par ordre de priorité
		if($eCash['sourceBankAccount']->notEmpty()) {

			$eAccount = $cAccount->find(fn($e) => $e['id'] === $eCash['sourceBankAccount']['account']['id'])->first();

		} else if($eCash['sourceCashflow']->notEmpty()) {

			$eAccount = $cAccount->find(fn($e) => $e['id'] === $eCash['sourceCashflow']['account']['id'])->first();

		} else if($eCash['account']->notEmpty()) {

			$eAccount = $cAccount->find(fn($e) => $e['id'] === $eCash['account']['id'])->first();

		} else {

			$eAccount = $cAccount->find(fn($e) => $e['class'] === \account\AccountSetting::BANK_ACCOUNT_CLASS)->first();

		}

		return self::generateCashLines($eCash, $eRegister, $eAccount);

	}

	public static function generateCashLines(\cash\Cash $eCash, \cash\Register $eRegister, \account\Account $eAccount): array {

		$lines = [];

		if($eCash['type'] === \cash\Cash::DEBIT) { // L'argent sort de la caisse

			$counterpartType = \journal\Operation::DEBIT;
			$cashType = \journal\Operation::CREDIT;

		} else {

			$counterpartType = \journal\Operation::CREDIT;
			$cashType = \journal\Operation::DEBIT;

		}

		$document = '';
		$compAuxNum = '';
		$compAuxLib = '';

		$lines[] = self::getFecLine(
			eAccount    : $eAccount,
			date        : $eCash['date'],
			eCode       : new \journal\JournalCode(),
			ecritureLib : $eCash['description'],
			document    : $document,
			documentDate: $eCash['date'],
			amount      : $eCash['amountExcludingVat'],
			type        : $counterpartType,
			payment     : $eRegister['paymentMethod']['name'],
			compAuxNum  : $compAuxNum,
			compAuxLib  : $compAuxLib,
			isSummed    : TRUE,
			origin      : 'register',
		);

		if($eCash['vat'] > 0) {

			$lines[] = self::getFecLine(
				eAccount    : $eAccount['vatAccount'],
				date        : $eCash['date'],
				eCode       : new \journal\JournalCode(),
				ecritureLib : $eCash['description'],
				document    : $document,
				documentDate: $eCash['date'],
				amount      : $eCash['vat'],
				type        : $counterpartType,
				payment     : $eRegister['paymentMethod']['name'],
				compAuxNum  : $compAuxNum,
				compAuxLib  : $compAuxLib,
				isSummed    : TRUE,
				origin      : 'register',
			);

		}

		$lines[] = self::getFecLine(
			eAccount    : $eRegister['account'],
			date        : $eCash['date'],
			eCode       : new \journal\JournalCode(),
			ecritureLib : $eCash['description'],
			document    : $document,
			documentDate: $eCash['date'],
			amount      : $eCash['amountIncludingVat'],
			type        : $cashType,
			payment     : $eRegister['paymentMethod']['name'],
			compAuxNum  : $compAuxNum,
			compAuxLib  : $compAuxLib,
			isSummed    : FALSE,
			origin      : 'register',
		);

		return $lines;

	}

	public static function generateSalesFec(\Collection $cSale, \Collection $cFinancialYear, \Collection $cAccount, \account\Account $eAccountFilter): array {

		$fecData = [];
		$nSale = 0;

		// On ne connaît pas forcément le numéro de compte de banque
		$eAccountBank = $cAccount->find(fn($e) => $e['class'] === \account\AccountSetting::BANK_ACCOUNT_CLASS)->first();

		foreach($cSale as $eSale) {

			$document = $eSale['document'];
			$documentDate = $eSale['deliveredAt'];
			$compAuxLib = ($eSale['customer']['name'] ?? '');
			$compAuxNum = '';

			$items = [];

			$allEntries = self::computeSalePaymentRatio($eSale, $cFinancialYear, $cAccount, $eAccountBank);

			foreach($allEntries as $item) {

				$eAccount = $cAccount->offsetGet($item['account']);

				if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class'])) {

					if($eSale['cPayment']->find(fn($e) => $e['id'] === $item['payment'])->count() > 0) {
						$ePayment = $eSale['cPayment']->find(fn($e) => $e['id'] === $item['payment'])->first();
						$date = $ePayment['paidAt'];
						$payment = $ePayment['methodName'];
					} else {
						$date = $eSale['deliveredAt'];
						$payment = '';
					}

					$fecDataItemPayment = self::getFecLine(
						eAccount    : $eAccount,
						date        : $date,
						eCode       : $eAccount['journalCode'],
						ecritureLib : $document,
						document    : $document,
						documentDate: $documentDate,
						amount      : $item['amount'],
						type        : $item['amount'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
						payment     : $payment,
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
						isSummed    : $eAccountBank['id'] !== $eAccount['id'],
						origin      : 'sale',
					);

					self::mergeFecLineIntoItemData($items, $fecDataItemPayment);

			}

			}

			if(count($items) > 0) {
				$nSale++;
			}
			$fecData = array_merge($fecData, $items);


		}

		return [$fecData, $nSale];

	}

	public static function generateInvoicesFec(\Collection $cInvoice, \Collection $cFinancialYear, \Collection $cAccount, bool $forImport, \account\Account $eAccountFilter = new \account\Account()): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$fecData = [];
		$nInvoices = 0;
		$number = 0;
		foreach($cInvoice as $eInvoice) {

			if($eInvoice['cashflow']->notEmpty()) {
				$referenceDate = $eInvoice['cashflow']['date'];
			} else {
				$referenceDate = $eInvoice['date'];
			}

			$document = $eInvoice['number'];
			$documentDate = $eInvoice['date'];
			$compAuxLib = ($eInvoice['customer']['name'] ?? '');
			$compAuxNum = '';

			$eFinancialYearFound = self::extractFinancialYearByDate($cFinancialYear, $referenceDate);
			$hasVat = ($eFinancialYearFound->empty() or $eFinancialYearFound['hasVat']);

			$items = []; // groupement par accountlabel
			$payment = ($eInvoice['paymentMethod']['name'] ?? '');

			$totalExcludingVat = $eInvoice['priceExcludingVat'];
			$totalVat = $eInvoice['vat'];

			$currentExcludingVat = 0;
			$currentVat = 0;

			$eSaleLast = $eInvoice['cSale']->last();

			foreach($eInvoice['cSale'] as $eSale) {

				$eItemLast = $eSale['cItem']->last();

				foreach($eSale['cItem'] as $eItem) {

					$eAccountDefault = self::getDefaultAccount($eItem['vatRate'], $eAccountVatDefault);

					$eAccount = self::extractAccountFromItem($eItem, $cAccount, $eAccountDefault);

					$amountExcludingVat = $eItem['priceStats'];

					if(round($eItem['vatRate'], 2) !== 0.0) {
						$amountVat = round($amountExcludingVat * $eItem['vatRate'] / 100, 2);
					} else {
						$amountVat = 0.0;
					}

					$currentExcludingVat += $amountExcludingVat;
					$currentVat += $amountVat;

					// Si on n'est pas redevable de la TVA => On enregistre TTC
					if($hasVat === FALSE) {
						$amountExcludingVat += $amountVat;
						$amountVat = 0.0;
					}

					// Fera l'objet d'une autre entrée.
					if($eSale['shippingExcludingVat'] > 0) {
						$amountExcludingVat -= $eSale['shippingExcludingVat'];
						if($eSale['shipping'] != $eSale['shippingExcludingVat']) {
							$amountVat += ($eSale['shipping'] - $eSale['shippingExcludingVat']);
						}
					}

					// On utilise le dernier item de la dernière vente pour réharmoniser les centimes
					if($eSale->is($eSaleLast) and $eItem->is($eItemLast)) {

						// Cts manquants sur la TVA
						if($totalVat !== $currentVat) {
							$amountVat += ($totalVat - $currentVat);
						}

						// Cts manquants sur le HT
						if($totalExcludingVat !== $currentExcludingVat) {
							$amountExcludingVat += ($totalExcludingVat - $currentExcludingVat);
						}

					}

					// Montant HT
					if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class'])) {

						$fecDataExcludingVat = self::getFecLine(
							eAccount    : $eAccount,
							date        : $referenceDate,
							eCode       : $eAccount['journalCode'],
							ecritureLib : $document,
							document    : $document,
							documentDate: $documentDate,
							amount      : $amountExcludingVat,
							type        : $amountExcludingVat > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
							payment     : $payment,
							compAuxNum  : $compAuxNum,
							compAuxLib  : $compAuxLib,
							number      : $forImport ? ++$number : NULL,
							isSummed    : TRUE,
							origin      : 'invoice',
						);

						$numberWithoutVat = $number;
						self::mergeFecLineIntoItemData($items, $fecDataExcludingVat);

					}

					// TVA
					if($hasVat and $amountVat !== 0.0) {

						$eAccountVat = $eAccount['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccountVat['class'])) {

							$fecDataVat = self::getFecLine(
								eAccount    : $eAccountVat,
								date        : $referenceDate,
								eCode       : $eAccount['journalCode'],
								ecritureLib : $document,
								document    : $document,
								documentDate: $documentDate,
								amount      : $amountVat,
								type        : $amountVat > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
								payment     : $payment,
								compAuxNum  : $compAuxNum,
								compAuxLib  : $compAuxLib,
								number      : $forImport ? ++$number : NULL,
								for         : $eAccount['class'],
								isSummed    : TRUE,
								origin      : 'invoice',
							);
							if($forImport) {
								$fecDataVat[self::FEC_COLUMN_NUMBER] .= '-'.$numberWithoutVat;
							}

							self::mergeFecLineIntoItemData($items, $fecDataVat);

						}
					}

				}

				// Si la vente a des frais de port
				if($eSale['shippingExcludingVat'] !== NULL and $eSale['shippingExcludingVat'] > 0) {

					$eAccountShipping = $cAccount->find(fn($e) => (int)$e['class'] === (int)\account\AccountSetting::PRODUCT_SHIPPING_ACCOUNT_CLASS)->first();

					if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccountShipping['class'])) {

						$fecDataShipping = self::getFecLine(
							eAccount    : $eAccountShipping,
							date        : $referenceDate,
							eCode       : $eAccountShipping['journalCode'],
							ecritureLib : $document,
							document    : $document,
							documentDate: $documentDate,
							amount      : $eSale['shippingExcludingVat'],
							type        : $eSale['shippingExcludingVat'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
							payment     : $payment,
							compAuxNum  : $compAuxNum,
							compAuxLib  : $compAuxLib,
							number      : $forImport ? ++$number : NULL,
							isSummed    : TRUE,
							origin      : 'invoice',
						);
						$numberShipping = $number;

						self::mergeFecLineIntoItemData($items, $fecDataShipping);
					}
					// Si les frais de port ont de la TVA
					if($eSale['shippingVatRate'] !== 0.0 and $eSale['shipping'] !== $eSale['shippingExcludingVat']) {

						$eAccountVat = $eAccountShipping['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						$amountVat = $eSale['shipping'] - $eSale['shippingExcludingVat'];

						if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccountVat['class'])) {

							$fecDataVat = self::getFecLine(
								eAccount    : $eAccountVat,
								date        : $referenceDate,
								eCode       : $eAccountShipping['journalCode'],
								ecritureLib : $document,
								document    : $document,
								documentDate: $documentDate,
								amount      : $amountVat,
								type        : $amountVat > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
								payment     : $payment,
								compAuxNum  : $compAuxNum,
								compAuxLib  : $compAuxLib,
								number      : $forImport ? ++$number : NULL,
								isSummed    : TRUE,
								origin      : 'invoice',
							);
							if($forImport) {
								$fecDataVat[self::FEC_COLUMN_NUMBER] .= '-'.$numberShipping;
							}

							self::mergeFecLineIntoItemData($items, $fecDataVat);

						}

					}

				}

			}

			if($eInvoice['cashflow']->notEmpty()) { // Contrepartie en 512 directe si un rapprochement a déjà été réalisé

				$eAccountBank = $eInvoice['cashflow']['account']['account'];
				if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccountBank['class'])) {

					$fecDataBank = self::getFecLine(
						eAccount    : $eAccountBank,
						date        : $eInvoice['cashflow']['date'],
						eCode       : new \journal\JournalCode(),
						ecritureLib : $eInvoice['cashflow']->getMemo(),
						document    : $document,
						documentDate: $documentDate,
						amount      : $eInvoice['cashflow']['amount'],
						type        : $eInvoice['cashflow']['amount'] > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
						payment     : $payment,
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
						number      : $forImport ? ++$number : NULL,
						isSummed    : FALSE,
						origin      : 'invoice',
					);

					self::mergeFecLineIntoItemData($items, $fecDataBank);
				}

				// S'il y a une différence de montant et qu'il faut la régulariser automatiquement
				$difference = round($eInvoice['priceIncludingVat'] - $eInvoice['cashflow']['amount'], 2);

				if($difference !== 0.0 and $eInvoice['accountingDifference'] === \selling\Invoice::AUTOMATIC) {

					if($difference > 0) { // Arrondi défavorable

						$accountLabel = \account\AccountSetting::CHARGES_OTHER_CLASS;
						$type = \journal\Operation::DEBIT;

					} else if($difference < 0) { // Arrondi favorable

						$accountLabel = \account\AccountSetting::PRODUCT_OTHER_CLASS;
						$type = \journal\Operation::CREDIT;

					}

					$eAccountRegul = $cAccount->find(fn($e) => $e['class'] === $accountLabel)->first();
					if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccountRegul['class'])) {

						$fecDataRegul = self::getFecLine(
							eAccount    : $eAccountRegul,
							date        : $referenceDate,
							eCode       : $eAccountBank['journalCode'],
							ecritureLib : $document,
							document    : $document,
							documentDate: $documentDate,
							amount      : abs($difference),
							type        : $type,
							payment     : $payment,
							compAuxNum  : $compAuxNum,
							compAuxLib  : $compAuxLib,
							number      : $forImport ? ++$number : NULL,
							isSummed    : TRUE,
							origin      : 'invoice',
						);

						self::mergeFecLineIntoItemData($items, $fecDataRegul);

					}

				}

			}

			if(count($items) > 0) {
				$nInvoices++;
			}
			$fecData = array_merge($fecData, $items);

		}

		return [$fecData, $nInvoices];

	}

	public static function sortOperations(array $operations): array {

		usort($operations, function($entry1, $entry2) {
			if($entry1[\preaccounting\AccountingLib::FEC_COLUMN_DATE] === $entry2[\preaccounting\AccountingLib::FEC_COLUMN_DATE]) {
				if($entry1[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT] === $entry2[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT]) {
					return 0;
				}
				return strcmp($entry1[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT], $entry2[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT]);
			}
			if($entry1[\preaccounting\AccountingLib::FEC_COLUMN_DATE] < $entry2[\preaccounting\AccountingLib::FEC_COLUMN_DATE]) {
				return -1;
			}
			return 1;
    });

		return $operations;

	}

	public static function unsetExtraColumns(array &$fecLine): array {

		unset($fecLine[self::EXTRA_FEC_COLUMN_ORIGIN]);
		unset($fecLine[self::EXTRA_FEC_COLUMN_IS_SUMMED]);

		return $fecLine;

	}

	public static function computeSalePaymentRatio(\selling\Sale $eSale, \Collection $cFinancialYear, \Collection $cAccount, \account\Account $eAccountPaymentDefault): array {

		$itemsPaymentRatio = [];

		$itemsByPayment = self::computeSaleRatios($eSale, $cFinancialYear, $cAccount);

		// On ventile par paiement
		foreach($eSale['cPayment'] as $ePayment) {

			$paymentRatio = $ePayment['amountIncludingVat'] / $eSale['priceIncludingVat'];

			$totalPayment = 0;

			foreach($itemsByPayment as $item) {

				$item['amount'] = round($item['amount'] * $paymentRatio, 2);
				$item['type'] = 'sale';
				$item['method'] = $ePayment['method']['id'];
				$item['payment'] = $ePayment['id'];
				$itemsPaymentRatio[] = $item;

				$totalPayment += $item['amount'];

			}

			$eAccountPayment = $ePayment['account'] ?? $eAccountPaymentDefault;

			$itemsPaymentRatio[] = [
				'account' => $eAccountPayment['id'],
				'accountReference' => NULL,
				'vatRate' => NULL,
				'amount' => $ePayment['amountIncludingVat'] * -1,
				'type' => 'payment',
				'method' => $ePayment['method']['id'],
				'payment' => $ePayment['id'],
			];

		}

		// TODO Vérification des totaux HT et TVA
		/*$amountExcludingVatTotal = round(array_sum(array_column(array_filter($itemsPaymentRatio, fn($item) => ($item['vatRate'] === NULL and $item['type'] === 'sale')), 'amount')), 2);
		$differenceAmountExcludingVat = $amountExcludingVatTotal - $eSale['priceExcludingVat'];

		$amountVatTotal = round(array_sum(array_column(array_filter($itemsPaymentRatio, fn($item) => ($item['vatRate'] !== NULL and $item['type'] === 'sale')), 'amount')), 2);
		$differenceAmountVat = $amountVatTotal - $eSale['vat'];

		foreach($eSale['cPayment'] as $ePayment) {
			$amountPaymentTotal = round(array_sum(array_column(array_filter($itemsPaymentRatio, fn($item) => ($item['type'] === 'sale' and $item['payment'] === $ePayment['id'])), 'amount')), 2);
			$expectedPaymentTotal = $ePayment['amountIncludingVat'];

			if($differenceAmountVat === 0.0) { // On va répercuter ça n'importe où qui ne soit pas de la TVA

			}

		}*/

		return $itemsPaymentRatio;

	}

	/**
	 * Calcule pour une vente les montants ventilés par
	 * - classe de compte,
	 * - classe de tva,
	 * - lien classe de compte - classe de tva
	 * - taux de TVA
	 */
	public static function computeSaleRatios(\selling\Sale $eSale, \Collection $cFinancialYear, \Collection $cAccount): array {

		$eFinancialYearFound = self::extractFinancialYearByDate($cFinancialYear, $eSale['deliveredAt']);
		$hasVat = ($eFinancialYearFound->empty() or $eFinancialYearFound['hasVat']);

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$items = [];

		foreach($eSale['cItem'] as $eItem) {

			$eAccountDefault = self::getDefaultAccount($eItem['vatRate'], $eAccountVatDefault);

			$eAccount = self::extractAccountFromItem($eItem, $cAccount, $eAccountDefault);

			$amountExcludingVat = $eItem['priceStats'];

			if(round($eItem['vatRate'], 2) !== 0.0) {
				$amountVat = $amountExcludingVat * $eItem['vatRate'] / 100;
			} else {
				$amountVat = 0.0;
			}

			// Si on n'est pas redevable de la TVA => On enregistre TTC
			if($hasVat === FALSE) {
				$amountExcludingVat += $amountVat;
				$amountVat = 0.0;
			}

			// Montant HT
			$key = array_find_key($items, fn($item) => $item['account'] === $eAccount['id'] and $item['vatRate'] === NULL and $item['accountReference'] === NULL);
			if($key === NULL) {
				$items[] = [
					'account' => $eAccount['id'],
					'accountReference' => NULL,
					'vatRate' => NULL,
					'amount' => $amountExcludingVat,
				];
			} else {
				$items[$key]['amount'] += $amountExcludingVat;
			}

			// TVA
			if($hasVat and $amountVat !== 0.0) {

				$eAccountVat = $eAccount['vatAccount'];
				if($eAccountVat->empty()) {
					$eAccountVat = $eAccountVatDefault;
				}

				$key = array_find_key($items, fn($item) => ($item['account'] === $eAccountVat['id'] and $item['accountReference'] === $eAccount['id'] and $eItem['vatRate'] === $item['vatRate']));
				if($key === NULL) {
					$items[] = [
						'account' => $eAccountVat['id'],
						'accountReference' => $eAccount['id'],
						'vatRate' => $eItem['vatRate'],
						'amount' => $amountVat,
					];
				} else {
					$items[$key]['amount'] += $amountVat;
				}

			}

		}

		// Si la vente a des frais de port
		if($eSale['shippingExcludingVat'] !== NULL) {

			$eAccountShipping = $cAccount->find(fn($e) => (int)$e['class'] === (int)\account\AccountSetting::PRODUCT_SHIPPING_ACCOUNT_CLASS)->first();

			$key = array_find_key($items, fn($item) => $item['account'] === $eAccountShipping['id'] and $item['vatRate'] === NULL and $item['accountReference'] === NULL);
			if($key === NULL) {
				$items[] = [
					'account' => $eAccountShipping['id'],
					'accountReference' => NULL,
					'vatRate' => NULL,
					'amount' => $eSale['shippingExcludingVat'],
				];
			} else {
				$items[$key]['amount'] += $eSale['shippingExcludingVat'];
			}

			// Si les frais de port ont de la TVA
			if($eSale['shippingVatRate'] !== 0.0 and $eSale['shipping'] !== $eSale['shippingExcludingVat']) {

				$eAccountVat = $eAccountShipping['vatAccount'];
				if($eAccountVat->empty()) {
					$eAccountVat = $eAccountVatDefault;
				}

				$amountVat = $eSale['shipping'] - $eSale['shippingExcludingVat'];

				$key = array_find_key($items, fn($item) => ($item['account'] === $eAccountVat['id'] and $item['accountReference'] === $eAccountShipping['id'] and $eSale['shippingVatRate'] === $item['vatRate']));
				if($key === NULL) {
					$items[] = [
						'account' => $eAccountVat['id'],
						'accountReference' => $eAccountShipping['id'],
						'vatRate' => $eSale['shippingVatRate'],
						'amount' => $amountVat,
					];
				} else {
					$items[$key]['amount'] += $amountVat;
				}

			}

		}

		// Vérification finale :
		//- le montant HT et le montant de TVA comparés à ceux de la vente
		$totalExcludingVat = round(array_sum(array_column(array_filter($items, fn($item) => $item['vatRate'] === NULL), 'amount')), 2);
		$totalVat = round(array_sum(array_column(array_filter($items, fn($item) => $item['vatRate'] !== NULL), 'amount')), 2);

		$expectedTotalExcludingVat = $eSale['priceExcludingVat'];
		$expectedTotalVat = $eSale['vat'];

		if($totalExcludingVat !== $expectedTotalExcludingVat) {
			$difference = $totalExcludingVat - $expectedTotalExcludingVat;
			$key = array_find_key($items, fn($item) => $item['vatRate'] === NULL);
			$items[$key]['amount'] = $items[$key]['amount'] - $difference;
		}

		if($totalVat !== $expectedTotalVat) {
			$difference = $totalVat - $expectedTotalVat;
			$key = array_find_key($items, fn($item) => $item['vatRate'] !== NULL);
			$items[$key]['amount'] = $items[$key]['amount'] - $difference;
		}

		return $items;

	}

	private static function extractAccountFromItem(\selling\Item $eItem, \Collection $cAccount, \account\Account $eAccountDefault): \account\Account {

		// Account défini dans l'item
		if($eItem['account']->notEmpty() and $cAccount->offsetExists($eItem['account']['id'])) {

			$eAccount = $cAccount->offsetGet($eItem['account']['id']);

		// Fallback sur le produit : cas du private
		} else if(
			$eItem['product']->notEmpty() and
			$eItem['type'] === \selling\Item::PRIVATE and
			$eItem['product']['privateAccount']->notEmpty() and
			$cAccount->offsetExists($eItem['product']['privateAccount']['id'])
		) {

			$eAccount = $cAccount->offsetGet($eItem['product']['privateAccount']['id']);

		// Fallback sur le produit : cas du pro
		} else if(
			$eItem['product']->notEmpty() and
			$eItem['type'] === \selling\Item::PRO and
			$eItem['product']['proAccount']->notEmpty() and
			$cAccount->offsetExists($eItem['product']['proAccount']['id'])
		) {

			$eAccount = $cAccount->offsetGet($eItem['product']['proAccount']['id']);

		// Fallback sur le produit : cas du pro sans account pro mais avec account private
		} else if(
			$eItem['product']->notEmpty() and
			$eItem['type'] === \selling\Item::PRO and
			$eItem['product']['privateAccount']->notEmpty() and
			$cAccount->offsetExists($eItem['product']['privateAccount']['id'])
		) {

			$eAccount = $cAccount->offsetGet($eItem['product']['privateAccount']['id']);

		// On sait pas.
		} else {

			$eAccount = $eAccountDefault;

		}

		return $eAccount;

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

	/**
	 * isSummed : champ utilisé à l'affichage de précompta pour connaître le total par le filtre utilisé.
	 * Doit sommer les montants du point de vue de l'exploitation, donc la somme sera credit - debit
	 * - si on est sur le logiciel de caisse = toutes les contreparties à la caisse
	 * - si on est sur des ventes = toutes les contreparties de la banque (permet de sommer même si on n'a pas de contrepartie en banque)
	 */
	private static function getFecLine(
		\account\Account $eAccount, string $date, \journal\JournalCode $eCode, string $ecritureLib,
		string $document, string $documentDate, float $amount, string $type, string $payment, string $compAuxNum, string $compAuxLib,
		?int $number = NULL, ?string $for = NULL, bool $isSummed = TRUE, string $origin = '',
	): array {

		return [
			$eCode['code'] ?? '',
			$eCode['name'] ?? '',
			$number, // Utilisé pour l'import (pour rattacher la TVA à son écriture d'origine)
			date('Ymd', strtotime($date)),
			$eAccount->empty() ? '' : \account\AccountLabelLib::pad($eAccount['class']),
			$eAccount->empty() ? '' : $eAccount['description'],
			$compAuxNum,
			$compAuxLib,
			$document,
			date('Ymd', strtotime($documentDate)),
			$ecritureLib,
			$type === \journal\Operation::DEBIT ? abs($amount) : 0.0,
			$type === \journal\Operation::CREDIT ? abs($amount) : 0.0,
			'',
			'',
			'',
			$amount,
			'EUR',
			date('Ymd', strtotime($date)),
			$payment,
			$for,
			(int)$isSummed,
			$origin
		];
	}

	private static function mergeFecLineIntoItemData(array &$items, array $fecLine): void {

		$added = FALSE;
		foreach($items as &$item) {
			if(
				$fecLine[self::FEC_COLUMN_ACCOUNT_LABEL] !== '' and // On ne regroupe pas si on n'a pas le numéro de compte
				$item[self::FEC_COLUMN_ACCOUNT_LABEL] === $fecLine[self::FEC_COLUMN_ACCOUNT_LABEL] and
				$item[self::FEC_COLUMN_PAYMENT_METHOD] === $fecLine[self::FEC_COLUMN_PAYMENT_METHOD] and
				$item[self::FEC_COLUMN_OPERATION_NATURE] === $fecLine[self::FEC_COLUMN_OPERATION_NATURE]
			) {
				$item[self::FEC_COLUMN_DEBIT] = round($item[self::FEC_COLUMN_DEBIT] + $fecLine[self::FEC_COLUMN_DEBIT], 2);
				$item[self::FEC_COLUMN_CREDIT] = round($item[self::FEC_COLUMN_CREDIT] + $fecLine[self::FEC_COLUMN_CREDIT], 2);
				$item[self::FEC_COLUMN_DEVISE_AMOUNT] = round($item[self::FEC_COLUMN_DEVISE_AMOUNT] + $fecLine[self::FEC_COLUMN_DEVISE_AMOUNT], 2);

				if($item[self::FEC_COLUMN_DEBIT] > 0 and $item[self::FEC_COLUMN_CREDIT] > 0) {

					if($item[self::FEC_COLUMN_DEBIT] > $item[self::FEC_COLUMN_CREDIT]) {

						$item[self::FEC_COLUMN_DEBIT] -= $item[self::FEC_COLUMN_CREDIT];
						$item[self::FEC_COLUMN_CREDIT] = 0.0;
						$item[self::FEC_COLUMN_DEBIT] = round($item[self::FEC_COLUMN_DEBIT], 2);

					} else {

						$item[self::FEC_COLUMN_CREDIT] -= $item[self::FEC_COLUMN_DEBIT];
						$item[self::FEC_COLUMN_DEBIT] = 0.0;
						$item[self::FEC_COLUMN_CREDIT] = round($item[self::FEC_COLUMN_CREDIT], 2);

					}

				}
				$added = TRUE;
				break;
			}
		}

		if($added === FALSE) {
			$items[] = $fecLine;
		}
	}

	private static function extractFinancialYearByDate(\Collection $cFinancialYear, string $date): \account\FinancialYear {

		if($cFinancialYear->notEmpty()) {

			$eFinancialYear = $cFinancialYear->find(
				fn($e) => $e['startDate'] <= $date and $date <= $e['endDate']
			)->first();

		}

		return $eFinancialYear ?? new \account\FinancialYear();

	}

}

?>
