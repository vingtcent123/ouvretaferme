<?php
new \journal\OperationPage(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

})
	->applyElement(function($data, \journal\Operation $e) {

		$e->validate('acceptDeferral');

	})
	->read('set', function($data) {

			$data->field = GET('field');

			throw new ViewAction($data);

	})
	->write('doSet', function($data) {

			$success = \journal\DeferralLib::createDeferral($data->e, $_POST);

			throw new ReloadAction('journal', $success);

	})
;

new \journal\DeferralPage(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

})
	->applyElement(function($data, \journal\Deferral $e) {

		$e->validate('acceptDelete');

	})
	->doDelete(function($data) {

		\account\LogLib::save('delete', 'Deferral', ['id' => $data->e['id']]);

		throw new ReloadAction('journal', 'Deferral::'.$data->e['type'].'.deleted');

	})
;
