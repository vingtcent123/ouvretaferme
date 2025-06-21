<?php
new Page()
	->get('index', function($data) {

		\Setting::set('main\viewAsset', 'state');

		$data->eFarm->validate('canManage');
		// TODO Récupérer et sauvegarder dynamiquement
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

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
