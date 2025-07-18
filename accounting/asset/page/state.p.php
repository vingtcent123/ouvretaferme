<?php
new Page()
	->get('index', function($data) {

		$data->eFarm->validate('canManage');

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$data->assetSummary = \asset\DepreciationLib::getSummary($data->eFinancialYear);

		throw new ViewAction($data);

	});


new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();
	$data->eFarm->validate('canManage');

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
