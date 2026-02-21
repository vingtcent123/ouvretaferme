<?php
new \account\ThirdPartyPage()
	->get('index', function($data) {

		$data->search = new Search([
			'name' => GET('name'),
		], GET('sort', 'string', 'name'));

		$data->cThirdParty = account\ThirdPartyLib::getAll($data->search);
		$financialYearIds = $data->eFarm['cFinancialYear']->getKeys();

		$cOperation = \journal\OperationLib::countGroupByThirdParty(array_slice($financialYearIds, 0, 2));
		$cOperationAll = \journal\OperationLib::countByThirdParty();
		$cAccountByThirdParty = \account\AccountLib::countByThirdParty();

		foreach($data->cThirdParty as &$eThirdParty) {

			$eThirdParty['operations'] = [];

			foreach($financialYearIds as $financialYearId) {
				$eThirdParty['operations'][$financialYearId] = $cOperation[$eThirdParty['id']][$financialYearId]['count'] ?? 0;
			}
			$eThirdParty['operations']['all'] = $cOperationAll[$eThirdParty['id']] ?? 0;

			$eThirdParty['accounts'] = $cAccountByThirdParty[$eThirdParty['id']]['count'] ?? 0;
		}

		throw new ViewAction($data);

	})
	->create(function($data) {

		$data->e['farm'] = $data->eFarm;

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		throw new ViewAction($data);

	})
	->update(function($data) {

		$data->e['farm'] = $data->eFarm;

		throw new ViewAction($data);

	})
	->doUpdate(function($data) {

		throw throw new ReloadAction('account', 'ThirdParty::updated');

	})
	->quick(['name', 'customer'])
	->doDelete(fn($data) => throw new ReloadAction('account', 'ThirdParty::deleted'));

new Page()
->post('query', function($data) {

	$data->search = new Search([
		'name' => POST('query'),
	], GET('sort', default: 'name'));

	$cThirdParty = account\ThirdPartyLib::getAll($data->search);

	if(post_exists('cashflowId') === TRUE) {
		$eCashflow = \bank\CashflowLib::getById(POST('cashflowId', 'int'));
		$cThirdParty = account\ThirdPartyLib::filterByCashflow($cThirdParty, $eCashflow);
	}


	$data->cThirdParty = $cThirdParty;

	throw new \ViewAction($data);

});
?>
