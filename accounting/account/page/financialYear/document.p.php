<?php
new \account\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
	}
)
	->get('opening', function($data) {

	});

new \account\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');

		$data->eFinancialYear = \account\FinancialYearLib::getById(GET('id'))->validate('canReadDocument');
	}
)
	->get('fec', function($data) {

		$fecData = \account\FecLib::generate($data->eFinancialYear);

		throw new DataAction(
			$fecData,
			'text/txt',
			// TODO SIRET 
			$data->eFarm['id'].'FEC'.date('Ymd', strtotime($data->eFinancialYear['closeDate'])).'.txt',
		);

	})
	->get('closing', function($data) {

	});
