<?php
new \selling\CustomerGroupPage(function($data) {

		\user\ConnectionLib::checkLogged();

	})
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \selling\CustomerGroup([
			'farm' => $data->eFarm,
		]);

	})
	->create()
	->doCreate(fn($data) => throw new ReloadAction('selling', 'CustomerGroup::created'));

new \selling\CustomerGroupPage()
	->read('get', function($data) {

		$data->e['farm'] = \farm\FarmLib::getById($data->e['farm']);

		$data->cGrid = \selling\GridLib::getByGroup($data->e);
		$data->cCustomer = \selling\CustomerLib::getByGroup($data->e);
		$data->cCustomerGroup = \selling\CustomerGroupLib::getByFarm($data->e['farm']);

		$data->eFarm = $data->e['farm'];

		throw new ViewAction($data);

	})
	->update()
	->doUpdate(fn($data) => throw new ViewAction($data))
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\selling\CustomerGroupLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	})
	->doDelete(fn($data) => throw new ViewAction($data));

new Page()
	->post('query', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');
		$type = \selling\CustomerGroup::POST('type', 'type');

		$data->cCustomerGroup = \selling\CustomerGroupLib::getFromQuery(POST('query'), $data->eFarm, $type);

		throw new \ViewAction($data);

	})
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cCustomerGroup = \selling\CustomerGroupLib::getForManage($data->eFarm);


		throw new ViewAction($data);

	});
?>
