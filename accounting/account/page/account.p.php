<?php
new Page(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
->get('index', function($data) {

	$data->search = new Search([
		'class' => GET('class'),
		'description' => GET('description'),
		'vatFilter' => GET('vatFilter', 'bool', FALSE),
		'customFilter' => GET('customFilter', 'bool', FALSE),
	]);
	$data->cAccount = \account\AccountLib::getAll(search: $data->search);

	$cOperation = \journal\OperationLib::countByAccounts($data->cAccount);
	foreach($data->cAccount as &$eAccount) {
		$eAccount['nOperation'] = $cOperation->offsetExists($eAccount['id']) === TRUE ? $cOperation[$eAccount['id']]['count'] : 0;
	}

	$cProductPro = \selling\Product::model()
		->select(['proAccount', 'count' => new Sql('COUNT(*)')])
		->whereFarm($data->eFarm)
		->whereStatus('!=', \selling\Product::INACTIVE)
		->whereProAccount('!=', new \account\Account())
		->group('proAccount')
		->getCollection(NULL, NULL, 'proAccount');

	$cProductPrivate = \selling\Product::model()
		->select(['privateAccount', 'count' => new Sql('COUNT(*)')])
		->whereFarm($data->eFarm)
		->whereStatus('!=', \selling\Product::INACTIVE)
		->wherePrivateAccount('!=', new \account\Account())
		->group('privateAccount')
		->getCollection(NULL, NULL, 'privateAccount');

	foreach($data->cAccount as &$eAccount) {

		$eAccount['nProductPro'] = 0;
		$eAccount['nProductPrivate'] = 0;

		if($cProductPro->offsetExists($eAccount['id'])) {
			$eAccount['nProductPro'] += $cProductPro->offsetGet($eAccount['id'])['count'];
		}

		if($cProductPrivate->offsetExists($eAccount['id'])) {
			$eAccount['nProductPrivate'] += $cProductPrivate->offsetGet($eAccount['id'])['count'];
		}

	}

	throw new ViewAction($data);

})
->post('query', function($data) {

	$query = POST('query');
	$thirdParty = POST('thirdParty', '?int');
	$accountsAlreadyUsed = POST('accountAlready', 'array', []);
	$stock = POST('stock', '?int');
	
	$data->search = new Search([
		'classPrefix' => GET('classPrefix'),
		'classPrefixes' => GET('classPrefixes', 'array')
	]);

	if($stock) {
		$eAccountStock = \account\AccountLib::getById($stock);
		if($eAccountStock->notEmpty()) {
			$data->search->set('stock', $eAccountStock);
		}
	}
	if(get_exists('class')) {
		$data->search->set('class', GET('class', 'array'));
	}
	$data->search->set('visible', TRUE);

	$data->cAccount = \account\AccountLib::getAll(query: $query, search: $data->search);

	$data->cAccount = \account\AccountLib::orderAccounts($data->cAccount, $thirdParty, $accountsAlreadyUsed);

	throw new \ViewAction($data);

})
->post('queryLabel', function($data) {

	$query = POST('query');
	$eThirdParty = \account\ThirdPartyLib::getById(POST('thirdParty', '?int'));
	$eAccount = \account\AccountLib::getById(POST('account', '?int'));

	$labels = \journal\OperationLib::getLabels($query, $eThirdParty, $eAccount);

	if($eAccount->notEmpty()) {

		$accountClass = str_pad($eAccount['class'], 8, '0');

		if($eAccount->exists() === TRUE and in_array($accountClass, $labels) === FALSE) {
			$labels[] = $accountClass;
		}

		if($eThirdParty['clientAccountLabel'] !== NULL and \account\AccountLabelLib::isFromClass($eThirdParty['clientAccountLabel'], $eAccount['class'])) {
			$labels[] = $eThirdParty['clientAccountLabel'];
		}
		if($eThirdParty['supplierAccountLabel'] !== NULL and \account\AccountLabelLib::isFromClass($eThirdParty['supplierAccountLabel'], $eAccount['class'])) {
			$labels[] = $eThirdParty['supplierAccountLabel'];
		}
	}

	if(mb_strlen($query) > 0) {
		$accountClass = str_pad(post('query'), 8, '0');
		if(in_array($accountClass, $labels) === FALSE) {
			array_unshift($labels, $accountClass);
		}
	}
	$data->labels = $labels;
	throw new \ViewAction($data);

});

new \account\AccountPage(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
	->applyElement(function($data, \account\Account $e) {
		$e['cJournalCode'] = \journal\JournalCodeLib::getAll();
	})
	->quick(['description', 'journalCode'], validate: ['canQuickUpdate'])
	->create(function($data) {

		throw new ViewAction($data);

	})
	->post('doCreate', function($data) {

		$fw = new FailWatch();

		\account\AccountLib::createCustomClass($_POST);

		$fw->validate();

		throw new ReloadAction('account', 'Account::created');
	})
	->post('doDelete', function($data) {

		$fw = new FailWatch();

		$eAccount = \account\AccountLib::getById(POST('id', 'int'));

		\account\AccountLib::delete($eAccount);

		$fw->validate();

		throw new ReloadAction('account', 'Account::deleted');

	});

?>
