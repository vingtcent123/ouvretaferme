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

		$cFinancialYear = \account\FinancialYearLib::getAll();

		$fecData = [];
		$nCash = 0;

		foreach($cCash as &$eCash) {

			$items = [];
			$eRegister = $cRegister->offsetGet($eCash['register']['id']);
			$eRegister['paymentMethod'] = $cMethod->offsetGet($eRegister['paymentMethod']['id']);
			$eFinancialYear = \account\FinancialYearLib::getFinancialYearForDate($eCash['date'], $cFinancialYear);

			if($eFinancialYear->isCashReceipts()) {

				$eRegister['account'] = $cAccount->find(fn($e) => \account\AccountLabelLib::isFromClass($e['class'], \account\AccountSetting::CASH_SUB_ACCOUNT_CLASS), limit: 1);

			}

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

					$eCash['sale']['customer'] = $eCash['customer'];

					[$fecLines,] = self::generateInvoicesFec(new \Collection([$eCash['invoice']]), $cAccount, eAccountFilter: new \account\Account(), ePaymentFilter: $eCash['payment'], eCash: $eCash);
					break;

				case \cash\Cash::SELL_SALE:
					$eCash['sale']['cItem'] = \selling\ItemLib::getBySales($eCash['sale']['farm'], new \Collection([$eCash['sale']]))->linearize();
					$eCash['sale']['cPayment'] = PaymentLib::getBySaleForFec($eCash['sale']);

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

		$cFinancialYear = \account\FinancialYearLib::getAll();
		$eFinancialYear = \account\FinancialYearLib::getFinancialYearForDate($eCash['date'], $cFinancialYear);

		if($eFinancialYear->isCashReceipts()) {

			$eAccount = $cAccount->find(fn($e) => \account\AccountLabelLib::isFromClass($e['class'], \account\AccountSetting::BANK_ACCOUNT_CLASS), limit: 1);

		} else {

			// On cherche la contrepartie par ordre de priorité
			if($eCash['cashflow']->notEmpty()) {

				$eAccount = $cAccount->find(fn($e) => $e['id'] === $eCash['cashflow']['account']['account']['id'], limit: 1);

			} else if($eCash['account']->notEmpty()) {

				$eAccount = $cAccount->find(fn($e) => $e['id'] === $eCash['account']['id'], limit: 1);

			} else {

				$eAccount = new \account\Account();

			}

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
			default => \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_CASH),
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
		$cFinancialYear = \account\FinancialYearLib::getAll();

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

			$eFinancialYear = \account\FinancialYearLib::getFinancialYearForDate($eSale['deliveredAt'], $cFinancialYear);
			$ratios = new RatioLib($eSale, $cAccount, ($eFinancialYear->notEmpty() and $eFinancialYear->isCashReceipts()))
				->filter($ePaymentFilter, $eMethodFilter);
			$items = self::generateFecData($ratios, $eSale['deliveredAt'], $cAccount, $eAccountFilter, $eJournalCode,
				$document, $eSale['deliveredAt'], ($eInvoice['customer']['name'] ?? ''), $counterpart, $description, $eCash, 'sale');

			if(count($items) > 0) {
				$nSale++;
			}
			$fecData = array_merge($fecData, $items);

		}

		return [$fecData, $nSale];

	}

	public static function generateInvoicesFec(
		\Collection $cInvoice,
		\Collection $cAccount,
		\account\Account $eAccountFilter = new \account\Account(),
		\selling\Payment $ePaymentFilter = new \selling\Payment(),
		?string $counterpart = NULL,
		\payment\Method $eMethodFilter = new \payment\Method(),
		\cash\Cash $eCash = new \cash\Cash()
	): array {

		$eJournalCode = \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_SELL);

		$cFinancialYear = \account\FinancialYearLib::getAll();

		$fecData = [];
		$nInvoices = 0;

		foreach($cInvoice as $eInvoice) {

			$cItems = new \Collection();
			foreach($eInvoice['cSale']->getColumnCollection('cItem') as $itemCollection) {
				$cItems->mergeCollection($itemCollection);
			}
			$eInvoice['cItem'] = $cItems;

			$eFinancialYear = \account\FinancialYearLib::getFinancialYearForDate($eInvoice['date'], $cFinancialYear);
			$ratios = new RatioLib($eInvoice, $cAccount, ($eFinancialYear->notEmpty() and $eFinancialYear->isCashReceipts()))
				->filter($ePaymentFilter, $eMethodFilter);
			$items = self::generateFecData($ratios, $eInvoice['date'], $cAccount, $eAccountFilter, $eJournalCode,
				$eInvoice['number'] ?? '', $eInvoice['date'], ($eInvoice['customer']['name'] ?? ''), $counterpart, $eInvoice['number'] ?? '', $eCash, 'invoice');

			$fecData = array_merge($fecData, $items);

			if(count($items) > 0) {
				$nInvoices++;
			}

		}

		return [$fecData, $nInvoices];

	}

	public static function generatePaymentsFec(\Collection $cPayment, \Collection $cAccount): array {

		$eJournalCode = \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_SELL);
		$cFinancialYear = \account\FinancialYearLib::getAll();

		$fecData = [];
		$nPayment = 0;

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

			$eFinancialYear = \account\FinancialYearLib::getFinancialYearForDate($referenceDate, $cFinancialYear);
			$ratios = new RatioLib($eElement, $cAccount, ($eFinancialYear->notEmpty() and $eFinancialYear->isCashReceipts()))->getByVat();

			$items = self::generateFecData($ratios, $referenceDate, $cAccount, new \account\Account(), $eJournalCode,
				$document, $documentDate, $compAuxLib, NULL, $document, new \cash\Cash(), $ePayment['source'], TRUE);

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
						compAuxNum  : '',
						compAuxLib  : $compAuxLib,
						origin      : $ePayment['source'],
					);

					self::mergeFecLineIntoItemData($items, $fecDataRegul);

				}

			}

			$fecData = array_merge($fecData, $items);

			if(count($items) > 0) {
				$nPayment++;
			}

		}

		return [$fecData, $nPayment];

	}

	private static function generateFecData(array $ratios, string $date, \Collection $cAccount, \account\Account $eAccountFilter, \journal\JournalCode $eJournalCode, string $document, string $documentDate, string $compAuxLib, ?string $counterpart, string $description, \cash\Cash $eCash, ?string $origin = NULL, bool $forImport = FALSE) : array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT, limit: 1);
		$eAccountWaiting = $cAccount->find(fn($e) => $e['class'] === (string)\account\AccountSetting::WAITING_ACCOUNT_CLASS, limit: 1);
		$compAuxNum = '';

		$number = 0;

		if($eCash->notEmpty()) {

			$origin = 'register';

			if($eCash['description'] !== NULL) {
				$description = $eCash['description'];
			} else {
				$description = \cash\CashUi::getName($eCash);
			}
		}

		$items = [];

		foreach($ratios as $vatRate => $ratio) {

			foreach($ratio['splitByPayments'] as $paymentId => $ratioByPayment) {

				$ePayment = $ratioByPayment['payment'];

				if($eCash->notEmpty() and $ePayment['cash']->is($eCash) === FALSE) {
					continue;
				}

				$paymentName = $ePayment->notEmpty() ? $ePayment['methodName'] : '';

				if($ePayment->notEmpty() and $ePayment['status'] === \selling\Payment::PAID) {
					$date = $ePayment['paidAt'];
				}

				foreach($ratioByPayment['splitByAccounts'] as $accountId => $splitByAccount) {

					if($splitByAccount['amountExcludingVat'] === 0.0) {
						continue;
					}

					if($cAccount->offsetExists($accountId)) {
						$eAccount = $cAccount->offsetGet($accountId);
					} else {
						$eAccount = new \account\Account();
					}

					if($eAccountFilter->empty() or \account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class'])) {

						$fecDataItem = self::getFecLine(
							eAccount    : $eAccount,
							date        : $date,
							eCode       : $eJournalCode,
							ecritureLib : $description,
							document    : $document,
							documentDate: $documentDate,
							amount      : $splitByAccount['amountExcludingVat'],
							type        : $splitByAccount['amountExcludingVat'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
							payment     : $paymentName,
							compAuxNum  : $compAuxNum,
							compAuxLib  : $compAuxLib,
							number      : $forImport ? ++$number : NULL,
							origin      : $origin,
							sortDate    : $date,
						);

						$lastNumber = $number;

						self::mergeFecLineIntoItemData($items, $fecDataItem);

						if(empty($vatRate) === FALSE) {

							if($eAccount->notEmpty() and $eAccount['vatAccount']->notEmpty()) {
								$eAccountVat = $eAccount['vatAccount'];
							} else {
								$eAccountVat = $eAccountVatDefault;
							}

							$fecDataItem = self::getFecLine(
								eAccount    : $eAccountVat,
								date        : $date,
								eCode       : $eJournalCode,
								ecritureLib : $description,
								document    : $document,
								documentDate: $documentDate,
								amount      : $splitByAccount['vat'],
								type        : $splitByAccount['vat'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
								payment     : $paymentName,
								compAuxNum  : $compAuxNum,
								compAuxLib  : $compAuxLib,
								number      : $forImport ? ++$number : NULL,
								origin      : $origin,
								sortDate    : $date,
							);

							if($forImport) {

								$fecDataItem[self::FEC_COLUMN_NUMBER] .= '-'.$lastNumber;
								$fecDataItem[self::FEC_COLUMN_OPERATION_NATURE] .= $lastNumber;

							}

							self::mergeFecLineIntoItemData($items, $fecDataItem);

						}

					}

				}

				// rajouter counterpart
				if($ePayment->notEmpty() and $ePayment['status'] === \selling\Payment::PAID) {

					if($ePayment['cashflow']->notEmpty()) {

						if($ePayment['cashflow']['account']['account']->notEmpty()) {
							$eAccountCounterpart = $cAccount->find(fn($e) => $e['id'] === $ePayment['cashflow']['account']['account']['id'], limit: 1);
						} else {
							$eAccountCounterpart = new \account\Account();
						}

						$amount = $ePayment['cashflow']['amount'];

					} else if($eCash->notEmpty()) {

						if($eCash['register']['account']->notEmpty()) {
							$eAccountCounterpart = $cAccount->find(fn($e) => $e['id'] === $eCash['register']['account']['id'], limit: 1);
						} else {
							$eAccountCounterpart = new \account\Account();
						}

						$amount = $eCash['amountIncludingVat'];

					} else {

						// Payé mais on ne sait pas où mettre => compte attente client
						$eAccountCounterpart = $eAccountWaiting;
						$amount = $ePayment['amountIncludingVat'];

					}

					$fecDataItem = self::getFecLine(
						eAccount    : $eAccountCounterpart->notEmpty() ? $eAccountCounterpart : $eAccountWaiting,
						date        : $date,
						eCode       : $eJournalCode,
						ecritureLib : $description,
						document    : $document,
						documentDate: $documentDate,
						amount      : $amount,
						type        : $amount > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
						payment     : $paymentName,
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
						number      : $forImport ? ++$number : NULL,
						isSummed    : FALSE,
						origin      : $origin,
						sortDate    : $date,
					);

					self::mergeFecLineIntoItemData($items, $fecDataItem);

				} else if($ratioByPayment['amountIncludingVat'] !== 0.0) {

					if($counterpart !== NULL) {
						$eAccountCounterpart = $cAccount->find(fn($e) => $e['class'] === $counterpart)->first();
					} else {
						$eAccountCounterpart = $eAccountWaiting;
					}

					$fecDataItem = self::getFecLine(
						eAccount    : $eAccountCounterpart->notEmpty() ? $eAccountCounterpart : $eAccountWaiting,
						date        : $date,
						eCode       : $eJournalCode,
						ecritureLib : $description,
						document    : $document,
						documentDate: $documentDate,
						amount      : $ratioByPayment['amountIncludingVat'],
						type        : $ratioByPayment['amountIncludingVat'] > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
						payment     : $paymentName,
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
						number      : $forImport ? ++$number : NULL,
						isSummed    : FALSE,
						origin      : $origin,
						sortDate    : $date,
					);

					self::mergeFecLineIntoItemData($items, $fecDataItem);
				}
			}
		}

		return $items;
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
		unset($fecLine[self::EXTRA_FEC_COLUMN_SORT_DATE]);

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

		if(empty($fecLine)) {
			return;
		}

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

}

?>
