<?php
new \account\ImportPage()
	->get('import', function($data) {

		$data->cImport = \account\ImportLib::getImports();
		$data->eImport = \account\ImportLib::currentOpenImport();

		$nOperationByFinancialYear = \journal\OperationLib::countByFinancialYears($data->eFarm['cFinancialYear']);
		foreach($data->eFarm['cFinancialYear'] as $key => $eFinancialYear) {
			$data->eFarm['cFinancialYear'][$key]['nOperation'] = $nOperationByFinancialYear[$eFinancialYear['id']]['count'] ?? 0;
		}

		$data->cJournalCode = \journal\JournalCodeLib::getAll();
		$data->cAccount = \account\AccountLib::getAll();
		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarm, FALSE, TRUE);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		\company\CompanyCronLib::addConfiguration($data->eFarm, \company\CompanyCronLib::FEC_IMPORT, \company\CompanyCron::WAITING, $data->e['id']);

		throw new ReloadAction('account', 'Import::created');

	})
	->doUpdateProperties('doCancel', ['status'], fn() => throw new ReloadAction('account', 'Import::cancelled'), validate: ['acceptCancel'])
	->write('doValidateRules', function($data) {

		$isActionRequired = \account\ImportLib::validateRules($data->e);

		if($isActionRequired) {
			throw new FailAction('account\Import::updated.feedbackNeeded');
		}
		throw new ReloadAction('account', 'Import::updated');

	}, validate: ['acceptUpdate'])
	->write('doUpdateRuleValue', function($data) {

		\account\ImportLib::updateRuleValue($data->e, $_POST);

		throw new ReloadAction();

	}, validate: ['acceptUdpate']);

new \account\FinancialYearPage()
	->get('view', function($data) {

		$data->fecInfo = \account\FecLib::checkDataForFec($data->eFarm, $data->eFarm['eFinancialYear']);
		$data->fecInfo['cJournalCode'] = \journal\JournalCodeLib::getAll();

		$data->nOperationByFinancialYear = \journal\OperationLib::countByFinancialYears($data->eFarm['cFinancialYear']);

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
