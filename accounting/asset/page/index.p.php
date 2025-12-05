<?php
new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
	->create(function($data) {

		$eAccount = \account\AccountLib::getById(GET('account'));
		if(
			$eAccount->notEmpty() and
			\account\AccountLabelLib::isFromClass($eAccount['class'], \account\AccountSetting::GRANT_ASSET_CLASS) === FALSE and
			\account\AccountLabelLib::isFromClass($eAccount['class'], \account\AccountSetting::ASSET_GENERAL_CLASS) === FALSE
		) {
			$eAccount = new \account\Account();
		}

		$data->e['account'] = $eAccount;

		// Références de durées
		$data->e['cAmortizationDuration'] = \company\AmortizationDurationLib::getAll();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		if(\asset\AssetLib::isAsset($data->e['accountLabel'])) {
			throw new ReloadAction('asset', 'Asset::asset.created');
		} elseif(\asset\AssetLib::isGrant($data->e['accountLabel'])) {
			throw new ReloadAction('asset', 'Asset::grant.created');
		}

	})
	->update(function($data) {

		// Références de durées
		$data->e['cAmortizationDuration'] = \company\AmortizationDurationLib::getAll();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		throw new ViewAction($data);

	})
	->doUpdate(function($data) {

		if(\asset\AssetLib::isAsset($data->e['accountLabel'])) {
			throw new ReloadAction('asset', 'Asset::asset.updated');
		} elseif(\asset\AssetLib::isGrant($data->e['accountLabel'])) {
			throw new ReloadAction('asset', 'Asset::grant.updated');
		}

	})
	->post('query', function($data) {

		$search = new Search([
			'account' => \account\AccountLib::getById(POST('account')),
			'accountLabel' => POST('accountLabel'),
			'query' => POST('query')
		]);

		$data->cAsset = \asset\AssetLib::getAll($search);

		throw new \ViewAction($data);

	})
;

new \asset\AssetPage(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
	\company\CompanyLib::connectSpecificDatabaseAndServer($data->eFarm);

	$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	$data->cFinancialYear = \account\FinancialYearLib::getAll();

})
	->read('/immobilisation/{id}/', function($data) {

		$data->e->validate('canView');

		$data->e['table'] = \asset\AmortizationLib::computeTable($data->e);

		throw new ViewAction($data);

	});

new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();

	if(get_exists('id') === FALSE and post_exists('id') === FALSE) {
		throw new NotExpectedAction('Asset Id is required.');
	}

	$data->eFarm->validate('canManage');

	$data->eAsset = \asset\AssetLib::getWithDepreciationsById(REQUEST('id'))->validate('canView');

})
	->get('dispose', function($data) {

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		throw new ViewAction($data);

	})
	->post('doDispose', function($data) {

		\asset\AssetLib::dispose($data->eAsset, $_POST);

		throw new ReloadAction('asset', 'Asset::disposed');

	});

?>
