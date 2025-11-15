<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Tous les journaux de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/journalCode';

	$t->mainTitle = new \journal\JournalCodeUi()->getManageTitle($data->eFarm);

	echo new \journal\JournalCodeUi()->getManage($data->eFarm, $data->cJournalCode);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \journal\JournalCodeUi()->create($data->eFarm, $data->e);

});

new AdaptativeView('accounts', function($data, PanelTemplate $t) {

	return new \journal\JournalCodeUi()->accounts($data->eFarm, $data->e, $data->cAccount);

});

?>
