<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	$search = new Search([
		'startDate' => GET('startDate'),
		'endDate' => GET('endDate'),
		'precision' => GET('precision'),
		'summary' => GET('summary'),
		'accountLabel' => GET('accountLabel'),
	], GET('sort'));

	$data->search = clone $search;

})
	->get('index', function($data) {

		$data->eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->eFarm['eFinancialYear']);
		$hasPrevious = $data->eFinancialYearPrevious->notEmpty();

		$tab = GET('tab');

		if($data->eFarm['eFinancialYear']->isCashAccounting() or in_array($tab, ['customer', 'supplier']) === FALSE) {
			$tab = NULL;
		}

		if($tab !== NULL) {

			$data->cOperation = \journal\OperationLib::getByThirdParty($data->eFarm['eFinancialYear'], $tab);

		} else { // balance générale

			// Comparaison des mêmes périodes entre année N et année N-1 => Vérifications et calculs
			$searchCurrent = clone $data->search;
			$searchPrevious = clone $data->search;

			if($data->search->get('startDate') !== '') {

				$startDate = $data->search->get('startDate');

				if($startDate >= $data->eFarm['eFinancialYear']['startDate'] and $startDate <= $data->eFarm['eFinancialYear']['endDate']) {

					$searchPrevious->set('startDate', date('Y-m-d', strtotime($startDate.' - 1 YEAR')));

				} else if($hasPrevious and $startDate >= $data->eFinancialYearPrevious['startDate'] and $startDate <= $data->eFinancialYearPrevious['endDate']) {

					$searchCurrent->set('startDate', date('Y-m-d', strtotime($startDate.' + 1 YEAR')));

				} else {

					$searchCurrent->set('startDate', '');
					$searchPrevious->set('startDate', '');
					$data->search->set('startDate', '');

				}
			}

			if($data->search->get('endDate') !== '') {

				$endDate = $data->search->get('endDate');

				if($endDate >= $data->eFarm['eFinancialYear']['startDate'] and $endDate <= $data->eFarm['eFinancialYear']['endDate']) {

					$searchPrevious->set('endDate', date('Y-m-d', strtotime($endDate.' - 1 YEAR')));

				} else if($hasPrevious and $endDate >= $data->eFinancialYearPrevious['startDate'] and $endDate <= $data->eFinancialYearPrevious['endDate']) {

					$searchCurrent->set('endDate', date('Y-m-d', strtotime($endDate.' + 1 YEAR')));

				} else {

					$searchCurrent->set('endDate', '');
					$searchPrevious->set('endDate', '');
					$data->search->set('endDate', '');

				}
			}

			$data->trialBalanceData = \journal\TrialBalanceLib::extractByAccounts($searchCurrent, $data->eFarm['eFinancialYear']);

			$data->searches = ['current' => $searchCurrent];

			if($hasPrevious) {

				$data->trialBalancePreviousData = \journal\TrialBalanceLib::extractByAccounts($searchPrevious, $data->eFinancialYearPrevious);
				$data->searches['previous'] = $searchPrevious;

			} else {

				$data->trialBalancePreviousData = [];

			}

		}

		$data->tab = $tab;

		throw new ViewAction($data);

	});
