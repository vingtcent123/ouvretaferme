<?php
new Page()
	->get('index', function($data) {

		\Setting::set('main\viewAsset', 'state');

		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canView');

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));

		$data->assetSummary = \asset\DepreciationLib::getSummary($data->eFinancialYear);

		throw new ViewAction($data);

	});


new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();

	if(get_exists('id') === FALSE) {
		throw new NotExpectedAction('Asset Id is required.');
	}

	$data->eAsset = \asset\AssetLib::getWithDepreciationsById(GET('id'));

})
	->get('view', function($data) {

		throw new ViewAction($data);

	})
	->get('dispose', function($data) {

		throw new ViewAction($data);

	});

?>
