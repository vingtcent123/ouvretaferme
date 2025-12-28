<?php
new AdaptativeView('/facturation-electronique', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	$t->title = s("Les factures de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/facturation-electronique';

	$this->mainTitleClass = 'invoicing-presentation';

	$t->mainTitle = '<h1>'.s("Facturation électronique").'</h1>';

	echo '<h2>En cours...</h2>';


});


new AdaptativeView('/factures/', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	$t->title = s("Les factures de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/factures/';

	$t->mainTitle = new \farm\FarmUi()->getAccountingInvoiceTitle($data->eFarm, $data->counts);

	if(FEATURE_PA === FALSE) {

		echo '<div class="util-block-important	">'.s("Cette fonctionnalité arrive bientôt ! On se dépêche... ").' '.Asset::icon('lightning-charge-fill').Asset::icon('lightning-charge-fill').Asset::icon('lightning-charge-fill').'</div>';

	} else {

		echo '<div class="util-block-help">';
			echo '<p>'.s("Cette page vous permet de suivre le statut de vos factures envoyées et reçues sur la Plateforme Agréée.").'</p>';
		echo '</div>';

		echo '<div class="tabs-item">';

		foreach(['buy', 'sell'] as $tab) {

			echo '<a class="tab-item '.($data->selectedTab === $tab ? ' selected' : '').'" data-tab="'.$tab.'" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/factures/?tab='.$tab.'">';
			echo match($tab) {
				'sell' => s("Ventes"),
				'buy' => s("Achats"),
			};
			echo ' <small class="tab-item-count">'.$data->counts['invoice'][$tab].'</small>';
			echo '</a>';

		}

	}

	echo '</div>';


});
