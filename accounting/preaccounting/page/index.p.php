<?php
new Page(function($data) {

	if(
		get_exists('from') === FALSE and
		get_exists('to') === FALSE and
		$data->eFarm->usesAccounting()
	) {

		$data->search = new Search([
			'from' => $data->eFarm['eFinancialYear']['startDate'],
			'to' => $data->eFarm['eFinancialYear']['endDate'],
		]);

	} else {

		$data->search = new Search([
			'from' => GET('from', default: date('Y-01-01')),
			'to' => GET('to', default: date('Y-12-31')),
		]);

	}

	$data->isSearchValid = (
		mb_strlen($data->search->get('from')) === 10 and mb_strlen($data->search->get('to')) === 10 and
		\util\DateLib::isValid($data->search->get('from')) and
		\util\DateLib::isValid($data->search->get('to'))
	);

	if($data->isSearchValid and $data->search->get('from') > $data->search->get('to')) {
		$from = $data->search->get('from');
		$data->search->set('from', $data->search->get('to'));
		$data->search->set('to', $from);
	}

})
	->get('/precomptabilite', function($data) {

		if($data->eFarm['hasSales']) {
			$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-pre-accounting');
			$data->tipNavigation = 'inline';
		}

		if($data->isSearchValid) {

			$data->nProductToCheck = \preaccounting\ProductLib::countForAccountingCheck($data->eFarm, $data->search);
			$data->nItemToCheck = \preaccounting\ItemLib::countForAccountingCheck($data->eFarm, $data->search, 'invoice');

			$data->nPaymentToCheck = \preaccounting\InvoiceLib::countForAccountingPaymentCheck($data->eFarm, $data->search);

			$data->nSale = \preaccounting\SaleLib::countEligible($data->eFarm, $data->search);
			$data->nInvoice = \preaccounting\InvoiceLib::countEligible($data->eFarm, $data->search);

		} else {

			$data->nProductToCheck = 0;
			$data->nItemToCheck = 0;

			$data->nPaymentToCheck = 0;

			$data->nSale = 0;
			$data->nInvoice = 0;

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

		if($data->nProductToCheck === 0 and $data->nItemToCheck === 0 and $data->nPaymentToCheck === 0) {

			$data->type = 'export';

		} else if(in_array($data->type, ['product', 'payment', 'export']) === FALSE) {

			if($data->nProductToCheck + $data->nItemToCheck > 0) {
				$data->type = 'product';
			} else {
				$data->type = 'payment';
			}

		} else if($data->type === 'product') { // On bascule sur l'onglet suivant si + pertinent

			if($data->nProductToCheck + $data->nItemToCheck === 0) {
				if($data->nPaymentToCheck > 0) {
					$data->type = 'payment';
				} else {
					$data->type = 'export';
				}
			}
		} else if($data->type === 'payment') { // On bascule sur l'onglet suivant si + pertinent

			if($data->nPaymentToCheck === 0) {
				if($data->nProductToCheck + $data->nItemToCheck > 0) {
					$data->type = 'product';
				} else {
					$data->type = 'export';
				}
			}
		}

		\session\SessionLib::set('preaccounting-type', $data->type);

		if($data->isSearchValid) {

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
					$data->cInvoice = \preaccounting\InvoiceLib::getForAccountingCheck($data->eFarm, $data->search);
					$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
					break;

			}

		}
		throw new ViewAction($data);

	})
	->get('/precomptabilite/ventes', function($data) {

		if($data->isSearchValid) {

			$cMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
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
			$data->search->set('cMethod', $cMethod);
			$data->search->set('method', $eMethod);
			$data->search->set('account', \account\AccountLib::getById(GET('account')));
			$data->search->set('hasInvoice', GET('hasInvoice', '?int'));

			$cAccount = \account\AccountLib::getAll();

			if($data->search->get('hasInvoice') === NULL or $data->search->get('hasInvoice') === 0) {

				$data->cSale = \preaccounting\SaleLib::getForAccounting($data->eFarm, $data->search);
				[$fecSale, $data->nSale] = \preaccounting\AccountingLib::generateSalesFec($data->cSale, $data->eFarm['cFinancialYear'], $cAccount, $data->search);

			} else {

				$fecSale = [];
				$data->nSale = 0;

			}

			if($data->search->get('hasInvoice') === NULL or $data->search->get('hasInvoice') === 1) {

				$cAccount = \account\AccountLib::getAll();
				$invoiceOperations= \preaccounting\AccountingLib::extractInvoice($data->eFarm, $data->search, $data->eFarm['cFinancialYear'], $cAccount, FALSE);
				$fecInvoice = \preaccounting\AccountingLib::filterOperations($invoiceOperations, $data->search);

				$documents = array_unique(array_column($fecInvoice, \preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT));
				$data->nInvoice = count($documents);

				// Pour avoir le lien vers la facture
				$data->cInvoice = count($documents) > 0 ?
					\selling\Invoice::model()
						->select(['document', 'name', 'customer' => ['name']])
						->whereFarm($data->eFarm)
						->whereName('IN', $documents)
						->getCollection(NULL, NULL, 'name') :
					new Collection();

			} else {

				$fecInvoice = [];
				$data->nInvoice = 0;
				$data->cInvoice = new Collection();

			}

			$data->operations = \preaccounting\AccountingLib::sortOperations(array_merge($fecSale, $fecInvoice));

		} else {

			$data->operations = [];

		}
		throw new ViewAction($data);

	})
	->get('/precomptabilite/ventes:telecharger', function($data) {

		if($data->isSearchValid) {

			$data->search->set('customer', \selling\CustomerLib::getById(GET('customer')));
			$data->search->set('method', GET('method', 'payment\Method'));
			$data->search->set('account', \account\AccountLib::getById(GET('account')));
			$data->search->set('hasInvoice', GET('hasInvoice', '?int'));

			$cSale = \preaccounting\SaleLib::getForAccounting($data->eFarm, $data->search);

			$cAccount = \account\AccountLib::getAll();

			if($data->search->get('hasInvoice') === NULL or $data->search->get('hasInvoice') === 0) {

				[$saleOperations,] = \preaccounting\AccountingLib::generateSalesFec($cSale, $data->eFarm['cFinancialYear'], $cAccount, $data->search);

			} else {
				$saleOperations = [];
			}

			if($data->search->get('hasInvoice') === NULL or $data->search->get('hasInvoice') === 1) {

				$cAccount = \account\AccountLib::getAll();
				$invoiceOperations = \preaccounting\AccountingLib::extractInvoice($data->eFarm, $data->search, $data->eFarm['cFinancialYear'], $cAccount, FALSE);
				$invoiceOperations = \preaccounting\AccountingLib::filterOperations($invoiceOperations, $data->search);

			} else {
				$invoiceOperations = [];
			}

			$operations = \preaccounting\AccountingLib::sortOperations(array_merge($saleOperations, $invoiceOperations));

			if((GET('format') ?? 'csv') === 'csv') {

				// Formattage des nombres
				foreach($operations as &$lineFec) {

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

			throw new DataAction($fecDataString, 'text/txt', $filename);
		}

		throw new VoidAction();

	})
	->get('/precomptabilite:fec', function($data) {

		if($data->isSearchValid) {

			$export = \preaccounting\AccountingLib::generateFec($data->eFarm, $data->search->get('from'), $data->search->get('to'), $data->eFarm['cFinancialYear'], forImport: FALSE);

			throw new CsvAction($export, 'pre-comptabilite.csv');

		}

		throw new FailAction('farm\Accounting::invalidDatesForFec');

	})
	->get('/precomptabilite:importer', function($data) {

		if($data->eFarm->usesAccounting() === FALSE) {
			throw new NotExistsAction();
		}

		$data->selectedTab = in_array(GET('tab'), ['market', 'invoice', 'sales']) ? GET('tab') : 'market';

		$data->search = new Search([
			'type' => GET('type'),
			'accountingDifference' => GET('accountingDifference', '?bool'),
			'customer' => \selling\CustomerLib::getById(GET('customer')),
			'from' => $data->eFarm['eFinancialYear']['startDate'],
			'to' => $data->eFarm['eFinancialYear']['endDate'],
		]);

		\preaccounting\InvoiceLib::setReadyForAccounting($data->eFarm);

		$data->nInvoice = \preaccounting\AccountingLib::countInvoices($data->eFarm, $data->search);

		$data->cInvoice = \preaccounting\ImportLib::getInvoiceSales($data->eFarm, $data->search);

		throw new ViewAction($data);

	})
	->get('/precomptabilite:rapprocher', function($data) {

		$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-invoice-cashflow');
		$data->tipNavigation = 'inline';

		$data->countsByInvoice = \preaccounting\SuggestionLib::countWaiting();

		$data->eImportLast = \bank\ImportLib::getLastImport();

		$data->ccSuggestion = preaccounting\SuggestionLib::getAllWaitingGroupByCashflow();

		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarm, FALSE);

		throw new ViewAction($data);

	})
;

?>
