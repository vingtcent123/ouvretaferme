<?php
new AdaptativeView('/ferme/{farm}/adherer', function($data, FarmTemplate $t) {

	$t->nav = 'selling';
	$t->subNav = '';

	$t->title = s("Adhérer à l'association");
	$t->mainTitle = Asset::image('main', 'logo.png', ['style' => 'height: 3rem; width: auto; margin-bottom: 0.5rem']).'<h1>'.$t->title.'</h1>';

	if(get_exists('membership')) {

		echo '<div class="util-box-success mb-2">';
			echo '<h4>'.s("Votre adhésion a bien été prise en compte !").'</h4>';
			echo '<div>'.s("Toute l'équipe de Ouvretaferme vous souhaite la bienvenue et vous remercie pour votre engagement 🥳").'</div>';
		echo '</div>';

	}

	if(get_exists('donation')) {

		echo '<div class="util-box-success mb-2">';
			echo '<h4>'.s("Nous avons bien reçu votre don !").'</h4>';
			echo '<div>'.s("Toute l'équipe de Ouvretaferme vous remercie pour votre générosité 🥳").'</div>';
		echo '</div>';

	} else {

		echo new \association\MembershipUi()->getMembership($data->eFarm, $data->hasJoinedForNextYear);

	}

	if($data->eFarm['membership'] === FALSE) {

		if($data->eFarm->isLegalComplete()) {

			echo new \association\MembershipUi()->getJoinForm($data->eFarm, $data->eUser);

		} else {

			echo '<div class="util-block-help">';

				echo '<h4>'.s("Vous êtes sur la page qui vous permet d'adhérer à l'association Ouvretaferme qui édite le logiciel que vous êtes actuellement en train d'utiliser").'</h4>';

			echo '</div>';

				echo '<p class="mt-1 mb-2">'.s("Certaines informations sont nécessaires pour adhérer à l'association :").'</p>';
				echo new \farm\FarmUi()->updateLegal($data->eFarm, ['legalEmail', 'siret', 'legalName']);

		}

	} else {

		if(
			date('m-d') >= Setting::get('association\canJoinForNextYearFrom') and
			$data->hasJoinedForNextYear === FALSE
		) {

			echo new \association\MembershipUi()->getJoinForm($data->eFarm, $data->eUser);

		}

	}

	if($data->cHistory->count() > 0) {

		echo '<h2 class="mt-2">'.s("Historique").'</h2>';

		echo new \association\HistoryUi()->display($data->cHistory);

	}


});

new AdaptativeView('adherer', function ($data, MainTemplate $t) {

	$t->title = s("Adhérer à l'association Ouvretaferme");

	echo '<h3>'.s("Avec quelle ferme souhaitez-vous adhérer à l'association Ouvretaferme ?").'</h3>';

	echo new \association\MembershipUi()->getMyFarms($data->cFarmUser);

});

new AdaptativeView('/ferme/{farm}/donner', function($data, PanelTemplate $t) {

	return new \association\MembershipUi()->getDonateForm($data->eFarm);

});
