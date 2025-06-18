<?php
new \accounting\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
	}
)
	->get('opening', function($data) {

	});

new \accounting\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
		$data->eFinancialYear = \accounting\FinancialYearLib::getById(GET('id'))->validate('canReadDocument');
	}
)
	->get('fec', function($data) {

		$fecData = \accounting\FecLib::generate($data->eFinancialYear);

		throw new DataAction(
			$fecData,
			'text/txt',
			$data->eCompany['siret'].'FEC'.date('Ymd', strtotime($data->eFinancialYear['closeDate'])).'.txt',
		);

	})
	->get('closing', function($data) {

	});
