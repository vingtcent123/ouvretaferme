<?php
new Page(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
	->get('index', function($data) {

		$data->cJournalCode = \journal\JournalCodeLib::getAll();

		throw new ViewAction($data);

	});

new \journal\JournalCodePage(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage');
})
	->quick(['name', 'color', 'isExtournable'], validate: ['canQuickUpdate'])
	->create(function($data) {

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new ReloadAction('journal', 'JournalCode::created');
	})
	->doDelete(function($data) {
		throw new ReloadAction('journal', 'JournalCode::deleted');
	});

?>
