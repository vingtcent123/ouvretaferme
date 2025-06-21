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

		Setting::set('main\viewAnalyze', 'bank');

		$data->cOperationBank = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'bank');
		$data->cOperationCash = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'cash');

		throw new ViewAction($data);

	});
