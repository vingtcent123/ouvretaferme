<?php
new \company\CompanyPage()
	->get('index', function($data) {

		$data->eFarm->validate('canManage');

		throw new ViewAction($data);

	});
?>
