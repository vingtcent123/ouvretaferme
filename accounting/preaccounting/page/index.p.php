<?php
new Page(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(
		get_exists('from') === FALSE and
		get_exists('to') === FALSE and
		$data->eFarm->usesAccounting()
	) {

		$from = $data->eFarm['eFinancialYear']['startDate'];
		$to = $data->eFarm['eFinancialYear']['endDate'];

	} else {

		$from = GET('from', default: $data->eFarm['eFinancialYear']['startDate']);
		$to = GET('to', default: $data->eFarm['eFinancialYear']['endDate']);

	}

	if(mb_strlen($from) !== 10 or \util\DateLib::isValid($from) === FALSE) {
		$from = $data->eFarm['eFinancialYear']['startDate'];
	}

	if(mb_strlen($to) !== 10 or \util\DateLib::isValid($to) === FALSE) {
		$to = $data->eFarm['eFinancialYear']['endDate'];
	}

	if($from > $to) {
		$fromOld = $from;
		$from = $to;
		$to = $fromOld;
	}

	$data->search = new Search([
		'from' => $from,
		'to' => $to,
	]);

})
	->get('/precomptabilite', function($data) {

		$from = $data->search->get('from');
		$to = $data->search->get('to');
		if($from < $data->eFarm['eFinancialYear']['startDate'] or $from > $data->eFarm['eFinancialYear']['endDate']) {
			$from = $data->eFarm['eFinancialYear']['startDate'];
		}

		if($to < $data->eFarm['eFinancialYear']['startDate'] or $to > $data->eFarm['eFinancialYear']['endDate']) {
			$to = $data->eFarm['eFinancialYear']['endDate'];
		}
		$data->search->set('from', $from);
		$data->search->set('to', $to);

		$eFinancialYear = $data->eFarm['eFinancialYear'];
		$data->dates = \preaccounting\PreaccountingLib::extractMonths($eFinancialYear);

		// Vérifier l'éligibilité de toutes les factures par mois de l'exercice comptable
		$data->cInvoice = \preaccounting\PreaccountingLib::checkInvoices($data->eFarm, $data->dates);
		$data->cInvoiceImported = \preaccounting\PreaccountingLib::checkImportedInvoices($data->eFarm, $data->dates);

		// Vérifier l'éligibilité de toutes les lignes de cash par mois de l'exercice comptable
		$data->cRegister = \cash\RegisterLib::getActive();
		$data->cCash = \preaccounting\PreaccountingLib::checkCash($data->cRegister, $data->dates);
		$data->cCashImported = \preaccounting\PreaccountingLib::checkImportedCash($data->cRegister, $data->dates);

		if($data->eFarm['hasSales'] or $data->cCash->notEmpty()) {

			$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-pre-accounting-summary');
			$data->tipNavigation = 'inline';

		}

		throw new ViewAction($data);

})
	->get('/precomptabilite/verifier:fec', function($data) {

		$data->checkType = 'fec';

		$eFinancialYear = $data->eFarm['eFinancialYear'];
		$data->dates = \preaccounting\PreaccountingLib::extractMonths($eFinancialYear);

		if($data->eFarm['hasSales']) {
			$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-pre-accounting-fec');
			$data->tipNavigation = 'inline';
		}

		$data->nProductToCheck = \preaccounting\ProductLib::countForAccountingCheck($data->eFarm, $data->search);
		$data->nItemToCheck = \preaccounting\ItemLib::countForAccountingCheck($data->eFarm, $data->search);

		$data->nInvoiceForPaymentToCheck = \preaccounting\InvoiceLib::countForPaymentAccountingCheck($data->eFarm, $data->search);

		$data->cRegister = \cash\RegisterLib::getActive();
		$data->cRegisterMissing = $data->cRegister->find(fn($e) => $e['account']->empty());

		if($data->cRegister->notEmpty()) {
			$data->cCash = \preaccounting\PreaccountingLib::checkCash($data->cRegister, $data->dates);
		} else {
			$data->cCash = new Collection();
		}

		if(get_exists('type') === FALSE) {

			try {
				$data->type = \session\SessionLib::get('preaccounting-type');
			} catch(\Exception) {
				$data->type = NULL;
			}

		} else {
			$data->type = GET('type');
		}

		if(
			$data->nProductToCheck === 0 and
			$data->nItemToCheck === 0 and
			$data->nInvoiceForPaymentToCheck === 0
		) {

			$data->type = 'export';

		} else if(get_exists('type') === FALSE or empty(GET('type'))) {

			if(($data->nProductToCheck + $data->nItemToCheck + $data->nInvoiceForPaymentToCheck) > 0) {

				$data->type = 'product';

			} else if($data->nInvoiceForPaymentToCheck > 0) {

				$data->type = 'payment';

			} else {

				$data->type = 'export';

			}

		} else if($data->type === 'product') { // On bascule sur l'onglet suivant si + pertinent

			if($data->nProductToCheck + $data->nItemToCheck === 0) {
				if($data->nInvoiceForPaymentToCheck > 0) {
					$data->type = 'payment';
				} else {
					$data->type = 'export';
				}
			}

		} else if($data->type === 'payment') { // On bascule sur l'onglet suivant si + pertinent

			if($data->nInvoiceForPaymentToCheck === 0) {
				if($data->nProductToCheck + $data->nItemToCheck > 0) {
					$data->type = 'product';
				} else {
					$data->type = 'export';
				}
			}
		}

		\session\SessionLib::set('preaccounting-type', $data->type);

		switch($data->type) {

			case 'product':

				$data->search->set('profile', GET('profile'));
				$data->search->set('name', GET('name'));
				$data->search->set('plant', GET('plant'));
				[$data->cProduct, $data->cCategories, $data->products] = \preaccounting\ProductLib::getForAccountingCheck($data->eFarm, $data->search, $data->nItemToCheck);
				$data->cItem = \preaccounting\ItemLib::getForAccountingCheck($data->eFarm, $data->search, 'invoice');
				break;

			case 'payment':
				$data->search->set('customer', \selling\CustomerLib::getById(GET('customer')));
				$data->cInvoiceForPayment = \preaccounting\InvoiceLib::getForAccountingCheck($data->eFarm, $data->search);
				$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
				break;

			case 'export':

				$cMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
				$cRegister = \cash\RegisterLib::getAll();

				if($data->checkType === 'fec') {

					if(
						GET('counterpart', 'int') === (int)\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS or
						GET('counterpart', 'int') === (int)\account\AccountSetting::WAITING_ACCOUNT_SUBCLASS
					) {
						$counterpart = GET('counterpart');
					} else {
						$counterpart = NULL;
					}

					$data->search->set('counterpart', $counterpart);

					$cAccount = \account\AccountLib::getAll(new Search(['withVat' => TRUE, 'withJournal' => TRUE]));
					$data->search->set('cMethod', $cMethod);
					$data->search->set('account', \account\AccountLib::getById(GET('account')));

					$cMethod->offsetSet(0, new \payment\Method(['id' => \preaccounting\SaleLib::MARKET_PAYMENT_METHOD_FAKE_ID, 'name' => \payment\MethodUi::getCashRegisterText()]));

					$methodId = GET('method', '?int');
					if(is_int($methodId)) {
						if($methodId === \preaccounting\SaleLib::MARKET_PAYMENT_METHOD_FAKE_ID) {
							$eMethod = $cMethod->offsetGet(\preaccounting\SaleLib::MARKET_PAYMENT_METHOD_FAKE_ID);
						} else {
							$eMethod = $cMethod->offsetGet($methodId);
						}
					} else {
						$eMethod = new \payment\Method();
					}

					$data->search->set('customer', \selling\CustomerLib::getById(GET('customer')));
					$data->search->set('method', $eMethod);

					$hasInvoice = in_array(GET('filter', '?string'), ['hasInvoice', 'noInvoice']) ? GET('filter', '?string') === 'hasInvoice' : NULL;
					$data->search->set('filter', GET('filter', '?string'));

					$data->search->set('cRegister', $cRegister);
					$eRegister = (GET('filter', 'int') !== 0) ? $cRegister->offsetGet(GET('filter', 'int')) : new \cash\Register();
					$data->search->set('register', $eRegister);

					// Préfiltre Caisse et Moyen de paiement
					if($eRegister->empty()) {
						if($eMethod->empty()) {
							// Pas de filtre caisse / moyen de paiement
							$cRegisterFiltered = $cRegister;
						} else {
							// On filtre les caisses sur le moyen de paiement
							$cRegisterFiltered = $cRegister->find(fn($e) => $e['paymentMethod']->is($eMethod));
						}
					} else {
						if($eMethod->empty()) {
							$cRegisterFiltered = new Collection([$eRegister]);
						} else {
							$cRegisterFiltered = $eRegister['paymentMethod']->is($eMethod) ? new Collection([$eRegister]) : new Collection();
						}
					}

					$data->search->set('cRegisterFilter', $cRegisterFiltered);

					// Ventes des journaux de caisse
					if(
						in_array($data->search->get('filter'), ['noInvoice', 'hasInvoice']) === FALSE and
						($eMethod->empty() or
						($eRegister->notEmpty() and ($eMethod->empty() or $eMethod->is($eRegister['paymentMethod']))))
					) {

						$data->cCash = \preaccounting\CashLib::getForAccounting($data->eFarm, $data->search, FALSE);
						[$fecCash, $data->nCash] = \preaccounting\AccountingLib::generateCashFec(
							cCash: $data->cCash,
							cAccount: $cAccount,
							search: $data->search
						);

					} else {

						$fecCash = [];
						$data->nCash = 0;

					}

				} else {

					$fecCash = [];
					$data->nCash = 0;

				}

				// Ventes non facturées
				if($data->search->get('filter') === NULL or $hasInvoice === FALSE) {

					$data->cSale = \preaccounting\SaleLib::getForAccounting($data->eFarm, $data->search);
					[$fecSale, $data->nSale] = \preaccounting\AccountingLib::generateSalesFec($data->cSale, $cAccount, $data->search->get('account'), new \selling\Payment(), counterpart: $data->search->get('counterpart') ?? NULL);

				} else {

					$fecSale = [];
					$data->nSale = 0;

				}

				// Ventes facturées
				if($data->search->get('filter') === NULL or $hasInvoice === TRUE) {

					$cInvoice = \preaccounting\InvoiceLib::getForExport($data->eFarm, $data->search);
					[$fecInvoice, $data->nInvoice] = preaccounting\AccountingLib::generateInvoicesFec($cInvoice, $cAccount, $data->search->get('account'), new \selling\Payment(), $data->search->get('counterpart'));

					$documents = array_unique(array_column($fecInvoice, \preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT));

					// Pour avoir le lien vers la facture
					$data->cInvoice = count($documents) > 0 ?
						\selling\Invoice::model()
							->select(['document', 'number', 'customer' => ['name']])
							->whereFarm($data->eFarm)
							->whereNumber('IN', $documents)
							->getCollection(NULL, NULL, 'number') :
						new Collection();

				} else {

					$fecInvoice = [];
					$data->nInvoice = 0;
					$data->cInvoice = new Collection();

				}

				$data->operations = \preaccounting\AccountingLib::sortOperations(array_merge($fecCash, $fecSale, $fecInvoice));

				break;

		}

		throw new ViewAction($data, ':/precomptabilite/verifier');

	})
	->get('/precomptabilite/verifier:import', function($data) {

		$data->checkType = 'import';
		$data->search->set('to', date('Y-m-t', strtotime($data->search->get('from'))));

		$eFinancialYear = $data->eFarm['eFinancialYear'];
		$data->dates = \preaccounting\PreaccountingLib::extractMonths($eFinancialYear);

		if($data->eFarm['hasSales'] and $data->search->get('from') < date('Y-m-01')) {
			$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-pre-accounting-import');
			$data->tipNavigation = 'inline';
		}

		$data->nProductToCheck = \preaccounting\ProductLib::countForAccountingCheck($data->eFarm, $data->search);
		$data->nItemToCheck = \preaccounting\ItemLib::countForAccountingCheck($data->eFarm, $data->search);

		$data->nInvoiceForPaymentToCheck = \preaccounting\InvoiceLib::countForPaymentAccountingCheck($data->eFarm, $data->search);

		$data->cRegister = \cash\RegisterLib::getActive();

		if($data->cRegister->notEmpty()) {
			$data->cCash = \preaccounting\PreaccountingLib::checkCash($data->cRegister, $data->dates);
		} else {
			$data->cCash = new Collection();
		}

		$data->cRegisterMissing = $data->cRegister->find(fn($e) => (
			in_array($e['id'], $data->cCash->getKeys()) and
			$e['account']->empty() and
			isset($data->cCash[$e['id']][mb_substr($data->search->get('from'), 0, 7)])
		));

		if(get_exists('type') === FALSE) {

			try {
				$data->type = \session\SessionLib::get('preaccounting-type');
			} catch(\Exception) {
				$data->type = NULL;
			}

		} else {
			$data->type = GET('type');
		}

		if(
			$data->nProductToCheck === 0 and
			$data->nItemToCheck === 0 and
			$data->cRegisterMissing->count() === 0
		) {

			$data->type = 'export';

		} else if(get_exists('type') === FALSE or empty(GET('type'))) {

			if(($data->nProductToCheck + $data->nItemToCheck + $data->cRegisterMissing->count()) > 0) {

				$data->type = 'product';

			} else if($data->cRegisterMissing->count() > 0) {

				$data->type = 'payment';

			} else {
				
				$data->type = 'export';

			}

		} else if($data->type === 'product') { // On bascule sur l'onglet suivant si + pertinent

			if($data->nProductToCheck + $data->nItemToCheck === 0) {
				if($data->cRegisterMissing->count() > 0) {
					$data->type = 'payment';
				} else {
					$data->type = 'export';
				}
			}

		} else if($data->type === 'payment') { // On bascule sur l'onglet suivant si + pertinent

			if($data->cRegisterMissing->count() === 0) {
				if($data->nProductToCheck + $data->nItemToCheck > 0) {
					$data->type = 'product';
				} else {
					$data->type = 'export';
				}
			}
		}

		\session\SessionLib::set('preaccounting-type', $data->type);

		switch($data->type) {

			case 'product':

				$data->search->set('profile', GET('profile'));
				$data->search->set('name', GET('name'));
				$data->search->set('plant', GET('plant'));
				[$data->cProduct, $data->cCategories, $data->products] = \preaccounting\ProductLib::getForAccountingCheck($data->eFarm, $data->search, $data->nItemToCheck);
				$data->cItem = \preaccounting\ItemLib::getForAccountingCheck($data->eFarm, $data->search, 'invoice');
				break;

			case 'payment':
				$data->search->set('customer', \selling\CustomerLib::getById(GET('customer')));
				$data->cInvoiceForPayment = \preaccounting\InvoiceLib::getForAccountingCheck($data->eFarm, $data->search);
				$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
				break;

			case 'export':

				$cMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
				$cRegister = \cash\RegisterLib::getAll();

				if(get_exists('from') === FALSE) {
					$from = first($data->dates);
				} else {
					$from = mb_substr(GET('from'), 0, 7).'-01';
				}
				if(in_array(mb_substr($from, 0, 7), $data->dates) === FALSE) {
					$from = first($data->dates);
				}

				$data->search = new Search([
					'importType' => GET('importType'),
					'customerType' => GET('customerType'),
					'accountingDifference' => GET('accountingDifference', '?bool'),
					'customer' => \selling\CustomerLib::getById(GET('customer')),
					'from' => $from,
					'to' => date('Y-m-t', strtotime(mb_substr($from, 0, 7).'-01')),
					'cRegister' => $cRegister,
					'cMethod' => $cMethod,
				]);

				\preaccounting\PaymentLib::setAccountingReady($data->eFarm);
				\preaccounting\CashLib::setAccountingReady();

				if(in_array($data->search->get('importType'), ['', 'invoice'])) {
					$cPayment = \preaccounting\ImportLib::getPayments($data->eFarm, $data->search);
					$nPayment = \preaccounting\InvoiceLib::countForAccounting($data->eFarm, $data->search);
				} else {
					$cPayment = new Collection();
					$nPayment = 0;
				}

				if(empty($data->search->get('importType')) or (int)$data->search->get('importType') !== 0) {
					$cCash = \preaccounting\ImportLib::getCash($data->eFarm, $data->search);
					$nCash = \preaccounting\CashLib::countForAccounting($data->eFarm, $data->search, TRUE);
				} else {
					$cCash = new Collection();
					$nCash = 0;
				}
				$cOperation = $cPayment->filter(fn($e) => $e->acceptAccountingImport())->mergeCollection($cCash->filter(fn($e) => $e->acceptAccountingImport()));
				$cOperation->sort(function($e1, $e2) {
					$date1 = $e1 instanceof \selling\Payment ? $e1['paidAt'] : $e1['date'];
					$date2 = $e2 instanceof \selling\Payment ? $e2['paidAt'] : $e2['date'];

					if($date1 === $date2 and $e1 instanceof \cash\Cash and $e2 instanceof \cash\Cash) {
						return $e1['position'] <=> $e2['position'];
					}
					return $date1 <=> $date2;
				});

				$data->cOperation = $cOperation;
				$data->nPayment = $nPayment;
				$data->nCash = $nCash;
				$data->lastValidationDate = \journal\OperationLib::getLastValidationDate($eFinancialYear);

				break;

		}

		throw new ViewAction($data, ':/precomptabilite/verifier');

	})
	->get('/precomptabilite/fec:telecharger', function($data) {

		$cAccount = \account\AccountLib::getAll(new Search(['withVat' => TRUE, 'withJournal' => TRUE]));
		$cMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);

		$data->search->set('customer', \selling\CustomerLib::getById(GET('customer')));
		$data->search->set('cMethod', $cMethod);
		$data->search->set('method', \payment\MethodLib::getById(GET('method')));
		$data->search->set('account', \account\AccountLib::getById(GET('account')));
		$data->search->set('hasInvoice', GET('hasInvoice', '?int'));

		$hasInvoice = in_array(GET('filter', '?string'), ['hasInvoice', 'noInvoice']) ? GET('filter', '?string') === 'hasInvoice' : NULL;
		$data->search->set('filter', GET('filter', '?string'));

		$data->search->set('cRegister', \cash\RegisterLib::getAll());
		$eRegister = (GET('filter', 'int') !== 0) ? $data->search->get('cRegister')->offsetGet(GET('filter', 'int')) : new \cash\Register();
		$data->search->set('register', $eRegister);

		if(
			GET('counterpart', 'int') === (int)\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS or
			GET('counterpart', 'int') === (int)\account\AccountSetting::WAITING_ACCOUNT_SUBCLASS
		) {
			$counterpart = GET('counterpart');
		} else {
			$counterpart = NULL;
		}
		$data->search->set('counterpart', $counterpart);

		if($data->search->get('cRegister')->notEmpty() and $hasInvoice === NULL) {

			// Ventes des journaux de caisse
			$data->cCash = \preaccounting\CashLib::getForAccounting($data->eFarm, $data->search, FALSE);
			[$cashOperations, ] = \preaccounting\AccountingLib::generateCashFec(
				cCash: $data->cCash,
				cAccount: $cAccount,
				search: $data->search
			);

		} else {
			$cashOperations = [];
		}

		if($data->search->get('filter') === NULL or $hasInvoice === FALSE) {

			$cSale = \preaccounting\SaleLib::getForAccounting($data->eFarm, $data->search);
			[$saleOperations,] = \preaccounting\AccountingLib::generateSalesFec($cSale, $cAccount, $data->search->get('account'), counterpart: $data->search->get('counterpart') ?? NULL);

		} else {
			$saleOperations = [];
		}

		if($data->search->get('filter') === NULL or $hasInvoice === TRUE) {

			$cInvoice = \preaccounting\InvoiceLib::getForExport($data->eFarm, $data->search);
			[$invoiceOperations, ] = preaccounting\AccountingLib::generateInvoicesFec($cInvoice, $cAccount, $data->search->get('account'));

		} else {
			$invoiceOperations = [];
		}

		$operations = \preaccounting\AccountingLib::sortOperations(array_merge($cashOperations, $saleOperations, $invoiceOperations));

		if((GET('format') ?? 'csv') === 'csv') {

			// Formattage des nombres
			foreach($operations as &$lineFec) {

				\preaccounting\AccountingLib::unsetExtraColumns($lineFec);
				foreach([\preaccounting\AccountingLib::FEC_COLUMN_DEBIT, \preaccounting\AccountingLib::FEC_COLUMN_CREDIT, \preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT] as $column) {
					$lineFec[$column] = \util\TextUi::csvNumber($lineFec[$column]);
				}

			}

			$fecData = array_merge([\account\FecLib::getHeader()], $operations);

			throw new CsvAction($fecData, 'pre-comptabilite-ventes-'.$data->search->get('from').'-'.$data->search->get('to').'.csv');

		}

		$fecData = array_merge([\account\FecLib::getHeader()], $operations);
		$fecDataString = join("\n", array_map(fn($operation) => join('|', $operation), $fecData));

		$filename = \account\FecLib::getFilename($data->eFarm, new \account\FinancialYear());

		throw new DataAction($fecDataString, 'text/txt', $filename.'.txt');

	})
	->get('/precomptabilite:rapprocher', function($data) {

		$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-invoice-cashflow');
		$data->tipNavigation = 'inline';

		$data->nSuggestionWaiting = \preaccounting\SuggestionLib::countWaiting();

		$data->eImportLast = \bank\ImportLib::getLastImport();

		$data->ccSuggestion = preaccounting\SuggestionLib::getAllWaitingGroupByCashflow();

		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarm, FALSE);

		throw new ViewAction($data);

	})
;

?>
