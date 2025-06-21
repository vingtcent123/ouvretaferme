<?php
new Page(
	function ($data) {

		$data->eFarm->validate('canManage');
		// TODO Récupérer et sauvegarder dynamiquement
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		\account\FinancialYearLib::checkHasAtLeastOne($data->cFinancialYear, $data->eFarm);

	}
)
	->get('index', function($data) {

		Setting::set('main\viewAnalyze', 'charges');

		[$data->cOperation, $data->cAccount] = \overview\AnalyzeLib::getChargeOperationsByMonth($data->eFinancialYear);

		throw new ViewAction($data);

	});
