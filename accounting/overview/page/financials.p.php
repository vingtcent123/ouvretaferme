<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	if(get_exists('view') and in_array(GET('view'), \farm\Farmer::model()->getPropertyEnum('viewAccountingFinancials'))) {

		$data->view = \farm\FarmerLib::setView('viewAccountingFinancials', $data->eFarm, GET('view'));

	} else {

		$data->view = $data->eFarm->getView('viewAccountingFinancials');

	}

})
	->get('/gestion', function($data) {

		switch($data->view) {

			case \farm\Farmer::BANK:
				$data->ccOperationBank = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'bank');
				$data->ccOperationCash = \overview\AnalyzeLib::getBankOperationsByMonth($data->eFinancialYear, 'cash');
				break;

			case \farm\Farmer::CHARGES:
				[$data->cOperation, $data->cAccount] = \overview\AnalyzeLib::getChargeOperationsByMonth($data->eFinancialYear);
				$data->cOperationResult = \overview\AnalyzeLib::getResultOperationsByMonth($data->eFinancialYear);
				break;

			case \farm\Farmer::INTERMEDIATE:

				$data->search = new Search([
					'financialYearComparison' => GET('financialYearComparison'),
				], GET('sort'));

				$values = \overview\SigLib::compute($data->eFinancialYear);
				$data->values = [
					$data->eFinancialYear['id'] => $values
				];

				if($data->search->get('financialYearComparison') and (int)$data->search->get('financialYearComparison') !== $data->eFinancialYear['id']) {
					$data->eFinancialYearComparison = \account\FinancialYearLib::getById($data->search->get('financialYearComparison'));
					if($data->eFinancialYearComparison->notEmpty()) {
						$data->values[$data->eFinancialYearComparison['id']] = \overview\SigLib::compute($data->eFinancialYearComparison);
					}
				} else {
					$data->eFinancialYearComparison = new \account\FinancialYear();
				}
		}

		throw new ViewAction($data, ':'.$data->view);

	});
?>
