<?php
new Page(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(\company\CompanySetting::BETA and in_array($data->eFarm['id'], \company\CompanySetting::ACCOUNTING_FARM_BETA) === FALSE) {
		throw new RedirectAction('/comptabilite/beta?farm='.$data->eFarm['id']);
	}

	$search = new Search([
		'accountLabel' => GET('accountLabel'),
		'financialYear' => $data->eFarm['eFinancialYear'],
	], GET('sort'));

	$data->search = clone $search;

})
	->get('/journal/grand-livre', function($data) {

		$data->cOperation = \journal\OperationLib::getAllForBook($data->search);
		$data->cAccount = \account\AccountLib::getAll();

		throw new ViewAction($data);

	});
?>
