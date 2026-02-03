<?php
new \asset\AssetPage(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(\company\CompanySetting::BETA and in_array($data->eFarm['id'], \company\CompanySetting::ACCOUNTING_FARM_BETA) === FALSE) {
		throw new RedirectAction('/comptabilite/beta?farm='.$data->eFarm['id']);
	}

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

		$data->cOperation = \journal\OperationLib::getByIdsForAsset(GET('ids', 'array'));

		$data->e['account'] = $eAccount;

		// Références de durées
		$data->cAmortizationDuration = \company\AmortizationDurationLib::getAll();

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		if(\asset\AssetLib::isAsset($data->e['accountLabel'])) {
			throw new ReloadAction('asset', 'Asset::asset.created');
		} elseif(\asset\AssetLib::isGrant($data->e['accountLabel'])) {
			throw new ReloadAction('asset', 'Asset::grant.created');
		}

	})
	->write('doAttach', function($data) {

		$operations = explode(',', POST('operations'));

		$cOperation = \journal\OperationLib::getForAssetAttach($operations);

		if($cOperation->notEmpty()) {
			\asset\AssetLib::attach($data->e, $cOperation);
		}

		throw new ReloadAction('asset', 'Asset::attached');

	}, validate: ['acceptAttach'])
	->update(function($data) {

		// Références de durées
		$data->cAmortizationDuration = \company\AmortizationDurationLib::getAll();

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
	->doDelete(function($data) {

		throw new ReloadAction('asset', 'Asset::asset.deleted');

	})
;

new \asset\AssetPage()
	->read('/immobilisation/{id}/', function($data) {

		$data->e->validate('canView');

		$data->e['table'] = \asset\AmortizationLib::computeTable($data->e);

		$data->e['cOperation'] = \journal\OperationLib::getByAsset($data->e);

		throw new ViewAction($data);

	});

new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();

	if(get_exists('id') === FALSE and post_exists('id') === FALSE) {
		throw new NotExpectedAction('Asset Id is required.');
	}

	$data->eAsset = \asset\AssetLib::getWithDepreciationsById(REQUEST('id'))->validate('canView');

})
	->get('dispose', function($data) {

		throw new ViewAction($data);

	})
	->post('doDispose', function($data) {

		\asset\AssetLib::dispose($data->eAsset, $_POST);

		throw new ReloadAction('asset', 'Asset::disposed');

	});

new Page()
	->post('getRecommendedDuration', function($data) {

		$data->cAmortizationDuration = \company\AmortizationDurationLib::getAll();

		throw new \ViewAction($data);

	})
	->get('attach', function($data) {

		$data->cOperation = \journal\OperationLib::getByIdsForAsset(GET('ids', 'array'));

		$data->cAssetWaiting = \asset\AssetLib::getNotAssigned();

		throw new ViewAction($data);

	})
	->post('unattach', function($data) {

		$eOperation = \journal\OperationLib::getById(POST('id'));

		if($eOperation->isNotLinkedToAsset()) {
			throw new NotExpectedAction('Unable to dissociate an operation not linked to asset');
		}

		\journal\Operation::model()
			->whereHash($eOperation['hash'])
			->update(['asset' => NULL]);

		throw new ReloadAction('asset', 'Asset::unattached');

	})
;
?>
