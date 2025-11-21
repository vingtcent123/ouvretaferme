<?php
new Page()
	->get('index', function($data) {

		$data->eFarm->validate('canManage');

		$data->cFinancialYear = \account\FinancialYearLib::getAll();
		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();

		throw new ViewAction($data);

	});

new \account\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm->validate('canManage');
	}
)
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
		\user\ConnectionLib::checkLogged();
		$data->eFarm->validate('canManage');
		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();
		if($data->cFinancialYearOpen->count() >= 2) {
			throw new NotExpectedAction('Cannot create a new financial year as there are already '.$data->cFinancialYearOpen->count().' financial years open');
		}
	}
)
	->doCreate(function($data) {

		throw new ReloadAction('account', 'FinancialYear::created');

	});

new \account\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm->validate('canManage');
	})
	->applyElement(function($data, \account\FinancialYear $e) {

		$e->validate('canUpdate');
		$data->eOld = clone $e;

	})
	->update(function($data) {

		throw new ViewAction($data);

	})
	->doUpdate(function($data) {

		\account\FinancialYearLib::cbUpdate($data->e, $data->eOld);
		throw new ReloadAction('account', 'FinancialYear::updated');

	})
	->read('open', function($data) {

		$data->e->validate('acceptOpen');

		$data->eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($data->e);

		$data->cOperation = \account\OpeningLib::getRetainedEarnings($data->eFinancialYearPrevious, $data->e, '');

		$data->eOperationResult = \account\OpeningLib::getResultOperation($data->eFinancialYearPrevious, $data->e, '');

		list($data->cJournalCode, $data->ccOperationReversed) = \account\OpeningLib::getReversableData($data->eFinancialYearPrevious, $data->e, '');

		throw new ViewAction($data);

	})
	->write('doOpen', function($data) {

		$data->e->validate('acceptOpen');

		\account\FinancialYearLib::openFinancialYear($data->e, POST('journalCode', 'array'));

		throw new RedirectAction(\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/?success=account:FinancialYear::open');

	})
	->read('close', function($data) {

		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();
		$search = new Search(['financialYear' => $data->e]);

		$data->cOperationToDefer = \journal\OperationLib::getAllChargesForClosing($search);
		\journal\DeferralLib::getDeferralsForOperations($data->cOperationToDefer);

		// Stock enregistré de cet exercice comptable + celui de l'exercice précédent non reporté
		$data->cStock = \journal\StockLib::getAllForFinancialYear($data->e);

		$data->cAssetGrant = \asset\AssetLib::getGrantsByFinancialYear($data->e);
		$data->cAsset = \asset\AssetLib::getAssetsByFinancialYear($data->e);
		\asset\AmortizationLib::simulate($data->e, $data->cAsset);
		foreach($data->cAsset as &$eAsset) {
			$eAsset['table'] = \asset\AmortizationLib::computeTable($eAsset);
		}

		throw new ViewAction($data);
	})
	->write('doClose', function($data) {

		$data->e->validate('acceptClose');

		\account\FinancialYearLib::closeFinancialYear($data->e, $_POST);

		throw new RedirectAction(\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/?success=account:FinancialYear::closed');
	})
	;

new \account\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm->validate('canManage');
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
?>
