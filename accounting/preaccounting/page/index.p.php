<?php
new Page(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	\company\CompanyLib::connectSpecificDatabaseAndServer($data->eFarm);

	if($data->eFarm->usesAccounting()) {

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

	} else {

		$data->eFinancialYear = new \account\FinancialYear();

	}

	if(get_exists('from') === FALSE and get_exists('to') === FALSE and $data->eFarm->usesAccounting()) {

		$data->search = new Search([
			'from' => $data->eFinancialYear['startDate'],
			'to' => $data->eFinancialYear['endDate'],
		]);

	} else {

		$data->search = new Search([
			'from' => GET('from'),
			'to' => GET('to'),
		]);

	}

	$data->isSearchValid = (
		$data->search->get('from') and $data->search->get('to') and
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

			$data->nProduct = \preaccounting\ProductLib::countForAccountingCheck($data->eFarm, $data->search) +
				\preaccounting\ItemLib::countForAccountingCheck($data->eFarm, $data->search);
			$data->nProductVerified = \preaccounting\ProductLib::countForAccountingCheck($data->eFarm, $data->search, FALSE) +
				\preaccounting\ItemLib::countForAccountingCheck($data->eFarm, $data->search, FALSE);

			$data->nSalePayment = array_sum(\preaccounting\SaleLib::countForAccountingCheck('payment', $data->eFarm, $data->search));
			$data->nSalePaymentVerified = array_sum(\preaccounting\SaleLib::countForAccountingCheck('payment', $data->eFarm, $data->search, FALSE));

			$data->nSaleClosed = array_sum(\preaccounting\SaleLib::countForAccountingCheck('closed', $data->eFarm, $data->search));
			$data->nSaleClosedVerified = array_sum(\preaccounting\SaleLib::countForAccountingCheck('closed', $data->eFarm, $data->search, FALSE));

		} else {

			$data->nProduct = 0;
			$data->nSalePayment = 0;
			$data->nSaleClosed = 0;
			$data->nProductVerified = 0;
			$data->nSalePaymentVerified = 0;
			$data->nSaleClosedVerified = 0;

		}

		$data->type = GET('type');
		if(in_array($data->type, ['product', 'payment', 'closed', 'export']) === FALSE) {
			$data->type = 'product';
		}

		if($data->isSearchValid) {

			switch($data->type) {

				case 'product':

					$data->search->set('profile', GET('profile'));
					$data->search->set('name', GET('name'));
					$data->search->set('plant', GET('plant'));
					[$data->nToCheck, $data->nVerified, $data->cProduct, $data->cCategories, $data->products] = \preaccounting\ProductLib::getForAccountingCheck($data->eFarm, $data->search);
					[$data->nToCheckItem, $data->nVerifiedItem, $data->cItem] = \preaccounting\ItemLib::getForAccountingCheck($data->eFarm, $data->search);

					break;

				case 'payment':
					$data->search->set('customer', \selling\CustomerLib::getById(GET('customer')));
					[$data->nToCheck, $data->nVerified, $data->cSale] = \preaccounting\SaleLib::getForPaymentAccountingCheck($data->eFarm, $data->search);
					$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
					break;

				case 'closed':
					$data->search->set('tab', GET('tab'));
					[$data->nToCheck, $data->nVerified, $data->cSale, $data->cInvoice] = \preaccounting\SaleLib::getForAccountingCheck($data->type, $data->eFarm, $data->search);
					$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
					break;

			}

		}
		throw new ViewAction($data);

	})
	->get('/precomptabilite:fec', function($data) {

		if($data->isSearchValid) {

			if($data->eFarm->hasAccounting()) {
				$cFinancialYear = \account\FinancialYearLib::getAll();
			} else {
				$cFinancialYear = new Collection();
			}
			$export = \preaccounting\AccountingLib::generateFec($data->eFarm, $data->search->get('from'), $data->search->get('to'), $cFinancialYear, forImport: FALSE);

			throw new CsvAction($export, 'pre-comptabilite.csv');

		}

		throw new FailAction('farm\Accounting::invalidDatesForFec');

	})
	->get('/precomptabilite:importer', function($data) {

		$data->selectedTab = in_array(GET('tab'), ['market', 'invoice', 'sales']) ? GET('tab') : 'market';

		$from = $data->eFinancialYear['startDate'];
		$to = $data->eFinancialYear['endDate'];

		$data->search = new Search([
			'from' => $data->eFinancialYear['startDate'],
			'to' => $data->eFinancialYear['endDate'],
			'type' => GET('type'),
		]);

		$data->counts = \preaccounting\PreaccountingLib::countImports($data->eFarm, $from, $to, $data->search);

		$isTabFilled = count(array_filter($data->counts, fn($val, $key) => ($key === $data->selectedTab and $val > 0), ARRAY_FILTER_USE_BOTH)) > 1;

		if($isTabFilled === FALSE and array_sum($data->counts) > 0) {
			$data->selectedTab = first(array_keys(array_filter($data->counts, fn($val) => $val > 0)));
		}

		if($data->selectedTab) {

			$data->c = match($data->selectedTab) {
				'market' => \preaccounting\ImportLib::getMarketSales($data->eFarm, $from, $to),
				'invoice' => \preaccounting\ImportLib::getInvoiceSales($data->eFarm, $data->search),
				'sales' => \preaccounting\ImportLib::getSales($data->eFarm, $data->search),
			};
		} else {
			$data->c = new Collection();
		}

		throw new ViewAction($data);

	})
	->get('/precomptabilite:rapprocher-factures', function($data) {

		$data->countsByInvoice = \preaccounting\SuggestionLib::countWaitingByInvoice();

		$data->eImportLast = \bank\ImportLib::getLastImport();

		$data->ccSuggestion = preaccounting\SuggestionLib::getAllWaitingGroupByCashflow();

		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarm, FALSE);

		throw new ViewAction($data);

	})
;

?>
