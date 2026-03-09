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
	const EXTRA_FEC_COLUMN_SORT_DATE = 23; /* Colonne pour trier les écritures */

	public static function generateCashFec(\Collection $cCash, \Collection $cAccount, \Search $search): array {

		$cRegister = $search->get('cRegister');
		$cMethod = $search->get('cMethod');

		$fecData = [];
		$nCash = 0;

		foreach($cCash as &$eCash) {

			$items = [];
			$eRegister = $cRegister->offsetGet($eCash['register']['id']);
			$eRegister['paymentMethod'] = $cMethod->offsetGet($eRegister['paymentMethod']['id']);

			$fecLines = [];

			switch($eCash['source']) {

				case \cash\Cash::BANK_CASHFLOW:
				case \cash\Cash::BANK_MANUAL:
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
					$eCash['invoice']['cSale'] = SaleLib::getByInvoiceForFec($eCash['invoice']);
					$eCash['invoice']['cPayment'] = PaymentLib::getByInvoiceForFec($eCash['invoice']);

					// Simulation de la contrepartie en caisse
					if($eCash['payment']['cashflow']->empty()) {
						self::simulateCashflowForCash($cAccount, $eCash);
					}
					$eCash['sale']['customer'] = $eCash['customer'];

					[$fecLines,] = self::generateInvoicesFec(new \Collection([$eCash['invoice']]), $cAccount, eAccountFilter: new \account\Account(), ePaymentFilter: $eCash['payment']);
					break;

				case \cash\Cash::SELL_SALE:
					$eCash['sale']['cItem'] = \selling\ItemLib::getBySales($eCash['sale']['farm'], new \Collection([$eCash['sale']]))->linearize();
					$eCash['sale']['cPayment'] = PaymentLib::getBySaleForFec($eCash['sale']);

					// Simulation de la contrepartie en caisse
					if($eCash['payment']['cashflow']->empty()) {
						self::simulateCashflowForCash($cAccount, $eCash);
					}
					$eCash['sale']['customer'] = $eCash['customer'];

					[$fecLines, ] = self::generateSalesFec(new \Collection([$eCash['sale']]), $cAccount, eAccountFilter: new \account\Account(), ePaymentFilter: $eCash['payment'], eCash: $eCash);
					break;
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

	private static function simulateCashflowForCash(\Collection $cAccount, \cash\Cash $eCash): void {

		$cAccountRegister = $cAccount->find(fn($e) => $e['id'] === ($eCash['register']['account']['id'] ?? NULL));
		if($cAccountRegister->notEmpty()) {
			$eAccountRegister = $cAccountRegister->first();
		} else {
			$eAccountRegister = new \account\Account();
		}
		$eCash['payment']['cashflow'] = new \bank\Cashflow([
			'account' => new \bank\BankAccount([
				'account' => $eAccountRegister,
			]),
			'amount' => $eCash['amountIncludingVat'],
		]);

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

				if($eFinancialYear['legalCategory'] === \farm\FarmSetting::CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL) {

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
		if($eCash['cashflow']->notEmpty()) {

			$eAccount = $cAccount->find(fn($e) => $e['id'] === $eCash['cashflow']['account']['account']['id'])->first();

		} else if($eCash['account']->notEmpty()) {

			$eAccount = $cAccount->find(fn($e) => $e['id'] === $eCash['account']['id'])->first();

		} else {

			$eAccount = new \account\Account();

		}

		return self::generateCashLines($eCash, $eRegister, $eAccount);

	}

	public static function generateCashLines(\cash\Cash $eCash, \cash\Register $eRegister, \account\Account $eAccount): array {

		$lines = [];

		$eJournalCode = match($eCash['source']) {
			\cash\Cash::SELL_SALE => \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_SELL),
			\cash\Cash::SELL_INVOICE => \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_SELL),
			\cash\Cash::SELL_MANUAL => \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_SELL),
			\cash\Cash::BUY_MANUAL => \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_BUY),
			default => \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_OD),
		};

		if($eCash['type'] === \cash\Cash::DEBIT) { // L'argent sort de la caisse

			$counterpartType = \journal\Operation::DEBIT;
			$cashType = \journal\Operation::CREDIT;

		} else {

			$counterpartType = \journal\Operation::CREDIT;
			$cashType = \journal\Operation::DEBIT;

		}

		$document = $eCash['register']['id'].'-'.$eCash['position'];
		$compAuxNum = '';
		$compAuxLib = '';
		if($eCash['description'] !== NULL) {
			$description = $eCash['description'];
		} else {
			$description = \cash\CashUi::getName($eCash);
		}
		$lines[] = self::getFecLine(
			eAccount    : $eAccount,
			date        : $eCash['date'],
			eCode       : $eJournalCode,
			ecritureLib : $description,
			document    : $document,
			documentDate: $eCash['date'],
			amount      : $eCash['amountIncludingVat'],
			type        : $counterpartType,
			payment     : $eRegister['paymentMethod']['name'],
			compAuxNum  : $compAuxNum,
			compAuxLib  : $compAuxLib,
			isSummed    : TRUE,
			origin      : 'register',
			sortDate    : $eCash['date'],
		);

		if($eCash['vat'] > 0) {

			$lines[] = self::getFecLine(
				eAccount    : $eAccount['vatAccount'],
				date        : $eCash['date'],
				eCode       : $eJournalCode,
				ecritureLib : $eCash['description'] ?? \cash\CashUi::getOperation($eCash['source']),
				document    : $document,
				documentDate: $eCash['date'],
				amount      : $eCash['vat'],
				type        : $counterpartType,
				payment     : $eRegister['paymentMethod']['name'],
				compAuxNum  : $compAuxNum,
				compAuxLib  : $compAuxLib,
				isSummed    : TRUE,
				origin      : 'register',
				sortDate    : $eCash['date'],
			);

		}

		if($eCash['description'] !== NULL) {
			$description = $eCash['description'];
		} else {
			$description = \cash\CashUi::getName($eCash);
		}
		$lines[] = self::getFecLine(
			eAccount    : $eRegister['account'],
			date        : $eCash['date'],
			eCode       : $eJournalCode,
			ecritureLib : $description,
			document    : $document,
			documentDate: $eCash['date'],
			amount      : $eCash['amountIncludingVat'],
			type        : $cashType,
			payment     : $eRegister['paymentMethod']['name'],
			compAuxNum  : $compAuxNum,
			compAuxLib  : $compAuxLib,
			isSummed    : FALSE,
			origin      : 'register',
			sortDate    : $eCash['date'],
		);

		return $lines;

	}

	public static function generateSalesFec(\Collection $cSale, \Collection $cAccount, \account\Account $eAccountFilter, \selling\Payment $ePaymentFilter = new \selling\Payment(), \cash\Cash $eCash = new \cash\Cash(), ?string $counterpart = NULL, \payment\Method $eMethodFilter = new \payment\Method()): array {

		$eJournalCode = \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_SELL);

		$fecData = [];
		$nSale = 0;

		foreach($cSale as $eSale) {

			if($eCash->notEmpty()) {
				if($eCash['description'] !== NULL) {
					$description = $eCash['description'];
				} else {
					$description = \cash\CashUi::getName($eCash);
				}
				$document = $eCash['register']['id'].'-'.$eCash['position'];
			} else {
				$document = $eSale['document'] ?? '';
				$description = $document;
			}
			$documentDate = $eSale['deliveredAt'];
			$compAuxLib = ($eSale['customer']['name'] ?? '');
			$compAuxNum = '';

			$items = [];

			$ratios = self::computeRatios($eSale, $cAccount, $ePaymentFilter, $counterpart, $eMethodFilter);
			$allEntries = array_merge(...array_values($ratios));

			// Exclure les ventes réglées dans les journaux de caisse
			if($eCash->empty()) {
				$paymentIdsToExclude = $eSale['cPayment']->find(fn($e) => ($e['cashStatus'] ?? NULL) === \selling\Payment::VALID)->getIds();
			} else {
				$paymentIdsToExclude = [];
			}

			foreach($allEntries as $item) {

				if(in_array((int)$item['payment'], $paymentIdsToExclude)) {
					continue;
				}

				if($cAccount->offsetExists($item['account'])) {
					$eAccount = $cAccount->offsetGet($item['account']);
				} else {
					$eAccount = new \account\Account();
				}

				if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class'])) {

					if($eSale['cPayment']->contains(fn($e) => ($e['id'] === $item['payment'] and $e['status'] === \selling\Payment::PAID)) > 0) {
						$ePayment = $eSale['cPayment']->find(fn($e) => $e['id'] === $item['payment'] and $e['status'] === \selling\Payment::PAID)->first();
						if($ePayment['paidAt'] !== NULL) {
							$date = $ePayment['paidAt'];
						} else {
							$date = $eSale['deliveredAt'];
						}
						$payment = $ePayment['methodName'];
					} else {
						$date = $eSale['deliveredAt'];
						$payment = '';
					}

					if($counterpart !== NULL and $eAccount->notEmpty() and $eAccount['class'] === $counterpart) {
						$isCounterpart = TRUE;
						$eAccountSelected = clone($eAccount);
						$eAccountSelected['class'] = $counterpart.str_pad($eSale['customer']['document'], 5, '0');
					} else {
						$eAccountSelected = clone($eAccount);
						$isCounterpart = (
							\account\AccountLabelLib::isFromClass($eAccountSelected['class'] ?? '', \account\AccountSetting::FINANCIAL_GENERAL_CLASS) or
							\account\AccountLabelLib::isFromClass($eAccountSelected['class'] ?? '', \account\AccountSetting::THIRD_PARTY_GENERAL_CLASS)
						);
					}
					$fecDataItem = self::getFecLine(
						eAccount    : $eAccountSelected,
						date        : $date,
						eCode       : $eJournalCode,
						ecritureLib : $description,
						document    : $document,
						documentDate: $documentDate,
						amount      : $item['amount'],
						type        : $item['amount'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
						payment     : $payment,
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
						isSummed    : ($isCounterpart === FALSE),
						origin      : $eCash->notEmpty() ? 'register' : 'sale',
						sortDate    : $eSale['deliveredAt'],
					);

					self::mergeFecLineIntoItemData($items, $fecDataItem);

				}

			}

			if(count($items) > 0) {
				$nSale++;
			}
			$fecData = array_merge($fecData, $items);


		}

		return [$fecData, $nSale];

	}

	public static function generateInvoicesFec(\Collection $cInvoice, \Collection $cAccount, \account\Account $eAccountFilter = new \account\Account(), \selling\Payment $ePaymentFilter = new \selling\Payment(), ?string $counterpart = NULL, \payment\Method $eMethodFilter = new \payment\Method()): array {

		$eAccountBank = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::BANK_ACCOUNT_CLASS)->first();
		$eJournalCode = \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_SELL);

		$fecData = [];
		$nInvoices = 0;

		foreach($cInvoice as $eInvoice) {

			$document = $eInvoice['number'] ?? '';
			$documentDate = $eInvoice['date'];
			$compAuxLib = ($eInvoice['customer']['name'] ?? '');
			$compAuxNum = '';

			$items = [];

			$cItems = new \Collection();
			foreach($eInvoice['cSale']->getColumnCollection('cItem') as $itemCollection) {
				$cItems->mergeCollection($itemCollection);
			}
			$eInvoice['cItem'] = $cItems;

			$ratios = self::computeRatios($eInvoice, $cAccount, $ePaymentFilter, $counterpart, $eMethodFilter);
			$allEntries = array_merge(...array_values($ratios));

			foreach($allEntries as $item) {

				if($cAccount->offsetExists($item['account'])) {
					$eAccount = $cAccount->offsetGet($item['account']);
				} else {
					$eAccount = new \account\Account();
				}

				if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class'])) {

					if($eInvoice['cPayment']->find(fn($e) => $e['id'] === $item['payment'])->count() > 0) {
						$ePayment = $eInvoice['cPayment']->find(fn($e) => $e['id'] === $item['payment'])->first();
						$date = $ePayment['paidAt'] ?? $eInvoice['date'];
						$payment = $ePayment['methodName'];
					} else {
						$date = $eInvoice['date'];
						$payment = '';
					}

					if($counterpart !== NULL and $eAccount->notEmpty() and $eAccount['class'] === $counterpart) {
						$isCounterpart = TRUE;
						$eAccountSelected = clone($eAccount);
						$eAccountSelected['class'] = $counterpart.str_pad($eInvoice['customer']['document'], 5, '0');
					} else {
						$eAccountSelected = clone($eAccount);
						$isCounterpart = (
							\account\AccountLabelLib::isFromClass($eAccountSelected['class'] ?? '', \account\AccountSetting::FINANCIAL_GENERAL_CLASS) or
							\account\AccountLabelLib::isFromClass($eAccountSelected['class'] ?? '', \account\AccountSetting::THIRD_PARTY_GENERAL_CLASS)
						);
					}

					$fecDataItemPayment = self::getFecLine(
						eAccount    : $eAccountSelected,
						date        : $date,
						eCode       : $eJournalCode,
						ecritureLib : $document,
						document    : $document,
						documentDate: $documentDate,
						amount      : $item['amount'],
						type        : $item['amount'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
						payment     : $payment,
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
						number      : NULL,
						isSummed    : ($isCounterpart === FALSE),
						origin      : 'invoice',
						sortDate    : $eInvoice['date'],
					);

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

	public static function generatePaymentsFec(\Collection $cPayment, \Collection $cAccount, bool $forImport, \account\Account $eAccountFilter = new \account\Account()): array {

		$eAccountBank = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::BANK_ACCOUNT_CLASS)->first();
		$eJournalCode = \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_SELL);

		$fecData = [];
		$nPayment = 0;
		$number = 0;

		foreach($cPayment as $ePayment) {

			if($ePayment['source'] === \selling\Payment::INVOICE) {

				$document = $ePayment['invoice']['number'] ?? '';

				$cItems = new \Collection();
				foreach($ePayment['invoice']['cSale']->getColumnCollection('cItem') as $itemCollection) {
					$cItems->mergeCollection($itemCollection);
				}
				$ePayment['invoice']['cItem'] = $cItems;

				$eElement = $ePayment['invoice'];

			} else {

				$document = $ePayment['sale']['document'] ?? '';

				$eElement = $ePayment['sale'];

			}
			if($ePayment['paidAt'] !== NULL) {
				$referenceDate = $ePayment['paidAt'];
			} else {
				$referenceDate = match($ePayment['source']) {
					\selling\Payment::INVOICE => $ePayment['date'],
					\selling\Payment::SALE => $ePayment['deliveredAt'],
				};
			}
			$documentDate = $referenceDate;
			$compAuxLib = ($eElement['customer']['name'] ?? '');
			$compAuxNum = '';

			$items = [];

			$ratios = self::computeRatios($eElement, $cAccount, ePaymentFilter: $ePayment);
			$allEntries = array_merge(...array_values($ratios));

			foreach($allEntries as $item) {

				if($cAccount->offsetExists($item['account'])) {
					$eAccount = $cAccount->offsetGet($item['account']);
				} else {
					$eAccount = new \account\Account();
				}

				if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class'])) {

					if($ePayment['cashflow']->notEmpty() and $ePayment['cashflow']['account']['account']->is($eAccount)) {
						$ecritureLib = $ePayment['cashflow']->getMemo();
					} else {
						$ecritureLib = $document;
					}

					$fecDataItemPayment = self::getFecLine(
						eAccount    : $eAccount,
						date        : $referenceDate,
						eCode       : $eJournalCode,
						ecritureLib : $ecritureLib,
						document    : $document,
						documentDate: $documentDate,
						amount      : $item['amount'],
						type        : $item['amount'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
						payment     : $ePayment['methodName'],
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
						number      : $forImport ? ++$number : NULL,
						isSummed    : $eAccountBank['id'] !== ($eAccount['id'] ?? ''),
						origin      : 'invoice',
					);

					if($item['isVat'] === FALSE) {

						// On rattache le numéro de l'écriture (pour l'utiliser dans la TVA)
						$index = array_find_key($ratios['amountsExcludingVat'], fn($ratio) => $ratio['amount'] === $item['amount'] and $ratio['account'] === $item['account'] and $ratio['vatRate'] === $item['vatRate']);
						if($index !== NULL) {
							$ratios['amountsExcludingVat'][$index]['number'] = $number;
						}

					} else if($forImport) {
						
						// Rechercher l'écriture originale
						$index = array_find_key($ratios['amountsVat'], fn($ratio) => $ratio['amount'] === $item['amount'] and $ratio['account'] === $item['account'] and $ratio['vatRate'] === $item['vatRate']);
						$numberWithoutVat = $ratios['amountsExcludingVat'][$index]['number'];

						$fecDataItemPayment[self::FEC_COLUMN_NUMBER] .= '-'.$numberWithoutVat;
						$fecDataItemPayment[self::FEC_COLUMN_OPERATION_NATURE] .= $numberWithoutVat;

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

				// Différence sur ce paiement précisément
				if($ePayment['amountIncludingVat'] !== $ePayment['cashflow']['amount']) {
					$difference = round($ePayment['amountIncludingVat'] - $ePayment['cashflow']['amount'], 2);
				} else if($priceIncludingVat !== $totalPaid) {// Différence au global
					$difference = round($priceIncludingVat - $totalPaid, 2);
				} else {
					$difference = 0.0;
				}

				if($difference !== 0.0 and $ePayment['accountingDifference'] === \selling\Payment::AUTOMATIC) {

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
							eCode       : $eJournalCode,
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
			if($entry1[\preaccounting\AccountingLib::EXTRA_FEC_COLUMN_SORT_DATE] === $entry2[\preaccounting\AccountingLib::EXTRA_FEC_COLUMN_SORT_DATE]) {
				if($entry1[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT] === $entry2[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT]) {
					return 0;
				}
				return strcmp($entry1[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT], $entry2[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT]);
			}
			if($entry1[\preaccounting\AccountingLib::EXTRA_FEC_COLUMN_SORT_DATE] < $entry2[\preaccounting\AccountingLib::EXTRA_FEC_COLUMN_SORT_DATE]) {
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

	/**
	 * isSummed : champ utilisé à l'affichage de précompta pour connaître le total par le filtre utilisé.
	 * Doit sommer les montants du point de vue de l'exploitation, donc la somme sera credit - debit
	 * - si on est sur le logiciel de caisse = toutes les contreparties à la caisse
	 * - si on est sur des ventes = toutes les contreparties de la banque (permet de sommer même si on n'a pas de contrepartie en banque)
	 */
	private static function getFecLine(
		\account\Account $eAccount, string $date, \journal\JournalCode $eCode, string $ecritureLib,
		string $document, string $documentDate, float $amount, string $type, string $payment, string $compAuxNum, string $compAuxLib,
		?int $number = NULL, ?string $for = NULL, bool $isSummed = TRUE, string $origin = '', string $sortDate = ''
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
			$origin,
			$sortDate,
		];
	}

	private static function mergeFecLineIntoItemData(array &$items, array $fecLine): void {

		$added = FALSE;
		if(isset($fecLine[self::FEC_COLUMN_NUMBER]) === FALSE or str_contains($fecLine[self::FEC_COLUMN_NUMBER], '-') === FALSE) {

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
