<?php
new \selling\CustomerPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \selling\Customer([
			'farm' => $data->eFarm,
			'user' => new \user\User(),
			'nGroup' => \selling\CustomerGroupLib::countByFarm($data->eFarm)
		]);

	})
	->create()
	->doCreate(function($data) {
		throw new RedirectAction(\selling\CustomerUi::url($data->e));
	});

new \selling\CustomerPage()
	->read('/client/{id}', function($data) {

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		$data->cGrid = \selling\GridLib::getByCustomer($data->e);
		$data->cGridGroup = \selling\GridLib::getByGroups($data->e['groups']);
		$data->cSale = \selling\SaleLib::getByCustomer($data->e);
		$data->cInvoice = \selling\InvoiceLib::getByCustomer($data->e, selectSales: TRUE);

		$data->e['invite'] = \farm\InviteLib::getByCustomer($data->e);
		$data->e['contact'] = \mail\ContactLib::getByCustomer($data->e, autoCreate: TRUE);

		$data->cEmail = \mail\EmailLib::getByCustomer($data->e);

		$data->cSaleTurnover = \selling\AnalyzeLib::getCustomerTurnover($data->e);

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);

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
	->update(function($data) {

		$data->e['nGroup'] = \selling\CustomerGroupLib::countByFarm($data->e['farm']);
		$data->e['cPaymentMethod'] = \payment\MethodLib::getByFarm($data->e['farm'], FALSE);

		throw new ViewAction($data);

	})
	->doUpdate(function($data) {
		throw new ReloadAction('selling', 'Customer::updated');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data), validate: ['canManage'])
	->doDelete(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlSellingCustomers($data->e['farm']).'?success=selling:Customer::deleted');
	});

new \selling\CustomerPage()
	->applyCollection(function($data, Collection $c) {
		$c->validateProperty('farm', $c->first()['farm']);
	})
	->writeCollection('doUpdateGroupAssociateCollection', function($data) {

		$eFarm = $data->c->first()['farm'];
		$eCustomerGroup = \selling\CustomerGroupLib::getById(POST('group'))->validateProperty('farm', $eFarm);

		\selling\CustomerLib::associateGroup($data->c, $eCustomerGroup);

		throw new ReloadAction();

	})
	->writeCollection('doUpdateGroupDissociateCollection', function($data) {

		$eFarm = $data->c->first()['farm'];
		$eCustomerGroup = \selling\CustomerGroupLib::getById(POST('group'))->validateProperty('farm', $eFarm);

		\selling\CustomerLib::dissociateGroup($data->c, $eCustomerGroup);

		throw new ReloadAction();

	})
	->doUpdateCollectionProperties('doUpdateStatusCollection', ['status'], fn($data) => throw new ReloadAction());

new Page()
	->post('getGroupField', function($data) {

		if(POST('id')) {
			$data->eCustomer = \selling\CustomerLib::getById(POST('id'))->validate('canWrite');
		} else {
			$data->eCustomer = new \selling\Customer();
		}

		$type = \selling\Customer::POST('type', 'type', fn() => throw new NotExpectedAction('Missing type'));

		if(
			$data->eCustomer->notEmpty() and
			$type !== $data->eCustomer['type']
		) {

			$data->eCustomer['groups'] = [];

		}

		$data->eCustomer['type'] = $type;


		throw new \ViewAction($data);

	})
	->post('query', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');

		$data->cCustomer = \selling\CustomerLib::getFromQuery(
			POST('query'),
			$data->eFarm,
			POST('type', default: fn() => NULL),
			withCollective: POST('withCollective', 'bool', TRUE)
		);

		$data->hasNew = post_exists('new');

		throw new \ViewAction($data);

	});
?>
