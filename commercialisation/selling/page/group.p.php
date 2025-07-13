<?php
new \selling\GroupPage(function($data) {

		\user\ConnectionLib::checkLogged();

	})
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \selling\Group([
			'farm' => $data->eFarm,
		]);

	})
	->create()
	->doCreate(fn($data) => throw new ViewAction($data));

new \selling\GroupPage()
	->update()
	->doUpdate(fn($data) => throw new ViewAction($data))
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\selling\GroupLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	})
	->doDelete(fn($data) => throw new ViewAction($data));

new Page()
	->post('query', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');
		$type = \selling\Group::POST('type', 'type');

		$data->cGroup = \selling\GroupLib::getFromQuery(POST('query'), $data->eFarm, $type);

		throw new \ViewAction($data);

	})
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cGroup = \selling\GroupLib::getForManage($data->eFarm);


		throw new ViewAction($data);

	});
?>
