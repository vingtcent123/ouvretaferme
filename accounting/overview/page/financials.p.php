<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$currentPage = self::getName();

	if($currentPage === 'index') {

		$data->selectedView = $data->eFarm->getView('viewAnalyzeAccountingFinancials');

	} else {

		$data->selectedView = $currentPage;
		\farm\FarmerLib::setView('viewAnalyzeAccountingFinancials', $data->eFarm, $currentPage);
	}

})
	->get(['index', 'bank', 'charges', 'result'], function($data) {

		switch($data->selectedView) {

			case \farm\Farmer::BANK:
				$data->cOperationBank = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'bank');
				$data->cOperationCash = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'cash');
				throw new ViewAction($data, ':bank');

			case \farm\Farmer::CHARGES:
				[$data->cOperation, $data->cAccount] = \overview\AnalyzeLib::getChargeOperationsByMonth($data->eFinancialYear);
				throw new ViewAction($data, ':charges');

			case \farm\Farmer::RESULTS:
				$data->cOperation = \overview\AnalyzeLib::getResultOperationsByMonth($data->eFinancialYear);
				[$data->result, $data->cAccount] = \overview\AnalyzeLib::getResult($data->eFinancialYear);
				throw new ViewAction($data, ':results');
		}

	});
?>
