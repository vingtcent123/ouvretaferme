<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les tiers de {company}", ['company' => $data->eCompany['name']]);
	$t->tab = 'settings';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eCompany);
	$t->canonical = \company\CompanyUi::urlJournal($data->eCompany).'/thirdParty/';

	$t->mainTitle = new \journal\ThirdPartyUi()->getThirdPartyTitle($data->eCompany);

	echo new \journal\ThirdPartyUi()->getSearch($data->search);
	echo new \journal\ThirdPartyUi()->manage($data->eCompany, $data->cThirdParty, $data->search);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \journal\ThirdPartyUi()->create($data->eCompany, $data->e);

});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = [];
	$found = FALSE;
	$others = FALSE;

	foreach($data->cThirdParty as $eThirdParty) {

		if($found === FALSE and ($eThirdParty['weight'] ?? 0) > 0 and $others === FALSE) {

			$results[] = [
				'type' => 'title',
				'itemHtml' => '<div>'.s("Tiers trouvés automatiquement").'</div>',
				'itemText' => s("Tiers trouvés automatiquement"),
			];

			$found = TRUE;

		} else if($found === TRUE and ($eThirdParty['weight'] ?? 0) === 0 and $others === FALSE) {

			$results[] = [
				'type' => 'title',
				'itemHtml' => '<div>'.s("Tous les autres tiers").'</div>',
				'itemText' => s("Tous les autres tiers"),
			];

			$others = TRUE;

		}

		$results[] = \journal\ThirdPartyUi::getAutocomplete($data->eCompany['id'], $eThirdParty);

	}

	$results[] = \journal\ThirdPartyUi::getAutocompleteCreate($data->eCompany);

	$t->push('results', $results);

});

new AdaptativeView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->success('journal', 'ThirdParty::created');
	$t->js()->closePanel('#panel-journal-thirdParty-create');
	$t->js()->eval('ThirdParty.setNewThirdParty('.$data->e['id'].', "'.$data->e['name'].'");');

});

?>
