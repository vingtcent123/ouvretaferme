<?php

new AdaptativeView('onboarding', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	$t->title = s("Configurer la plateforme agréée pour {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlAccount($data->eFarm).'/pdp/:onboarding';

	$t->mainTitle = new pdp\PdpUi()->getTitle($data->eFarm);

	echo '<div class="util-block-help">';
		echo '<h4>'.s("Vous êtes sur la page pour gérer vos factures électroniques").'</h4>';
		echo '<p>'.s("Avec {siteName}, vous allez gérer facilement et de façon fiable vos factures électroniques :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Gérez vos adresses de facturation électronique").'</li>';
			echo '<li>'.s("Recevez toutes vos factures électroniques").'</li>';
			echo '<li>'.s("Envoyez vos factures électroniques de ventes en un clic").'</li>';
			echo '<li>'.s("Intégrez vos factures électroniques dans la comptabilité sur {siteName} ou exportez un fichier FEC de vos factures électroniques (reçues et/ou envoyées) pour l'intégrer dans votre logiciel de comptabilité").'</li>';
		echo '</ul>';
	echo '</div>';

	echo '<br/>';

	echo new \pdp\PdpUi()->getConnectionBlock($data->eFarm);

});

new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("La configuration de la plateforme agréée {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlAccount($data->eFarm).'/pdp/';

	$t->mainTitle = new pdp\PdpUi()->getTitle($data->eFarm);

	if($data->token === NULL) {

		if($data->hadToken === TRUE) {

			echo '<div class="util-block-info">';

				echo '<p>'.s("La session a expiré, veuillez vous reconnecter sur Super PDP.").'</p>';

			echo '</div>';

			echo '<a href="'.\farm\FarmUi::urlConnected($data->cFarmUser->first()).'/pdp/:connect" class="btn btn-primary">'.s("Me reconnecter sur Super PDP").'</a>';

		} else {

			echo new \pdp\PdpUi()->getConnectionBlock($data->cFarmUser->first());

		}

	} else {

		echo '<div class="util-info">'.s("Votre ferme <b>{value}</b> est bien connectée à Super PDP !", $data->eFarm['name']).'</div>';

		echo '<h3>'.s("Configuration sur la plateforme agréée").'</h3>';

		echo new \pdp\CompanyUi()->summary($data->eCompany);

		echo '<h3 class="mt-2">'.s("Les adresses électroniques de la ferme").'</h3>';

		if($data->eCompany['cAddress']->count() <= 2) {

			echo '<div class="util-block-help">';
				echo s("Les adresses électroniques doivent être communiquées aux partenaires chez qui vous réalisez vos achats afin qu'ils puissent y envoyer leur facture électronique. Ainsi, vous recevrez vos factures sur la plateforme agréée depuis ces adresses.");
			echo '</div>';

		}

		if($data->eCompany['cAddress']->empty()) {

			echo '<div class="util-empty">';
				echo s("Vous n'avez pas encore créé d'adresse électronique !");
			echo '</div>';

			echo '<a class="btn btn-primary" href="'.\farm\FarmUi::urlConnected($data->eFarm).'/pdp/address:create">';
				echo Asset::icon('plus-circle').' '.s("Créer ma première adresse électronique");
			echo '</a>';

		} else {

			echo new \pdp\AddressUi()->list($data->eFarm, $data->eCompany['cAddress'], $data->nAddress);

			echo '<a class="btn btn-primary" href="'.\farm\FarmUi::urlConnected($data->eFarm).'/pdp/address:create">';
				echo Asset::icon('plus-circle').' '.s("Ajouter une adresse électronique");
			echo '</a>';
		}

	}

});
