<?php

new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("La configuration de la plateforme agréée {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlAccount($data->eFarm).'/superpdp/';

	$t->mainTitle = new pdp\PdpUi()->getTitle($data->eFarm);

	if($data->token === NULL) {

		if($data->hadToken === TRUE) {

			echo '<div class="util-block-info">';

				echo '<p>'.s("La session a expiré, veuillez vous reconnecter sur Super PDP.").'</p>';

			echo '</div>';

			echo '<a href="'.\farm\FarmUi::urlConnected($data->cFarmUser->first()).'/pdp/:connect" class="btn btn-primary">'.s("Me reconnecter sur Super PDP").'</a>';

		} else {

			echo '<div class="util-block-info">';

				echo '<p>'.s("{siteName} a choisi d'utiliser la plateforme Super PDP pour l'envoi et la réception de factures électroniques.").'</p>';
				echo '<p>'.s("Pour utiliser cette plateforme agréée : Cliquez sur le bouton <btn>Utiliser Super PDP</btn> et laissez-vous guider. Si vous avez déjà un compte sur Super PDP, vous pourrez vous connecter dessus pour autoriser {siteName} à déposer et récupérer les factures. Si vous n'avez pas de compte, vous pourrez en créer un et autoriser {siteName} à s'y connecter.", ['btn' => '<span class="btn btn-primary">']).'</p>';

			echo '</div>';

			echo '<a href="'.\farm\FarmUi::urlConnected($data->cFarmUser->first()).'/pdp/:connect" class="btn btn-primary">'.s("Utiliser Super PDP").'</a>';

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

			echo new \pdp\AddressUi()->list($data->eFarm, $data->eCompany['cAddress']);

			echo '<a class="btn btn-primary" href="'.\farm\FarmUi::urlConnected($data->eFarm).'/pdp/address:create">';
				echo Asset::icon('plus-circle').' '.s("Ajouter une adresse électronique");
			echo '</a>';
		}

	}

});
