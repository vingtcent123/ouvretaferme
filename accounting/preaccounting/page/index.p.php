<?php
new Page(function($data) {

	if(get_exists('from') === FALSE and get_exists('to') === FALSE and $data->eFarm->usesAccounting()) {

		$data->search = new Search([
			'from' => $data->eFarm['eFinancialYear']['startDate'],
			'to' => $data->eFarm['eFinancialYear']['endDate'],
		]);

	} else {

		$data->search = new Search([
			'from' => GET('from'),
			'to' => GET('to'),
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

		$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-pre-accounting');
		$data->tipNavigation = 'inline';

		if($data->isSearchValid) {

			$data->nProductToCheck = \preaccounting\ProductLib::countForAccountingCheck($data->eFarm, $data->search);
			$data->nItemToCheck = \preaccounting\ItemLib::countForAccountingCheck($data->eFarm, $data->search, 'invoice');
			$data->nProductVerified = \preaccounting\ProductLib::countForAccountingCheck($data->eFarm, $data->search, FALSE);

			$data->nPaymentToCheck = \preaccounting\InvoiceLib::countForAccountingPaymentCheck($data->eFarm, $data->search);
			$data->nPaymentVerified = \preaccounting\InvoiceLib::countForAccountingCheckVerified($data->eFarm, $data->search);

		} else {

			$data->nProductToCheck = 0;
			$data->nItemToCheck = 0;
			$data->nProductVerified = 0;

			$data->nPaymentToCheck = 0;
			$data->nPaymentVerified = 0;

		}

		$data->type = GET('type');
		if($data->nProductToCheck === 0 and $data->nItemToCheck === 0 and $data->nPaymentToCheck === 0) {
			$data->type = 'export';
		} else if(in_array($data->type, ['product', 'payment', 'export']) === FALSE) {
			if($data->nProductToCheck + $data->nItemToCheck > 0) {
				$data->type = 'product';
			} else {
				$data->type = 'payment';
			}
		}

		if($data->isSearchValid) {

			switch($data->type) {

				case 'product':

					$data->search->set('profile', GET('profile'));
					$data->search->set('name', GET('name'));
					$data->search->set('plant', GET('plant'));
					[$data->cProduct, $data->cCategories, $data->products] = \preaccounting\ProductLib::getForAccountingCheck($data->eFarm, $data->search);
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

			list($data->cSale, $data->nSale) = \preaccounting\SaleLib::getForAccounting($data->eFarm, $data->search);

			$cAccount = \account\AccountLib::getAll();
			$data->operations = \preaccounting\AccountingLib::generateSalesFec($data->cSale, $data->eFarm['cFinancialYear'], $cAccount);

		}
		throw new ViewAction($data);

	})
	->get('/precomptabilite/ventes:telecharger', function($data) {

		if($data->isSearchValid) {

			list($cSale) = \preaccounting\SaleLib::getForAccounting($data->eFarm, $data->search);

			$cAccount = \account\AccountLib::getAll();
			$operations = \preaccounting\AccountingLib::generateSalesFec($cSale, $data->eFarm['cFinancialYear'], $cAccount);

			foreach($operations as &$lineFec) {
				foreach([\preaccounting\AccountingLib::FEC_COLUMN_DEBIT, \preaccounting\AccountingLib::FEC_COLUMN_CREDIT, \preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT] as $column) {
					$lineFec[$column] = \util\TextUi::csvNumber($lineFec[$column]);
				}
			}

			$fecData = array_merge([\account\FecLib::getHeader()], $operations);

			throw new CsvAction($fecData, 'pre-comptabilite-ventes-'.$data->search->get('from').'-'.$data->search->get('to').'.csv');

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

		$data->selectedTab = in_array(GET('tab'), ['market', 'invoice', 'sales']) ? GET('tab') : 'market';

		$search = new Search([
			'from' => $data->eFarm['eFinancialYear']['startDate'],
			'to' => $data->eFarm['eFinancialYear']['endDate'],
		]);

		$errors = \preaccounting\ProductLib::countForAccountingCheck($data->eFarm, $search) +
			\preaccounting\ItemLib::countForAccountingCheck($data->eFarm, $search, 'invoice') +
			\preaccounting\InvoiceLib::countForAccountingPaymentCheck($data->eFarm, $search);

		if($errors > 0) {
			throw new NotExpectedAction('Access to preaccounting import page not permitted (errors found)');
		}

		$data->search = new Search([
			'from' => $data->eFarm['eFinancialYear']['startDate'],
			'to' => $data->eFarm['eFinancialYear']['endDate'],
			'type' => GET('type'),
			'reconciliated' => GET('reconciliated', '?int'),
			'accountingDifference' => GET('accountingDifference', 'bool'),
		]);

		\preaccounting\InvoiceLib::setReadyForAccounting($data->eFarm);

		$data->nInvoice = \preaccounting\AccountingLib::countInvoices($data->eFarm, $data->search);

		$data->cInvoice = \preaccounting\ImportLib::getInvoiceSales($data->eFarm, $data->search);

		throw new ViewAction($data);

	})
	->get('/precomptabilite:rapprocher', function($data) {

		$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-invoice-cashflow');
		$data->tipNavigation = 'inline';

		$data->countsByInvoice = \preaccounting\SuggestionLib::countWaitingByInvoice();

		$data->eImportLast = \bank\ImportLib::getLastImport();

		$data->ccSuggestion = preaccounting\SuggestionLib::getAllWaitingGroupByCashflow();

		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarm, FALSE);

		throw new ViewAction($data);

	})
;

?>
