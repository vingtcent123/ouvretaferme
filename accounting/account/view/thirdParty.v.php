<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Les tiers de {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/thirdParty/';

	$t->mainTitle = new \account\ThirdPartyUi()->getThirdPartyTitle($data->eFarm);

	echo new \account\ThirdPartyUi()->getSearch($data->search);

	if($data->eFarm->usesAccounting()) {

		echo '<div class="util-block-help">';
			if(
				(FEATURE_ACCOUNTING_ACCRUAL and $data->eFarm['eFinancialYear']->isAccrualAccounting()) or
				(FEATURE_ACCOUNTING_CASH_ACCRUAL and $data->eFarm['eFinancialYear']->isCashAccrualAccounting())
			) {
				echo s("Les tiers sont des personnes ou des organismes avec qui votre ferme échange des flux. Un tiers peut être un client, un fournisseur, l'état... En fonction de son statut (la plupart du temps client/fournisseur), vos tiers auront un numéro de compte personnalisé, ce qui vous permet une analyse plus fine de vos encours (créances, dettes) et de vos flux financiers.");
				echo '<br />';
			}
			echo s("Pour faciliter la remontée d'informations dans la partie comptabilité (notamment entre les écritures comptables et les factures), vos <link>clients</link> peuvent être associés ici aux tiers.", ['link' => '<a href="'.new \farm\FarmUi()->urlSellingCustomers($data->eFarm).'">']);
		echo '</div>';

	}

	echo new \account\ThirdPartyUi()->list($data->eFarm, $data->cThirdParty, $data->search);


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

		$results[] = \account\ThirdPartyUi::getAutocomplete($data->eFarm, $eThirdParty);

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
