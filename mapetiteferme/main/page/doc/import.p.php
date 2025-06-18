<?php
(new Page(function($data) {

		$cEmployee = \company\EmployeeLib::getOnline();

		$data->eCompany = $cEmployee->notEmpty() ? $cEmployee->first()['company'] : new \company\Company();

	}))
	->get('index', fn($data) => throw new ViewAction($data));
?>
