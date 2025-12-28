<?php
new \account\FinancialYearPage()
	->get('view', function($data) {

		$data->fecInfo = \account\FecLib::checkDataForFec($data->eFarm, $data->eFarm['eFinancialYear']);
		$data->fecInfo['cJournalCode'] = \journal\JournalCodeLib::getAll();


		throw new ViewAction($data);

	})
	->get('download', function($data) {

		$eFinancialYear = \account\FinancialYearLib::getById(GET('financialYear'));

		$startDate = $eFinancialYear['startDate'];
		$endDate = $eFinancialYear['endDate'];

		if(get_exists('startDate') and get_exists('endDate')) {

			$startDate = GET('startDate');
			$endDate = GET('endDate');

			if(\util\DateLib::isValid($startDate) === FALSE) {
				$startDate = $eFinancialYear['startDate'];
			}
			if(\util\DateLib::isValid($endDate) === FALSE) {
				$endDate = $eFinancialYear['endDate'];
			}
		}

		$fecData = \account\FecLib::generate($eFinancialYear, $startDate, $endDate);

		$filename = \account\FecLib::getFilename($data->eFarm, $eFinancialYear);

		throw new DataAction($fecData, 'text/txt', $filename);

	});
