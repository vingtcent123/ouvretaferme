<?php

new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
	->create(function($data) {

		$eAccount = \account\AccountLib::getById(GET('account'));
		if(
			$eAccount->notEmpty() and
			\account\ClassLib::isFromClass($eAccount['class'], \account\AccountSetting::GRANT_ASSET_CLASS) === FALSE and
			\account\ClassLib::isFromClass($eAccount['class'], \account\AccountSetting::ASSET_GENERAL_CLASS) === FALSE
		) {
			$eAccount = new \account\Account();
		}

		$data->e['account'] = $eAccount;

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new ReloadAction('asset', 'Asset::created');
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

?>
