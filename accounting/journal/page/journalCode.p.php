<?php
new Page(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
	->get('index', function($data) {

		$data->cJournalCode = \journal\JournalCodeLib::getAll();

		\journal\JournalCodeLib::countAccountsByJournalCode($data->cJournalCode);

		throw new ViewAction($data);

	});

new \journal\JournalCodePage(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
	->quick(['code', 'name', 'color', 'isReversable', 'isDisplayed'])
	->create(function($data) {

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new ReloadAction('journal', 'JournalCode::created');
	})
	->doDelete(function($data) {
		throw new ReloadAction('journal', 'JournalCode::deleted');
	})
	->read('accounts', function($data) {

		$data->cAccount = \account\AccountLib::getAll();

		throw new ViewAction($data);

	})
	->write('doAccounts', function($data) {

		\journal\JournalCodeLib::updateAccountsForJournalCode($data->e, POST('account', 'array'));

		throw new ReloadAction('journal', 'JournalCode::accountsUpdated');
	});

?>
