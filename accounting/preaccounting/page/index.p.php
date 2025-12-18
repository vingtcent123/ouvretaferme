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

		throw new ViewAction($data);

	})
	->get('/precomptabilite/{type}', function($data) {

		$data->type = GET('type');

		if(Route::getRequestedWith() !== 'ajax') {
			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite?type='.GET('type').'&from='.GET('from').'&to='.GET('to').'&tab='.GET('tab'));
		}

		if($data->isSearchValid and in_array($data->type, ['product', 'payment', 'closed'])) {

			switch($data->type) {

				case 'product':

					[$data->nToCheck, $data->nVerified, $data->cProduct, $data->cCategories, $data->products] = \preaccounting\ProductLib::getForAccountingCheck($data->eFarm, $data->search);
					[$data->nToCheckItem, $data->nVerifiedItem, $data->cItem] = \preaccounting\ItemLib::getForAccountingCheck($data->eFarm, $data->search);

					break;

				case 'payment':
					[$data->nToCheck, $data->nVerified, $data->cSale] = \preaccounting\SaleLib::getForPaymentAccountingCheck($data->eFarm, $data->search);
					$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
					break;

				case 'closed':
					$data->search->set('tab', GET('tab'));
					[$data->nToCheck, $data->nVerified, $data->cSale, $data->cInvoice] = \preaccounting\SaleLib::getForAccountingCheck($data->type, $data->eFarm, $data->search);
					$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
					break;

			}

		} else {

			throw new VoidAction();

		}

		throw new ViewAction($data);

	})
	->get('/precomptabilite/sale/', function($data) {

		$data->type = GET('type');

		if(in_array($data->type, ['missingPayment', 'notClosed', 'noDeliveryDate', 'preparationStatus']) === FALSE) {
			throw new VoidAction();
		}

		$data->cSale = \preaccounting\SaleLib::getForAccountingCheck($data->eFarm, $data->search, $data->type);
		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);

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

		$data->c = match($data->selectedTab) {
			'market' => \preaccounting\ImportLib::getMarketSales($data->eFarm, $from, $to),
			'invoice' => \preaccounting\ImportLib::getInvoiceSales($data->eFarm, $data->search),
			'sales' => \preaccounting\ImportLib::getSales($data->eFarm, $data->search),
		};

		throw new ViewAction($data);

	})
	->get('/precomptabilite:rapprocher-ventes', function($data) {

		$data->counts = (\preaccounting\SuggestionLib::countWaitingByInvoice() + \preaccounting\SuggestionLib::countWaitingBySale());

		$data->eImportLast = \bank\ImportLib::getLastImport();

		$data->ccSuggestion = preaccounting\SuggestionLib::getAllWaitingGroupByCashflow();

		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarm, FALSE);

		throw new ViewAction($data);

	})
	->get('/precomptabilite:rapprocher-ecritures', function($data) {

		$data->counts = \preaccounting\SuggestionLib::countWaitingByOperation();

		$data->ccSuggestion = preaccounting\SuggestionLib::getAllWaitingGroupByOperation();

		throw new ViewAction($data);

	})
;

?>
