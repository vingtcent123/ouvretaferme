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

			$eFinancialYearFound = self::extractFinancialYearByDate($cFinancialYear, $eSale['deliveredAt']);
			$hasVat = ($eFinancialYearFound->empty() or $eFinancialYearFound['hasVat']);

			$allEntries = self::computeRatios($eSale, $hasVat, $cAccount);

			foreach($allEntries as $item) {

				$eAccount = $cAccount->offsetGet($item['account']);

				if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class'])) {

					if($eSale['cPayment']->find(fn($e) => $e['id'] === ($item['payment'] and $e['status'] === \selling\Payment::PAID))->count() > 0) {
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

		$eAccountBank = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::BANK_ACCOUNT_CLASS)->first();

		$fecData = [];
		$nInvoices = 0;
		$number = 0;

		foreach($cInvoice as $eInvoice) {

			$document = $eInvoice['number'];
			$documentDate = $eInvoice['date'];
			$compAuxLib = ($eInvoice['customer']['name'] ?? '');
			$compAuxNum = '';

			$items = [];

			$cItems = new \Collection();
			foreach($eInvoice['cSale']->getColumnCollection('cItem') as $itemCollection) {
				$cItems->mergeCollection($itemCollection);
			}
			$eInvoice['cItem'] = $cItems;

			$eFinancialYearFound = self::extractFinancialYearByDate($cFinancialYear, $eInvoice['date']);
			$hasVat = ($eFinancialYearFound->empty() or $eFinancialYearFound['hasVat']);

			$allEntries = self::computeRatios($eInvoice, $hasVat, $cAccount);

			foreach($allEntries as $item) {

				$eAccount = $cAccount->offsetGet($item['account']);

				if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class'])) {

					if($eInvoice['cPayment']->find(fn($e) => $e['id'] === $item['payment'])->count() > 0) {
						$ePayment = $eInvoice['cPayment']->find(fn($e) => $e['id'] === $item['payment'])->first();
						$date = $ePayment['paidAt'] ?? $eInvoice['date'];
						$payment = $ePayment['methodName'];
					} else {
						$date = $eInvoice['date'];
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
						number      : $forImport ? ++$number : NULL,
						isSummed    : $eAccountBank['id'] !== $eAccount['id'],
						origin      : 'invoice',
					);

					if($item['accountReference'] === NULL) {
						$numberWithoutVat = $number;
					} else if($forImport) {
						$fecDataItemPayment[self::FEC_COLUMN_NUMBER] .= '-'.$numberWithoutVat;
					}

					self::mergeFecLineIntoItemData($items, $fecDataItemPayment);

				}

			}

			$fecData = array_merge($fecData, $items);

			if(count($items) > 0) {
				$nInvoices++;
			}

		}

		return [$fecData, $nInvoices];

	}

	public static function generatePaymentsFec(\Collection $cPayment, \Collection $cFinancialYear, \Collection $cAccount, bool $forImport, \account\Account $eAccountFilter = new \account\Account()): array {

		$eAccountBank = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::BANK_ACCOUNT_CLASS)->first();

		$fecData = [];
		$nPayment = 0;
		$number = 0;

		foreach($cPayment as $ePayment) {

			if($ePayment['source'] === \selling\Payment::INVOICE) {

				$document = $ePayment['invoice']['number'];

				$cItems = new \Collection();
				foreach($ePayment['invoice']['cSale']->getColumnCollection('cItem') as $itemCollection) {
					$cItems->mergeCollection($itemCollection);
				}
				$ePayment['invoice']['cItem'] = $cItems;

				$eElement = $ePayment['invoice'];

			} else {

				$document = $ePayment['sale']['document'];

				$eElement = $ePayment['sale'];

			}

			$referenceDate = $ePayment['paidAt'];
			$documentDate = $referenceDate;
			$compAuxLib = ($eElement['customer']['name'] ?? '');
			$compAuxNum = '';

			$items = [];

			$eFinancialYearFound = self::extractFinancialYearByDate($cFinancialYear, $ePayment['paidAt']);
			$hasVat = ($eFinancialYearFound->empty() or $eFinancialYearFound['hasVat']);

			$allEntries = self::computeRatios($eElement, $hasVat, $cAccount, ePaymentFilter: $ePayment);

			foreach($allEntries as $item) {

				$eAccount = $cAccount->offsetGet($item['account']);

				if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class'])) {

					if($ePayment['cashflow']->notEmpty() and $ePayment['cashflow']['account']['account']->is($eAccount)) {
						$ecritureLib = $ePayment['cashflow']->getMemo();
					} else {
						$ecritureLib = $document;
					}

					$fecDataItemPayment = self::getFecLine(
						eAccount    : $eAccount,
						date        : $referenceDate,
						eCode       : $eAccount['journalCode'],
						ecritureLib : $ecritureLib,
						document    : $document,
						documentDate: $documentDate,
						amount      : $item['amount'],
						type        : $item['amount'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
						payment     : $ePayment['methodName'],
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
						number      : $forImport ? ++$number : NULL,
						isSummed    : $eAccountBank['id'] !== $eAccount['id'],
						origin      : 'invoice',
					);

					if($item['accountReference'] === NULL) {
						$numberWithoutVat = $number;
					} else if($forImport) {
						$fecDataItemPayment[self::FEC_COLUMN_NUMBER] .= '-'.$numberWithoutVat;
					}

					self::mergeFecLineIntoItemData($items, $fecDataItemPayment);

				}

			}

			// S'il y a une différence de montant et qu'il faut la régulariser automatiquement
			if($ePayment['cashflow']->notEmpty()) {

				// Récupérer tous les paiements effectués de cette vente ou cette facture
				$totalPaid = \selling\Payment::model()
					->select('amountIncludingVat')
					->whereSale($ePayment['sale'], if: $ePayment['source'] === \selling\Payment::SALE)
					->whereInvoice($ePayment['invoice'], if: $ePayment['source'] === \selling\Payment::INVOICE)
					->whereStatus(\selling\Payment::PAID)
					->getCollection()
					->sum('amountIncludingVat');
				$priceIncludingVat = match($ePayment['source']) {
					\selling\Payment::INVOICE => $ePayment['invoice']['priceIncludingVat'],
					\selling\Payment::SALE => $ePayment['sale']['priceIncludingVat'],
				};

				$difference = round($priceIncludingVat - $totalPaid, 2);

				if($difference !== 0.0 and $ePayment['accountingDifference'] === \selling\Invoice::AUTOMATIC) {

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
							payment     : $ePayment['methodName'],
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

			$fecData = array_merge($fecData, $items);

			if(count($items) > 0) {
				$nPayment++;
			}

		}

		return [$fecData, $nPayment];

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

	private static function computeAccountRatios(\selling\Sale|\selling\Invoice $eElement, \Collection $cAccount, bool $hasVat): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();
		$items = [];

		foreach($eElement['cItem'] as $eItem) {

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

			$keyForTotal = array_find_key($eElement['vatByRate'], fn($vatByRate) => ((float)$vatByRate['vatRate'] === (float)$eItem['vatRate']));
			$totalByVatRateExcludingVat = (float)($eElement['vatByRate'][$keyForTotal]['amount'] - $eElement['vatByRate'][$keyForTotal]['vat']);

			// Montant HT
			if($totalByVatRateExcludingVat !== 0.0) {

				$key = array_find_key($items, fn($item) => ($item['account'] === $eAccount['id'] and $item['accountReference'] === NULL));

				if($key === NULL) {
					$items[] = [
						'account' => $eAccount['id'],
						'accountReference' => NULL,
						'ratio' => $amountExcludingVat / $totalByVatRateExcludingVat,
						'vatRate' => $eItem['vatRate'],
					];
				} else {
					$items[$key]['ratio'] += $amountExcludingVat / $totalByVatRateExcludingVat;
				}

			}

			// TVA
			if($hasVat and $amountVat !== 0.0) {

				$eAccountVat = $eAccount['vatAccount'];
				if($eAccountVat->empty()) {
					$eAccountVat = $eAccountVatDefault;
				}

				$key = array_find_key($items, fn($item) => ($item['account'] === $eAccountVat['id'] and $item['accountReference'] === $eAccount['id']));

				if($key === NULL) {
					$items[] = [
						'account' => $eAccountVat['id'],
						'accountReference' => $eAccount['id'],
						'ratio' => $amountVat / $eElement['vatByRate'][$keyForTotal]['vat'],
						'vatRate' => $eItem['vatRate'],
					];
				} else {
					$items[$key]['ratio'] += $amountVat / $eElement['vatByRate'][$keyForTotal]['vat'];
				}

			}

		}

		// Si la vente a des frais de port
		if($eElement instanceof \selling\Sale) {
			$cSale = new \Collection();
			$cSale->append($eElement);
		} else {
			$cSale = $eElement['cSale'];
		}

		foreach($cSale as $eSale) {

			if($eSale['shippingExcludingVat'] !== NULL) {

				$eAccountShipping = $cAccount->find(fn($e) => (int)$e['class'] === (int)\account\AccountSetting::PRODUCT_SHIPPING_ACCOUNT_CLASS)->first();

				if($hasVat === FALSE) {

					$shippingExcludingVat = $eSale['shipping'];
					$shippingVatRate = 0.0;

				} else {

					$shippingExcludingVat = $eSale['shippingExcludingVat'];
					$shippingVatRate = $eSale['shippingVatRate'];

				}

				$keyForTotal = array_find_key($eElement['vatByRate'], fn($vatByRate) => ($vatByRate['vatRate'] === $eSale['shippingVatRate']));
				$totalByVatRateExcludingVat = $eElement['vatByRate'][$keyForTotal]['amount'] - $eElement['vatByRate'][$keyForTotal]['vat'];

				$key = array_find_key($items, fn($item) => ($item['account'] === $eAccountShipping['id'] and $item['accountReference'] === NULL));

				if($key === NULL) {
					$items[] = [
						'account' => $eAccountShipping['id'],
						'accountReference' => NULL,
						'ratio' => $shippingExcludingVat / $totalByVatRateExcludingVat,
						'vatRate' => $shippingVatRate,
					];
				} else {
					$items[$key]['ratio'] += $shippingExcludingVat / $totalByVatRateExcludingVat;
				}

				// Si les frais de port ont de la TVA
				if($hasVat and $eSale['shippingVatRate'] !== 0.0 and $eSale['shipping'] !== $eSale['shippingExcludingVat']) {

					$eAccountVat = $eAccountShipping['vatAccount'];
					if($eAccountVat->empty()) {
						$eAccountVat = $eAccountVatDefault;
					}

					$amountVat = $eSale['shipping'] - $eSale['shippingExcludingVat'];

					$key = array_find_key($items, fn($item) => ($item['account'] === $eAccountVat['id'] and $item['accountReference'] === $eAccountShipping['id']));

					if($key === NULL) {
						$items[] = [
							'account' => $eAccountVat['id'],
							'accountReference' => $eAccountShipping['id'],
							'ratio' => $amountVat / $eElement['vatByRate'][$keyForTotal]['vat'],
							'vatRate' => $eSale['shippingVatRate'],
						];
					} else {
						$items[$key]['ratio'] += $amountVat / $eElement['vatByRate'][$keyForTotal]['vat'];
					}

				}
			}
		}

		return $items;
	}

	public static function computeRatios(\selling\Sale|\selling\Invoice $eElement, bool $hasVat, \Collection $cAccount, \selling\Payment $ePaymentFilter = new \selling\Payment()): array {

		// Construire le ratio par classe de compte
		$amountRatios = self::computeAccountRatios($eElement, $cAccount, $hasVat);

		// Niveau 1 : éclater par moyen de paiement
		$items = [];

		if($ePaymentFilter->notEmpty()) {
			$cPayment = new \Collection([$ePaymentFilter]);
		} else {
			$cPayment = $eElement['cPayment'];
		}

		foreach($cPayment as $ePayment) {

			if($ePayment['status'] === \selling\Payment::PAID) {

				$paymentRatio = $ePayment['amountIncludingVat'] / $eElement['priceIncludingVat'];

			} else { // On simule une vente payée

				$ePayment['amountIncludingVat'] = $eElement['priceIncludingVat'];
				$paymentRatio = 1;

			}

			// Niveau 2 : Éclater par taux de TVA
			if($hasVat === FALSE) {

				$vatByRates = [['vatRate' => 0.0, 'amount' => $ePayment['amountIncludingVat'], 'vat' => 0]];

			} else {

				$vatByRates = [];

				foreach($eElement['vatByRate'] as $vatByRate) {
					$vatByRates[] = [
						'amountWithoutRatio' => $vatByRate['amount'],
						'amount' => round($vatByRate['amount'] * round($paymentRatio, 2), 2),
						'vatRate' => $vatByRate['vatRate']
					];
				}

				// S'il y a un écart dans les montants ventilés par TVA => corriger maintenant
				$totalByVat = array_sum(array_column($vatByRates, 'amount'));

				if($totalByVat !== $ePayment['amountIncludingVat']) {

					$difference = $totalByVat - $ePayment['amountIncludingVat'];

					// On tri le tabeau par taux de TVA croissant
					usort($vatByRates, fn($vatByRate1, $vatByRate2) => $vatByRate1['vatRate'] <=> $vatByRate2['vatRate']);

					// On rééquilibre la différence en l'appliquant au plus petit taux qui ne tombe pas juste
					foreach($vatByRates as &$vatByRate) {
						if(is_int($vatByRate['amountWithoutRatio'] * $paymentRatio) === FALSE) {
							$vatByRate['amount'] = round($vatByRate['amount'] - $difference, 2);
							break;
						}
					}
				}

			}

			foreach($vatByRates as $vatByRate) {

				if($hasVat) {

					// TVA
					$amountVat = round($vatByRate['amount'] * $vatByRate['vatRate'] / 100, 2);
					$amountExcludingVat = $vatByRate['amount'] - $amountVat;

				} else {

					$amountExcludingVat = $eElement['priceExcludingVat'];

				}
				// HT
				$ratioItems = [];
				foreach($amountRatios as $amountRatio) {

					// On ne prend que les ratio non TVA
					if($amountRatio['accountReference'] !== NULL or $amountRatio['vatRate'] !== $vatByRate['vatRate']) {
						continue;
					}

					$ratioItems[] = [
						'payment' => $ePayment['status'] === \selling\Payment::PAID ? $ePayment['id'] : '',
						'vatRate' => $vatByRate['vatRate'],
						'account' => $amountRatio['account'],
						'accountReference' => $amountRatio['accountReference'],
						'ratio' => $amountRatio['ratio'],
						'amount' => round($amountExcludingVat * $amountRatio['ratio'], 2),
						'method' => $ePayment['method']['id'],
					];
				}

				// Vérification des écarts
				$totalRatio = array_sum(array_column($ratioItems, 'amount'));

				if($totalRatio !== $amountExcludingVat) {

					$difference = $totalRatio - $amountExcludingVat;

					// Tri par numéro de compte référencé
					usort($ratioItems, fn($ratio1, $ratio2) => $ratio1['accountReference'] <=> $ratio2['accountReference']);

					foreach($ratioItems as &$ratioItem) {
						if(is_int($amountExcludingVat * $ratioItem['ratio']) === FALSE) {
							$ratioItem['amount'] = round($ratioItem['amount'] - $difference, 2);
							break;
						}
					}
				}

				$items = array_merge($items, $ratioItems);

				if($hasVat) {

					$ratioItems = [];
					foreach($amountRatios as $amountRatio) {

						// On ne prend que les ratio de TVA
						if($amountRatio['accountReference'] === NULL or $amountRatio['vatRate'] !== $vatByRate['vatRate']) {
							continue;
						}

						$ratioItems[] = [
							'payment' => $ePayment['status'] === \selling\Payment::PAID ? $ePayment['id'] : '',
							'vatRate' => $vatByRate['vatRate'],
							'account' => $amountRatio['account'],
							'accountReference' => $amountRatio['accountReference'],
							'ratio' => $amountRatio['ratio'],
							'amount' => round($amountVat * $amountRatio['ratio'], 2),
							'method' => $ePayment['method']['id'],
						];

					}

					// Vérification des écarts
					$totalRatio = array_sum(array_column($ratioItems, 'amount'));

					if($totalRatio !== $amountVat) {

						$difference = $totalRatio - $amountVat;

						// Tri par numéro de compte référencé
						usort($ratioItems, fn($ratio1, $ratio2) => $ratio1['accountReference'] <=> $ratio2['accountReference']);

						foreach($ratioItems as &$ratioItem) {
							if(is_int($amountVat * $ratioItem['ratio']) === FALSE) {
								$ratioItem['amount'] -= $difference;
								break;
							}
						}
					}

					$items = array_merge($items, $ratioItems);

				}

			}

			// Ajout de l'écriture de banque
			if($ePayment['status'] === \selling\Payment::PAID and $ePayment['cashflow']->notEmpty()) {

				$items[] = [
					'account' => $ePayment['cashflow']['account']['account']['id'],
					'accountReference' => NULL,
					'vatRate' => NULL,
					'amount' => $ePayment['cashflow']['amount'] * -1,
					'type' => 'payment',
					'method' => $ePayment['method']['id'],
					'payment' => $ePayment['id'],
				];

			}
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
