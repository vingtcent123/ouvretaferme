<?php
new Page()
	->get('/configuration/accounting', function($data) {

		$data->eFarm->validate('canManage');

		throw new ViewAction($data);

	});
?>
