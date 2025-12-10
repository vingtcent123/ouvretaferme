<?php

new Page(function($data) {

	if(FEATURE_PRE_ACCOUNTING === FALSE) {
		throw new NotExistsAction();
	}

	\user\ConnectionLib::checkLogged();

	$data->eFarm = \farm\FarmLib::getById(GET('id'))->validate('canWrite');

	\company\CompanyLib::connectSpecificDatabaseAndServer($data->eFarm);

	$data->search = new Search([
		'from' => GET('from'),
		'to' => GET('to'),
	]);
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
	->get('/ferme/{id}/precomptabilite', function($data) {

		$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-pre-accounting');
		$data->tipNavigation = 'inline';

		if($data->isSearchValid) {

			$data->nProduct = \selling\ProductLib::countForAccountingCheck($data->eFarm, $data->search);

			$data->nItem = \selling\ItemLib::countForAccountingCheck($data->eFarm, $data->search);

			$data->nSaleDelivered = \selling\SaleLib::countForAccountingCheck('delivered', $data->eFarm, $data->search);
			$data->nSalePayment = \selling\SaleLib::countForAccountingCheck('payment', $data->eFarm, $data->search);
			$data->nSaleClosed = \selling\SaleLib::countForAccountingCheck('closed', $data->eFarm, $data->search);

		} else {

			$data->nProduct = 0;
			$data->nItem = 0;
			$data->nSaleDelivered = 0;
			$data->nSalePayment = 0;
			$data->nSaleClosed = 0;

		}

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/precomptabilite/{type}', function($data) {

		$data->type = GET('type');

		if($data->isSearchValid and in_array($data->type, ['product', 'item', 'delivered', 'payment', 'closed'])) {

			switch($data->type) {

				case 'product':
					[$data->nToCheck, $data->nVerified, $data->cProduct] = \selling\ProductLib::getForAccountingCheck($data->eFarm, $data->search);
					break;

				case 'item':
					[$data->nToCheck, $data->nVerified, $data->cItem] = \selling\ItemLib::getForAccountingCheck($data->eFarm, $data->search);
					break;

				case 'delivered':
				case 'payment':
				case 'closed':
					[$data->nToCheck, $data->nVerified, $data->cSale] = \selling\SaleLib::getForAccountingCheck($data->type, $data->eFarm, $data->search);
					$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
					break;

			}

		} else {

			throw new VoidAction();

		}

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/precomptabilite/sale/', function($data) {

		$data->type = GET('type');

		if(in_array($data->type, ['missingPayment', 'notClosed', 'noDeliveryDate', 'preparationStatus']) === FALSE) {
			throw new VoidAction();
		}

		$data->cSale = \selling\SaleLib::getForAccountingCheck($data->eFarm, $data->search, $data->type);
		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/precomptabilite:fec', function($data) {

		if($data->isSearchValid) {

			if($data->eFarm->hasAccounting()) {
				$cFinancialYear = \account\FinancialYearLib::getAll();
			} else {
				$cFinancialYear = new Collection();
			}
			$export = \farm\AccountingLib::generateFec($data->eFarm, $data->search->get('from'), $data->search->get('to'), $cFinancialYear);

			throw new CsvAction($export, 'pre-comptabilite.csv');

		}

		throw new FailAction('farm\Accounting::invalidDatesForFec');

	})
;

?>
