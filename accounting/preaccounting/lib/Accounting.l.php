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

	public static function generateFec(\farm\Farm $eFarm, string $from, string $to, \Collection $cFinancialYear, bool $forImport): array {

		$search = new \Search(['from' => $from, 'to' => $to]);
		$cAccount = \account\AccountLib::getAll();

		$contentFec = self::extractInvoice($eFarm, $search, $cFinancialYear, $cAccount, forImport: $forImport);

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

		foreach($contentFec as &$lineFec) {
			foreach([self::FEC_COLUMN_DEBIT, self::FEC_COLUMN_CREDIT, self::FEC_COLUMN_DEVISE_AMOUNT] as $column) {
				$lineFec[$column] = \util\TextUi::csvNumber($lineFec[$column]);
			}
		}

		return array_merge([\account\FecLib::getHeader()], $contentFec);

	}

		public static function generateSalesFec(\Collection $cSale, \Collection $cFinancialYear, \Collection $cAccount, \Search $search): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();

		$eAccountFilter = $search->get('account');

		$fecData = [];
		$nSale = 0;
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

			if($eSale['cPayment']->empty()) { // Pas de moyen de paiement => On fake
				$payments = [0 => ['label' => '', 'rate' => 100.0]];
			} else {
				$payments = self::explodePaymentsRatio($eSale);
			}

			foreach($eSale['cItem'] as $eItem) {

				$eAccountDefault = self::getDefaultAccount($eItem['vatRate'], $eAccountVatDefault);

				$eAccount = self::extractAccountFromItem($eItem, $cAccount, $eAccountDefault);

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
					if(
						$eAccountFilter->empty() or
						$eAccountFilter->is($eAccount) or
						\account\AccountLabelLib::isFromClass($eAccountFilter['class'], $eAccount['class']) or
						\account\AccountLabelLib::isFromClass($eAccount['class'], $eAccountFilter['class'])
					) {

						$fecDataExcludingVat = self::getFecLine(
							eAccount    : $eAccount,
							date        : $eSale['deliveredAt'],
							eCode       : $eAccount['journalCode'],
							ecritureLib : $document,
							document    : $document,
							documentDate: $documentDate,
							amount      : $amountExcludingVat,
							type        : $amountExcludingVat > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
							payment     : $payment['label'] ?? '',
							compAuxNum  : $compAuxNum,
							compAuxLib  : $compAuxLib,
						);

						self::mergeFecLineIntoItemData($items, $fecDataExcludingVat);

					}

					// TVA
					if($hasVat and $amountVat !== 0.0) {

						$eAccountVat = $eAccount['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						if($eAccountFilter->empty() or $eAccountFilter->is($eAccountVat)) {
							$fecDataVat = self::getFecLine(
								eAccount    : $eAccountVat,
								date        : $eSale['deliveredAt'],
								eCode       : $eAccount['journalCode'],
								ecritureLib : $document,
								document    : $document,
								documentDate: $documentDate,
								amount      : $amountVat,
								type        : $amountExcludingVat > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
								payment     : $payment['label'],
								compAuxNum  : $compAuxNum,
								compAuxLib  : $compAuxLib,
							);

							self::mergeFecLineIntoItemData($items, $fecDataVat);

						}
					}

				}

			}

				// Si la vente a des frais de port
				if($eSale['shippingExcludingVat'] !== NULL and $eSale['shippingExcludingVat'] > 0) {

					$eAccountShipping = $cAccount->find(fn($e) => (int)$e['class'] === (int)\account\AccountSetting::PRODUCT_SHIPPING_ACCOUNT_CLASS)->first();

					$fecDataShipping = self::getFecLine(
						eAccount    : $eAccountShipping,
						date        : $eSale['deliveredAt'],
						eCode       : $eAccountShipping['journalCode'],
						ecritureLib : $document,
						document    : $document,
						documentDate: $documentDate,
						amount      : $eSale['shippingExcludingVat'],
						type        : $eSale['shippingExcludingVat'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
						payment     : first($payments)['label'] ?? '',
						compAuxNum  : $compAuxNum,
						compAuxLib  : $compAuxLib,
					);

					self::mergeFecLineIntoItemData($items, $fecDataShipping);

					// Si les frais de port ont de la TVA
					if($eSale['shippingVatRate'] !== 0.0 and $eSale['shipping'] !== $eSale['shippingExcludingVat']) {

						$eAccountVat = $eAccountShipping['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						$amountVat = $eSale['shipping'] - $eSale['shippingExcludingVat'];

						$fecDataVat = self::getFecLine(
							eAccount    : $eAccountVat,
							date        : $eSale['deliveredAt'],
							eCode       : $eAccountShipping['journalCode'],
							ecritureLib : $document,
							document    : $document,
							documentDate: $documentDate,
							amount      : $amountVat,
							type        : $amountVat > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
							payment     : first($payments)['label'] ?? '',
							compAuxNum  : $compAuxNum,
							compAuxLib  : $compAuxLib,
						);

						self::mergeFecLineIntoItemData($items, $fecDataVat);
					}

				}



			if(count($items) > 0) {
				$nSale++;
			}
			$fecData = array_merge($fecData, $items);

		}

		return [$fecData, $nSale];

	}

	/**
	 * Extrait les données FEC de toutes les ventes affectées à une facture
	 * Caractéristique : 1 facture = 1 moyen de paiement
	 *
	 */
	public static function extractInvoice(\farm\Farm $eFarm, \Search $search, \Collection $cFinancialYear, \Collection $cAccount, bool $forImport): array {

		$cInvoice = self::getInvoices($eFarm, $search, $forImport);

		return self::generateInvoiceFec($cInvoice, $cFinancialYear, $cAccount, $forImport);

	}

	public static function applyInvoiceFilter(\farm\Farm $eFarm, \Search $search, bool $forImport): \selling\InvoiceModel {

		$dateCondition = \selling\Invoice::model()->format($search->get('from')).' AND '.\selling\Invoice::model()->format($search->get('to'));

		if($forImport) {
			\selling\Invoice::model()
				->whereCashflow('!=', NULL)
				->whereAccountingHash(NULL)
				->whereReadyForAccounting(TRUE)
				->where('m3.date BETWEEN '.$dateCondition, if: $search->get('from') and $search->get('to'))
			;
		}
		return \selling\Invoice::model()
			->join(\selling\Customer::model(), 'm1.customer = m2.id')
			->join(\bank\Cashflow::model(), 'm1.cashflow = m3.id', 'LEFT')
			->where('m1.status NOT IN ("'.\selling\Invoice::DRAFT.'", "'.\selling\Invoice::CANCELED.'")')
			->where('m1.paymentStatus IS NULL OR m1.paymentStatus != "'.\selling\Invoice::NEVER_PAID.'"')
			->where('m2.type = '.\selling\Customer::model()->format($search->get('type')), if: $search->get('type'))
			->where(fn() => 'm2.id = '.$search->get('customer')['id'], if: $search->has('customer') and $search->get('customer')->notEmpty())
			->where('m1.farm = '.$eFarm['id'])
			->whereAccountingDifference('!=', NULL, if: $search->get('accountingDifference') === TRUE)
			->whereAccountingDifference('=', NULL, if: $search->get('accountingDifference') === FALSE)
			->where('m1.date BETWEEN '.$dateCondition, if: $search->get('from') and $search->get('to'))
		;

	}

	public static function countInvoices(\farm\Farm $eFarm, \Search $search) {

		return self::applyInvoiceFilter($eFarm, $search, TRUE)
			->count();

	}

	public static function getInvoices(\farm\Farm $eFarm, \Search $search, bool $forImport = FALSE) {

		return self::applyInvoiceFilter($eFarm, $search, $forImport)
			->select([
				'id', 'date', 'name', 'document', 'farm',
				'priceExcludingVat', 'priceIncludingVat', 'vat', 'taxes', 'hasVat',
				'customer' => [
					'id', 'name', 'type', 'destination',
					'thirdParty' => \account\ThirdParty::model()
						->select('id')
						->delegateElement('customer')

				],
				'cashflow' => \bank\Cashflow::getSelection() + ['account' => \bank\BankAccount::getSelection()],
				'accountingDifference', 'readyForAccounting', 'accountingHash',
				'paymentMethod' => ['name'],
				'cSale' => \selling\Sale::model()
					->select([
						'id', 'shipping', 'shippingExcludingVat', 'shippingVatRate',
						'cItem' => \selling\Item::model()
							->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
							->delegateCollection('sale')
					])
					->delegateCollection('invoice'),
			])
			->getCollection();

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

	public static function generateInvoiceFec(\Collection $cInvoice, \Collection $cFinancialYear, \Collection $cAccount, bool $forImport): array {

		$eAccountVatDefault = $cAccount->find(fn($eAccount) => $eAccount['class'] === \account\AccountSetting::VAT_SELL_CLASS_ACCOUNT)->first();
		$eAccountBank = $cAccount->find(fn($e) => (int)$e['class'] === (int)\account\AccountSetting::BANK_ACCOUNT_CLASS)->first();

		$fecData = [];
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

			$hasVat = TRUE;
			if($cFinancialYear->notEmpty()) {
				$eFinancialYear = $cFinancialYear->find(
					fn($e) => $e['startDate'] <= $referenceDate and $referenceDate <= $e['endDate']
				)->first();
				if($eFinancialYear and $eFinancialYear->notEmpty() and $eFinancialYear['hasVat'] === FALSE) {
					$hasVat = FALSE;
				}
			}

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
					);

					$numberWithoutVat = $number;
					self::mergeFecLineIntoItemData($items, $fecDataExcludingVat);

					// TVA
					if($hasVat and $amountVat !== 0.0) {

						$eAccountVat = $eAccount['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

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
						);
						if($forImport) {
							$fecDataVat[self::FEC_COLUMN_NUMBER] .= '-'.$numberWithoutVat;
						}

						self::mergeFecLineIntoItemData($items, $fecDataVat);

					}

				}

				// Si la vente a des frais de port
				if($eSale['shippingExcludingVat'] !== NULL and $eSale['shippingExcludingVat'] > 0) {

					$eAccountShipping = $cAccount->find(fn($e) => (int)$e['class'] === (int)\account\AccountSetting::PRODUCT_SHIPPING_ACCOUNT_CLASS)->first();

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
					);
					$numberShipping = $number;

					self::mergeFecLineIntoItemData($items, $fecDataShipping);

					// Si les frais de port ont de la TVA
					if($eSale['shippingVatRate'] !== 0.0 and $eSale['shipping'] !== $eSale['shippingExcludingVat']) {

						$eAccountVat = $eAccountShipping['vatAccount'];
						if($eAccountVat->empty()) {
							$eAccountVat = $eAccountVatDefault;
						}

						$amountVat = $eSale['shipping'] - $eSale['shippingExcludingVat'];

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
						);
						if($forImport) {
							$fecDataVat[self::FEC_COLUMN_NUMBER] .= '-'.$numberShipping;
						}

						self::mergeFecLineIntoItemData($items, $fecDataVat);
					}

				}

			}

			if($eInvoice['cashflow']->notEmpty()) { // Contrepartie en 512 directe si un rapprochement a déjà été réalisé

				$eAccountBank['class'] = $eInvoice['cashflow']['account']['label'];
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
				);

				self::mergeFecLineIntoItemData($items, $fecDataBank);

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
					);

					self::mergeFecLineIntoItemData($items, $fecDataRegul);

				}

			}

			$fecData = array_merge($fecData, $items);

		}

		return $fecData;

	}

	public static function filterOperations(array $operations, \Search $search): array {

		$operationsFiltered = [];
		$eAccountFilter = $search->get('account');

		foreach($operations as $operation) {

			if(
				$eAccountFilter->notEmpty() and
				\account\AccountLabelLib::isFromClass($eAccountFilter['class'], $operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]) === FALSE and
				\account\AccountLabelLib::isFromClass($operation[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL], $eAccountFilter['class']) === FALSE
			) {
				continue;
			}

			if($search->get('method') and $search->get('method')->notEmpty()) {
				if($operation[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD] !== $search->get('method')['name']) {
					continue;
				}
			}

			$operationsFiltered[] = $operation;
		}

		return $operationsFiltered;

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
		\account\Account $eAccount, string $date, \journal\JournalCode $eCode, string $ecritureLib,
		string $document, string $documentDate, float $amount, string $type, string $payment, string $compAuxNum, string $compAuxLib,
		?int $number = NULL, ?string $for = NULL
	): array {

		return [
			$eCode['code'] ?? '',
			$eCode['name'] ?? '',
			$number, // Utilisé pour l'import (pour rattacher la TVA à son écriture d'origine)
			date('Ymd', strtotime($date)),
			$eAccount['class'] === '' ? '' : \account\AccountLabelLib::pad($eAccount['class']),
			$eAccount['description'],
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
		];
	}

	private static function explodePaymentsRatio(\selling\Sale $eSale): array {

		if($eSale['profile'] === \selling\Sale::MARKET) {

			return [[
				'label' => \payment\MethodUi::getCashRegisterText(),
				'rate' => 100,
			]];

		}

		$payments = [];
		$cPayment = $eSale['cPayment'];
		$totalAmount = $cPayment->sum('amountIncludingVat');

		if($totalAmount === 0) {
			return [];
		}

		foreach($cPayment as $ePayment) {
			$payments[$ePayment['method']['id']] = [
				'label' => $ePayment['method']['name'],
				'rate' => $totalAmount !== 0.0 ? round($ePayment['amountIncludingVat'] / $totalAmount * 100, 2) : 100,
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
				and $item[self::FEC_COLUMN_OPERATION_NATURE] === $fecLine[self::FEC_COLUMN_OPERATION_NATURE]
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

}

?>
