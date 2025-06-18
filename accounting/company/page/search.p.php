<?php
(new Page())
	->post('query', function($data) {

		$data->cCompany = \company\CompanyLib::getFromQuery(POST('query'));

		throw new \ViewAction($data);

	});
?>