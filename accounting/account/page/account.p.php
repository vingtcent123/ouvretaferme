<?php
new Page()
->get('index', function($data) {

	$data->search = new Search([
		'classPrefix' => GET('classPrefix'),
		'description' => GET('description'),
		'vatFilter' => GET('vatFilter', 'bool', FALSE),
		'customFilter' => GET('customFilter', 'bool', FALSE),
	]);
	$data->cJournalCode = \journal\JournalCodeLib::deferred();
	$data->cAccount = \account\AccountLib::getForList(search: $data->search);

	$financialYearIds = array_slice($data->eFarm['cFinancialYear']->getKeys(), 0, 2);

	$cOperation = \journal\OperationLib::countByAccounts($data->cAccount, $financialYearIds);

	foreach($data->cAccount as &$eAccount) {

		$eAccount['operationByFinancialYear'] = [];

		foreach($financialYearIds as $financialYearId) {
			$eAccount['operationByFinancialYear'][$financialYearId] = $cOperation[$eAccount['id']][$financialYearId]['count'] ?? 0;
		}
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
		'classPrefixes' => GET('classPrefixes', 'array'),
		'withVat' => GET('withVat', 'bool', FALSE),
		'withJournal' => GET('withJournal', 'bool', FALSE),
		'withDetail' => GET('withDetail', 'bool', FALSE),
		'status' => GET('status'),
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

	$cAccount = \account\AccountLib::getAll(query: $query, search: $data->search);

	$data->cAccount = \account\AccountLib::orderAccounts($cAccount, $thirdParty, $accountsAlreadyUsed);

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

	}

	$data->labels = $labels;
	throw new \ViewAction($data);

});

new \account\AccountPage()
	->applyElement(function($data, \account\Account $e) {

		$e['eFarm'] = $data->eFarm;$data->eOld = clone $e;

		$e->acceptQuickUpdate(POST('property'));

		$e['eOld'] = $data->eOld;
		$e['cJournalCode'] = \journal\JournalCodeLib::deferred();
	})
	->quick(['description', 'journalCode', 'vatRate', 'class']);

new \account\AccountPage()
	->applyElement(function($data, \account\Account $e) {
		$e['cJournalCode'] = \journal\JournalCodeLib::deferred();
		$e['eFarm'] = $data->eFarm;
	})
	->create(function($data) {

		throw new ViewAction($data);

	});

new \account\AccountPage()
	->getCreateElement(function($data) {

		return new \account\Account([
			'eFarm' => $data->eFarm,
		]);

	})
	->doCreate(function($data) {

		throw new ReloadAction('account', 'Account::created');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ReloadAction())
	->doDelete(function($data) {

		throw new ReloadAction('account', 'Account::deleted');

	})
	->doUpdateCollectionProperties('doUpdateJournalCollection', ['journalCode'], fn($data) => throw new ReloadAction())
;

?>
