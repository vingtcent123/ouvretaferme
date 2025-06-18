<?php
new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();

	if(get_exists('id') === FALSE and post_exists('id') === FALSE) {
		throw new NotExpectedAction('Asset Id is required.');
	}

	$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canView');

	$data->eAsset = \asset\AssetLib::getWithDepreciationsById(REQUEST('id'));

})
	->get('view', function($data) {

		throw new ViewAction($data);

	});

new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();

	if(get_exists('id') === FALSE and post_exists('id') === FALSE) {
		throw new NotExpectedAction('Asset Id is required.');
	}

	$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canView');

	$data->eAsset = \asset\AssetLib::getWithDepreciationsById(REQUEST('id'))->validate('canView');

})
	->get('dispose', function($data) {

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));

		throw new ViewAction($data);

	})
	->post('doDispose', function($data) {

		\asset\AssetLib::dispose($data->eAsset, $_POST);

		throw new ReloadAction('asset', 'Asset::disposed');

	});
