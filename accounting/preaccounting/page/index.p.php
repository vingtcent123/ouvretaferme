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

			$data->nProduct = \preaccounting\ProductLib::countForAccountingCheck($data->eFarm, $data->search);

			$data->nItem = \preaccounting\ItemLib::countForAccountingCheck($data->eFarm, $data->search);

			$data->nSaleDelivered = \preaccounting\SaleLib::countForAccountingCheck('delivered', $data->eFarm, $data->search);
			$data->nSalePayment = \preaccounting\SaleLib::countForAccountingCheck('payment', $data->eFarm, $data->search);
			$data->nSaleClosed = \preaccounting\SaleLib::countForAccountingCheck('closed', $data->eFarm, $data->search);

		} else {

			$data->nProduct = 0;
			$data->nItem = 0;
			$data->nSaleDelivered = 0;
			$data->nSalePayment = 0;
			$data->nSaleClosed = 0;

		}

		$data->counts = \preaccounting\PreaccountingLib::counts($data->eFarm, $data->search->get('from'), $data->search->get('to'), $data->search);

		throw new ViewAction($data);

	})
	->get('/precomptabilite/{type}', function($data) {

		$data->type = GET('type');

		if($data->isSearchValid and in_array($data->type, ['product', 'item', 'delivered', 'payment', 'closed'])) {

			switch($data->type) {

				case 'product':
					[$data->nToCheck, $data->nVerified, $data->cProduct] = \preaccounting\ProductLib::getForAccountingCheck($data->eFarm, $data->search);
					break;

				case 'item':
					[$data->nToCheck, $data->nVerified, $data->cItem] = \preaccounting\ItemLib::getForAccountingCheck($data->eFarm, $data->search);
					break;

				case 'delivered':
				case 'payment':
				case 'closed':
					[$data->nToCheck, $data->nVerified, $data->cSale] = \preaccounting\SaleLib::getForAccountingCheck($data->type, $data->eFarm, $data->search);
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

		$data->counts = \preaccounting\PreaccountingLib::counts($data->eFarm, $from, $to, $data->search);

		$data->c = match($data->selectedTab) {
			'market' => \preaccounting\ImportLib::getMarketSales($data->eFarm, $from, $to),
			'invoice' => \preaccounting\ImportLib::getInvoiceSales($data->eFarm, $data->search),
			'sales' => \preaccounting\ImportLib::getSales($data->eFarm, $data->search),
		};

		throw new ViewAction($data);

	})
	->get('/precomptabilite:rapprocher-ventes', function($data) {

		$data->counts = \preaccounting\PreaccountingLib::counts($data->eFarm, $data->search->get('from'), $data->search->get('to'), $data->search);

		//$data->ccSuggestion = preaccounting\SuggestionLib::getAllWaitingGroupByOperation();
		$data->ccSuggestion = preaccounting\SuggestionLib::getAllWaitingGroupByCashflow();

		throw new ViewAction($data);

	})
	->get('/precomptabilite:rapprocher-ecritures', function($data) {

		$data->counts = \preaccounting\PreaccountingLib::counts($data->eFarm, $data->search->get('from'), $data->search->get('to'), $data->search);

		$data->ccSuggestion = preaccounting\SuggestionLib::getAllWaitingGroupByOperation();

		throw new ViewAction($data);

	})
;

?>
