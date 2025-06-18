<?php
new \journal\ThirdPartyPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = REQUEST('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
	}
)
	->get('index', function($data) {

		$data->search = new Search([
			'name' => GET('name'),
		], GET('sort'));

		$data->cThirdParty = \journal\ThirdPartyLib::getAll($data->search);
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
	->quick(['name']);

new Page(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canWrite');
})
->post('query', function($data) {

	$data->search = new Search([
		'name' => POST('query'),
	], GET('sort', default: 'name'));

	$cThirdParty = \journal\ThirdPartyLib::getAll($data->search);

	if(post_exists('cashflowId') === TRUE) {
		$eCashflow = \bank\CashflowLib::getById(POST('cashflowId', 'int'));
		$cThirdParty = \journal\ThirdPartyLib::filterByCashflow($cThirdParty, $eCashflow);
	}

	$supplierAccountLabel = \journal\ThirdPartyLib::getNextThirdPartyAccountLabel('supplierAccountLabel', \Setting::get('accounting\thirdAccountSupplierDebtClass'));
	$clientAccountLabel = \journal\ThirdPartyLib::getNextThirdPartyAccountLabel('clientAccountLabel', \Setting::get('accounting\thirdAccountClientReceivableClass'));

	// On affecte le prochain incrément automatiquement
	foreach($cThirdParty as &$eThirdParty) {
		$eThirdParty['supplierAccountLabel'] ??= $supplierAccountLabel;
		$eThirdParty['clientAccountLabel'] ??= $clientAccountLabel;
	}

	$data->cThirdParty = $cThirdParty;

	throw new \ViewAction($data);

})
?>
