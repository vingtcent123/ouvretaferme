<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	if(strpos(LIME_PAGE_REQUESTED, ':') !== FALSE) {
		$currentPage = substr(LIME_PAGE_REQUESTED, strpos(LIME_PAGE_REQUESTED, ':') + 1);
	} else {
		$currentPage = 'index';
	}

	if($currentPage === 'index') {

		$data->selectedView = $data->eFarm->getView('viewAccountingFinancials');

	} else {

		$data->selectedView = substr($currentPage, strpos($currentPage, ':'));
		\farm\FarmerLib::setView('viewAccountingFinancials', $data->eFarm, $currentPage);

	}

})
	->get(['index', 'bank', 'charges', 'results'], function($data) {

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
				throw new ViewAction($data, ':results');
		}

	});
?>
