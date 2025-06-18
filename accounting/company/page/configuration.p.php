<?php
new \company\CompanyPage()
	->get('index', function($data) {

		$data->eCompany->validate('canWrite');

		throw new ViewAction($data);

	});
?>
