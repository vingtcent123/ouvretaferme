<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Tous les comptes de {value}", $data->eCompany['name']);
	$t->tab = 'settings';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eCompany);

	$t->mainTitle = new \account\AccountUi()->getManageTitle($data->eCompany);

	echo new \account\AccountUi()->getSearch($data->search);
	echo new \account\AccountUi()->getManage($data->eCompany, $data->cAccount);

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

		$results[] = \account\AccountUi::getAutocomplete($data->eCompany['id'], $eAccount);

	}

	$results[] = \account\AccountUi::getAutocompleteCreate($data->eCompany);

	$t->push('results', $results);

});

new JsonView('queryLabel', function($data, AjaxTemplate $t) {

	$results = array_map(function($label) use ($data) { return \account\AccountUi::getAutocompleteLabel(POST('query'), $data->eCompany['id'], $label); }, $data->labels);

	$t->push('results', $results);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \account\AccountUi()->create($data->eCompany, $data->e);

});
?>
