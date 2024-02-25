<?php
(new Page())
	->get('index', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->canUpdate = user\SignUpLib::canUpdate($data->eUserOnline);
		$data->nCustomer = \selling\CustomerLib::countByUser($data->eUserOnline);

		throw new ViewAction($data);

	});
?>
