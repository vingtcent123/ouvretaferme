<?php
new AdaptativeView('adherer', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-commercialisation';
	$t->subNav = 'report';

	$t->title = s("Adhérer à l'association Ouvretaferme");
	$t->mainTitle = '<h1>'.$t->title.'</h1>';

	if($data->eFarm['membership'] === FALSE) {

		if($data->eFarm->isLegalComplete()) {

			echo new \association\MembershipUi()->memberInformation($data->eFarm, $data->eUser);
			echo new \association\MembershipUi()->joinForm($data->eFarm);
			echo new \association\MembershipUi()->donateForm($data->eFarm, FALSE);

		} else {

			echo '<div class="util-block-help">';

				echo '<h4>'.s("Vous êtes sur la page qui vous permet d'adhérer à l'association Ouvretaferme qui édite le logiciel que vous êtes actuellement en train d'utiliser").'</h4>';

			echo '</div>';

				echo '<p class="mt-1 mb-2">'.s("Certaines informations sont nécessaires pour adhérer à l'association :").'</p>';
				echo new \farm\FarmUi()->updateLegal($data->eFarm, ['legalEmail', 'siret', 'legalName']);

		}

	} else {

		echo new \association\MembershipUi()->membership();
		echo new \association\MembershipUi()->donateForm($data->eFarm, TRUE);

	}

	echo new \association\MembershipUi()->gdprInfo();

	if($data->cHistory->count() > 0) {

		echo '<h2 class="mt-2">'.s("Historique").'</h2>';

		echo new \association\HistoryUi()->display($data->cHistory);

	}


});
