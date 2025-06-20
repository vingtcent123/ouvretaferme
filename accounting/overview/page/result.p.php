<?php
new Page(
	function ($data) {

		$data->eFarm = \farm\FarmLib::getById(REQUEST('farm'))->validate('canManage');
		// TODO Récupérer et sauvegarder dynamiquement
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		\account\FinancialYearLib::checkHasAtLeastOne($data->cFinancialYear, $data->eFarm);

	}
)
	->get('index', function($data) {

		Setting::set('main\viewAnalyze', 'result');

		$data->cOperation = \overview\AnalyzeLib::getResultOperationsByMonth($data->eFinancialYear);
		[$data->result, $data->cAccount] = \overview\AnalyzeLib::getResult($data->eFinancialYear);

		throw new ViewAction($data);

	});

?>
