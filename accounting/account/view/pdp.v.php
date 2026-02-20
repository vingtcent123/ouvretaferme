<?php

new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("La configuration de la plateforme agréée {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlAccount($data->eFarm).'/superpdp/';

	$t->mainTitle = new \account\PdpUi()->getTitle($data->eFarm);

	if($data->token === NULL) {

		echo '<div class="util-block-info">';

			echo '<p>'.s("{siteName} a choisi d'utiliser la plateforme Super PDP pour l'envoi et la réception de factures électroniques.").'</p>';
			echo '<p>'.s("Pour utiliser cette plateforme agréée : Cliquez sur le bouton <btn>Utiliser Super PDP</btn> et laissez-vous guider. Si vous avez déjà un compte sur Super PDP, vous pourrez vous connecter dessus pour autoriser {siteName} à déposer et récupérer les factures. Si vous n'avez pas de compte, vous pourrez en créer un et autoriser {siteName} à s'y connecter.", ['btn' => '<span class="btn btn-primary">']).'</p>';

		echo '</div>';

		echo '<a href="'.\farm\FarmUi::urlConnected($data->cFarmUser->first()).'/account/pdp:connect" class="btn btn-primary">'.s("Utiliser Super PDP").'</a>';

	} else {

		echo '<div class="util-info">'.s("Votre ferme <b>{value}</b> est bien connectée à Super PDP !", $data->eFarm['name']).'</div>';

		echo '<h2>'.s("Configuration sur la plateforme agréée").' (<a target="_blank" href="'.\account\PartnerSetting::SUPER_PDP_URL.'app/companies/show/'.encode($data->company['id']).'">'.s("voir").'</a>)</h2>';

		echo '<div class="util-block stick-xs bg-background-light">';

			echo '<dl class="util-presentation util-presentation-1">';

				echo '<dt>'.s("Numéro d'entreprise").'</dt>';
				echo '<dd>'.encode($data->company['number']).'</dd>';

				echo '<dt>'.s("Nom").'</dt>';
				echo '<dd>'.encode($data->company['formal_name']).'</dd>';

				echo '<dt>'.s("Adresse").'</dt>';
				echo '<dd>';
					echo encode($data->company['address']);
					echo '<br />'.encode($data->company['postcode']);
					echo ' '.encode($data->company['city']);
				echo '</dd>';

			echo '</dl>';

		echo '</div>';

	}

});
