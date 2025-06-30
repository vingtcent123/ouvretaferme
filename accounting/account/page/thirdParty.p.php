<?php
new \account\ThirdPartyPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
	}
)
	->get('index', function($data) {

		$data->search = new Search([
			'name' => GET('name'),
		], GET('sort'));

		$data->cThirdParty = account\ThirdPartyLib::getAll($data->search);
		$cOperation = \journal\OperationLib::countGroupByThirdParty();
		foreach($data->cThirdParty as &$eThirdParty) {
			$eThirdParty['operations'] = $cOperation[$eThirdParty['id']]['count'] ?? 0;
		}

		throw new ViewAction($data);

	})
	->create(function($data) {

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		throw new ViewAction($data);

	})
	->quick(['name'], ['name' => fn($data) => \account\LogLib::save('update', 'thirdParty', ['field' => 'name', 'id' => $data['e']['id']])])
	->doDelete(fn($data) => throw new ReloadAction('account', 'ThirdParty::deleted'));

new Page(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
->post('query', function($data) {

	$data->search = new Search([
		'name' => POST('query'),
	], GET('sort', default: 'name'));

	$cThirdParty = account\ThirdPartyLib::getAll($data->search);

	if(post_exists('cashflowId') === TRUE) {
		$eCashflow = \bank\CashflowLib::getById(POST('cashflowId', 'int'));
		$cThirdParty = account\ThirdPartyLib::filterByCashflow($cThirdParty, $eCashflow);
	}

	$supplierAccountLabel = account\ThirdPartyLib::getNextThirdPartyAccountLabel('supplierAccountLabel', \Setting::get('account\thirdAccountSupplierDebtClass'));
	$clientAccountLabel = account\ThirdPartyLib::getNextThirdPartyAccountLabel('clientAccountLabel', \Setting::get('account\thirdAccountClientReceivableClass'));

	// On affecte le prochain incrément automatiquement
	foreach($cThirdParty as &$eThirdParty) {
		$eThirdParty['supplierAccountLabel'] ??= $supplierAccountLabel;
		$eThirdParty['clientAccountLabel'] ??= $clientAccountLabel;
	}

	$data->cThirdParty = $cThirdParty;

	throw new \ViewAction($data);

});
?>
