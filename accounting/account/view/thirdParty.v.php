<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Les tiers de {company}", ['company' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/thirdParty/';

	$t->mainTitle = new \account\ThirdPartyUi()->getThirdPartyTitle($data->eFarm);

	echo new \account\ThirdPartyUi()->getSearch($data->search);
	echo new \account\ThirdPartyUi()->manage($data->eFarm, $data->cThirdParty, $data->search);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \account\ThirdPartyUi()->create($data->eFarm, $data->e);

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

		$results[] = \account\ThirdPartyUi::getAutocomplete($data->eFarm['id'], $eThirdParty);

	}

	$results[] = \account\ThirdPartyUi::getAutocompleteCreate($data->eFarm);

	$t->push('results', $results);

});

new AdaptativeView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->success('account', 'ThirdParty::created');
	$t->js()->closePanel('#panel-journal-thirdParty-create');
	$t->js()->eval('ThirdParty.setNewThirdParty('.$data->e['id'].', "'.$data->e['name'].'");');

});

?>
