<?php
new Page()
	->get('index', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->canUpdate = user\SignUpLib::canUpdate($data->eUserOnline);

		throw new ViewAction($data);

	});
?>
