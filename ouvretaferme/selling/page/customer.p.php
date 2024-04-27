<?php
(new \selling\CustomerPage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \selling\Customer([
			'farm' => $data->eFarm
		]);

	})
	->create(function($data) {

		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(\selling\CustomerUi::url($data->e));
	});

(new \selling\CustomerPage())
	->read('/client/{id}', function($data) {

		$data->eFarm = $data->e['farm'];
		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		\farm\FarmerLib::register($data->eFarm);

		$data->cGrid = \selling\GridLib::getByCustomer($data->e);
		$data->cSale = \selling\SaleLib::getByCustomer($data->e);
		$data->cInvoice = \selling\InvoiceLib::getByCustomer($data->e, selectSales: TRUE);

		$data->e['invite'] = \farm\InviteLib::getByCustomer($data->e);

		$data->cSaleTurnover = \selling\AnalyzeLib::getCustomerTurnover($data->e);

		throw new ViewAction($data);

	})
	->read('analyze', function($data) {

		$data->year = GET('year', 'int');

		$data->cSaleTurnover = \selling\AnalyzeLib::getCustomerTurnover($data->e, $data->year);

		$data->cItemProduct = \selling\AnalyzeLib::getCustomerProducts($data->e, $data->year);
		$data->cItemMonth = \selling\AnalyzeLib::getCustomerMonths($data->e, $data->year);
		$data->cItemMonthBefore = \selling\AnalyzeLib::getCustomerMonths($data->e, $data->year - 1);
		$data->cItemWeek = \selling\AnalyzeLib::getCustomerWeeks($data->e, $data->year);
		$data->cItemWeekBefore = \selling\AnalyzeLib::getCustomerWeeks($data->e, $data->year - 1);

		throw new ViewAction($data);

	})
	->read('updateGrid', function($data) {

		if($data->e['type'] === \selling\Customer::PRIVATE) {
			throw new NotExpectedAction('Invalid customer type');
		}

		$data->e['farm']['selling'] = \selling\ConfigurationLib::getByFarm($data->e['farm']);

		$data->cProduct = \selling\ProductLib::getByCustomer($data->e);

		throw new ViewAction($data);

	}, validate: ['canManage'])
	->write('doUpdateGrid', function($data) {

		if($data->e['type'] === \selling\Customer::PRIVATE) {
			throw new NotExpectedAction('Invalid customer type');
		}

		$data->cGrid = \selling\GridLib::prepareByCustomer($data->e, $_POST);

		\selling\GridLib::updateGrid($data->cGrid);

		throw new ViewAction();

	}, validate: ['canManage'])
	->write('doDeleteGrid', function($data) {

		\selling\GridLib::deleteByCustomer($data->e);

		throw new ReloadLayerAction();

	}, validate: ['canManage'])
	->update()
	->doUpdate(function($data) {
		throw new ReloadAction('selling', 'Customer::updated');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data), validate: ['canManage'])
	->doDelete(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlSellingCustomer($data->e['farm']).'?success=selling:Customer::deleted');
	});

(new Page())
	->post('query', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');;

		$data->cCustomer = \selling\CustomerLib::getFromQuery(POST('query'), $eFarm, POST('withCollective', 'bool', TRUE));

		throw new \ViewAction($data);

	})
	->get('updateOptIn', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->cCustomer = \selling\CustomerLib::getByUser($data->eUserOnline);

		throw new ViewAction($data);

	})
	->post('doUpdateOptIn', function($data) {

		\user\ConnectionLib::checkLogged();

		\selling\CustomerLib::updateOptIn($data->eUserOnline, POST('customer', 'array'));

		throw new ReloadAction('selling', 'Customer::optInUpdated');

	})
	->get('/ferme/{id}/optIn', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('id'))->validate('canRead');
		$data->consent = GET('consent', 'bool', TRUE);

		throw new ViewAction($data);

	})
	->post('doUpdateOptInByEmail', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('id'))->validate('canRead');
		$data->consent = POST('consent', 'bool');

		\selling\CustomerLib::updateOptInByEmail($eFarm, POST('email'), $data->consent);

		throw new ViewAction($data, ':optInSaved');

	})
	->get('/client/{id}/optIn', function($data) {

		$eCustomer = \selling\CustomerLib::getById(GET('id'))->validate();

		$data->consent = GET('consent', 'bool');
		$hash = GET('hash');

		if($hash !== $eCustomer->getOptInHash()) {
			throw new NotExpectedAction('Bad hash');
		}

		$eCustomer['emailOptIn'] = $data->consent;

		\selling\CustomerLib::update($eCustomer, ['emailOptIn']);

		throw new ViewAction($data, ':optInSaved');

	});
?>
