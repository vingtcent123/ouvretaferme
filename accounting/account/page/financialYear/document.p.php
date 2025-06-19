<?php
new \account\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
	}
)
	->get('opening', function($data) {

	});

new \account\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
		$data->eFinancialYear = \account\FinancialYearLib::getById(GET('id'))->validate('canReadDocument');
	}
)
	->get('fec', function($data) {

		$fecData = \account\FecLib::generate($data->eFinancialYear);

		throw new DataAction(
			$fecData,
			'text/txt',
			$data->eCompany['siret'].'FEC'.date('Ymd', strtotime($data->eFinancialYear['closeDate'])).'.txt',
		);

	})
	->get('closing', function($data) {

	});
