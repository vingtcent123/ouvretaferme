<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Tous les comptes de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/thirdParty/';

	$t->mainTitle = new \account\AccountUi()->getManageTitle($data->eFarm);

	$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-custom-account');
	$data->tipNavigation = 'inline';

	echo new \account\AccountUi()->getSearch($data->search);
	echo new \account\AccountUi()->list($data->eFarm, $data->cAccount, $data->cJournalCode);

});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = [];

	if(GET('canHaveNoAccountOption', 'bool') === TRUE) {

		$results[] = \account\AccountUi::getAutocompleteWithout($data->eFarm);

	}

	$cAccountThirdParty = new Collection();
	$cAccountUsed = new Collection();
	$cAccountOthers = new Collection();

	foreach($data->cAccount as $eAccount) {
		if($eAccount['isThirdParty'] === TRUE) {
			$cAccountThirdParty->append($eAccount);
		} else if($eAccount['used'] === TRUE) {
			$cAccountUsed->append($eAccount);
		} else {
			$cAccountOthers->append($eAccount);
		}
	}

	if($cAccountThirdParty->notEmpty()) {

		$results[] = [
			'type' => 'title',
			'itemHtml' => '<div>'.s("Numéros de compte trouvés par le tiers").'</div>',
			'itemText' => s("Numéros de compte trouvés par le tiers"),
		];

		foreach($cAccountThirdParty as $eAccount) {
			$results[] = \account\AccountUi::getAutocomplete($data->eFarm, $eAccount, $data->search);
		}

	}

	if($cAccountUsed->notEmpty()) {

		$results[] = [
			'type' => 'title',
			'itemHtml' => '<div>'.s("Numéros de compte déjà utilisés (par numéro puis ordre décroissant d'utilisation)").'</div>',
			'itemText' => s("Numéros de compte déjà utilisés (par numéro puis ordre décroissant d'utilisation)"),
		];

		foreach($cAccountUsed as $eAccount) {
			$results[] = \account\AccountUi::getAutocomplete($data->eFarm, $eAccount, $data->search);
		}
	}

	if($cAccountOthers->notEmpty()) {

		if($cAccountThirdParty->empty() and $cAccountUsed->empty()) {
			$text = s("Tous les numéros de compte");
		} else {
			$text = s("Tous les autres numéros de compte");
		}

		$results[] = [
			'type' => 'title',
			'itemHtml' => '<div>'.$text.'</div>',
			'itemText' => $text,
		];

		foreach($cAccountOthers as $eAccount) {
			$results[] = \account\AccountUi::getAutocomplete($data->eFarm, $eAccount, $data->search);
		}

	}

	$results[] = \account\AccountUi::getAutocompleteCreate($data->eFarm);

	$t->push('results', $results);

});

new JsonView('queryLabel', function($data, AjaxTemplate $t) {

	$results = array_map(function($label) use ($data) { return \account\AccountUi::getAutocompleteLabel(POST('query'), $data->eFarm, $label); }, $data->labels);

	$t->push('results', $results);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	$data->e['eFarm'] = $data->eFarm;
	return new \account\AccountUi()->create($data->eFarm, $data->e);

});
?>
