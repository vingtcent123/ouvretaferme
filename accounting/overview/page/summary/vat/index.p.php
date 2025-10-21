<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

	$data->search = new Search([
		'view' => GET('view', 'string', \overview\BalanceSheetLib::VIEW_BASIC),
		'financialYearComparison' => GET('financialYearComparison'),
	], GET('sort'));


})
	->get('index', function($data) {

		if($data->eFinancialYear['status'] === \account\FinancialYear::CLOSE) {
			// TODO DEV throw new RedirectAction(\company\CompanyUi::urlSummary($data->eFarm).'/vat/history');
		}

		if($data->eFinancialYear['hasVat'] === FALSE) {
			throw new ViewAction($data, ':noVat');
		}

		$tab = GET('tab');
		if(in_array($tab, ['journal-sell', 'journal-buy', 'check', 'cerfa']) === FALSE) {
			$tab = NULL;
		}
		$data->tab = $tab;

		// Ne pas ouvrir le bloc de recherche
		$search = new Search();
		$search->set('financialYear', $data->eFinancialYear);

		$data->eFinancialYear['lastPeriod'] = \journal\VatDeclarationLib::calculateLastPeriod($data->eFinancialYear);

		$search->set('maxDate', $data->eFinancialYear['lastPeriod']['end'] ?? $data->eFinancialYear['startDate']);

		switch($tab) {

			case 'journal-buy':
			case 'journal-sell':
				$type = mb_substr($tab, mb_strlen('journal') + 1);
				$search->buildSort(['date' => SORT_ASC]);
				$data->cOperation = \journal\OperationLib::getAllForVatJournal($type, $search, TRUE, NULL);
				break;

			case 'check':
				$data->check = \overview\VatLib::getForCheck($search);
				break;

			case 'cerfa':
				$data->cerfa = \overview\VatLib::getVatDataDeclaration($data->eFinancialYear, $search);
		}

		$data->currentVatPeriod = \journal\VatDeclarationLib::calculateCurrentPeriod($data->eFinancialYear);

		throw new ViewAction($data);

	});
?>
