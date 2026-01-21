<?php
new \account\FinancialYearPage(function($data) {

	$data->eFarm->validate('canManage');

})
	->create(function($data) {

		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();
		if($data->cFinancialYearOpen->count() >= 2) {
			throw new NotExpectedAction('Cannot create a new financial year as there are already '.$data->cFinancialYearOpen->count().' financial years open');
		}

		$nextDates = \account\FinancialYearLib::getNextFinancialYearDates();
		$eFinancialYear = \account\FinancialYearLib::getLastFinancialYear();
		$eFinancialYear['startDate'] = $nextDates['startDate'];
		$eFinancialYear['endDate'] = $nextDates['endDate'];
		$data->e = $eFinancialYear;

		throw new ViewAction($data);

	})
;
new \account\FinancialYearPage(
	function($data) {
		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();
		if($data->cFinancialYearOpen->count() >= 2) {
			throw new NotExpectedAction('Cannot create a new financial year as there are already '.$data->cFinancialYearOpen->count().' financial years open');
		}
	}
)
	->doCreate(function($data) {

		throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm, $data->e).'/etats-financiers?success=account\\FinancialYear::created');

	});

new \account\FinancialYearPage(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->applyElement(function($data, \account\FinancialYear $e) {

		$e->validate('canUpdate');
		$data->eOld = clone $e;
		$e['eOld'] = $data->eOld;
		$e['nOperation'] = \journal\OperationLib::countByFinancialYear($e);

	})
	->update(function($data) {


		throw new ViewAction($data);

	})
	->doUpdate(function($data) {

		throw new ReloadAction('account', 'FinancialYear::updated');

	})
	->read('open', function($data) {

		$data->e->validate('acceptOpen');

		$data->eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->e);

		$data->cOperation = \account\OpeningLib::getRetainedEarnings($data->eFinancialYearPrevious, $data->e, '');

		$data->cOperationResult = \account\OpeningLib::getResultOperation($data->eFinancialYearPrevious, $data->e, '');

		[$data->cJournalCode, $data->ccOperationReversed] = \account\OpeningLib::getReversableData($data->eFinancialYearPrevious, $data->e, '');

		throw new ViewAction($data);

	})
	->write('doOpen', function($data) {

		$data->e->validate('acceptOpen');

		$data->eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->e);
		if($data->eFinancialYearPrevious->notEmpty() and $data->eFinancialYearPrevious->isClosed() === FALSE) {
			throw new NotExpectedAction('Unable to open a financialYear if the previous one is not closed');
		}

		\account\FinancialYearLib::openFinancialYear($data->e, POST('journalCode', 'array'));

		// Generate PDFs
		\account\FinancialYearDocumentLib::regenerateAll($data->eFarm, $data->e, [\account\FinancialYearDocumentLib::OPENING, \account\FinancialYearDocumentLib::OPENING_DETAILED]);

		throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/?success=account\\FinancialYear::open');

	});

new \account\FinancialYearPage(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->applyElement(function($data, \account\FinancialYear $e) {

		$e->validate('acceptClose');
		$data->eOld = clone $e;

	})
	->read('close', function($data) {

		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();

		$data->accountsToSettle = [
			'farmersAccount' => \journal\OperationLib::getFarmersAccountValue($data->e),
			'waitingAccounts' => \journal\OperationLib::getWaitingAccountValues($data->e),
		];

		$data->cDeferral = \journal\DeferralLib::getDeferralsForOperations();

		$data->cAssetGrant = \asset\AssetLib::getGrantsByFinancialYear($data->e);
		\asset\AmortizationLib::simulateGrants($data->e, $data->cAssetGrant);
		foreach($data->cAssetGrant as &$eAsset) {
			$eAsset['table'] = \asset\AmortizationLib::computeTable($eAsset);
		}

		$data->cAsset = \asset\AssetLib::getAssetsByFinancialYear($data->e);
		\asset\AmortizationLib::simulate($data->e, $data->cAsset);

		foreach($data->cAsset as &$eAsset) {
			$eAsset['table'] = \asset\AmortizationLib::computeTable($eAsset);
		}

		$data->e['cImport'] = \account\ImportLib::getByFinancialYear($data->e);

		throw new ViewAction($data);
	})
	->write('doClose', function($data) {

		$data->e->validate('acceptClose');

		\account\FinancialYearLib::closeFinancialYear($data->eFarm, $data->e);

		throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/?success=account\\FinancialYear::closed');
	})
	;

new \account\FinancialYearPage(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->write('doReopen', function($data) {

		$data->e->validate('isClosed');

		\account\FinancialYearLib::reopen($data->e);

		throw new ReloadAction('account', 'FinancialYear::reopen');
	})
	->write('doReclose', function($data) {

		$data->e->validate('acceptReClose');

		\account\FinancialYearLib::reclose($data->e);

		throw new ReloadAction('account', 'FinancialYear::reclose');
	});

new \account\FinancialYearPage(function($data) {
	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->applyElement(function($data, \account\FinancialYear $e) {

		$e['nOperation'] = \journal\OperationLib::countByFinancialYear($e);

	})
	->doDelete(function($data) {

		throw new ReloadAction('account', 'FinancialYear::deleted');

	})
;
?>
