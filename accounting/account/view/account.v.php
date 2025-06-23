<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Tous les comptes de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/thirdParty/';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eFarm);

	$t->mainTitle = new \account\AccountUi()->getManageTitle($data->eFarm);
	$t->mainTitleClass = 'hide-lateral-down';

	echo new \account\AccountUi()->getSearch($data->search);
	echo new \account\AccountUi()->getManage($data->eFarm, $data->cAccount);

});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = [];
	$found = FALSE;
	$others = FALSE;

	foreach($data->cAccount as $eAccount) {

		if($found === FALSE and ($eAccount['thirdParty'] ?? FALSE) === TRUE and $others === FALSE) {

			$results[] = [
				'type' => 'title',
				'itemHtml' => '<div>'.s("Classes de compte trouvées automatiquement").'</div>',
				'itemText' => s("Classes de compte trouvées automatiquement"),
			];

			$found = TRUE;

		} else if($found === TRUE and ($eAccount['thirdParty'] ?? FALSE) === FALSE and $others === FALSE) {

			$results[] = [
				'type' => 'title',
				'itemHtml' => '<div>'.s("Toutes les autres classes de compte").'</div>',
				'itemText' => s("Toutes les autres classes de compte"),
			];

			$others = TRUE;

		}

		$results[] = \account\AccountUi::getAutocomplete($data->eFarm['id'], $eAccount);

	}

	$results[] = \account\AccountUi::getAutocompleteCreate($data->eFarm);

	$t->push('results', $results);

});

new JsonView('queryLabel', function($data, AjaxTemplate $t) {

	$results = array_map(function($label) use ($data) { return \account\AccountUi::getAutocompleteLabel(POST('query'), $data->eFarm['id'], $label); }, $data->labels);

	$t->push('results', $results);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \account\AccountUi()->create($data->eFarm, $data->e);

});
?>
