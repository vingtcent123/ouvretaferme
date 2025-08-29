<?php
new AdaptativeView('adherer', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-commercialisation';
	$t->subNav = 'report';

	$t->title = s("Adhérer à l'association Ouvretaferme");

	$t->mainTitle = new \association\AssociationUi()->getTitle();

	if($data->cHistory->count() === 0) {

		if($data->eFarm->isLegalCompleteForMembership()) {

			echo new \association\MembershipUi()->joinForm($data->eFarm, $data->eUser);

		} else {

			echo '<div class="util-block-help">';

				echo '<h4>'.s("Vous êtes sur la page qui vous permet d'adhérer à l'association Ouvretaferme qui édite le logiciel que vous êtes actuellement en train d'utiliser").'</h4>';

				echo '<p class="mt-1 mb-2">'.s("Certaines informations sont nécessaires pour adhérer à l'association :").'</p>';
				echo new \farm\FarmUi()->updateLegal($data->eFarm, ['legalEmail', 'siret', 'legalName', 'legalForm']);

			echo '</div>';

		}

	} else if($data->eFarm['membership'] === FALSE) {

			echo new \association\MembershipUi()->joinForm($data->eFarm, $data->eUser);

	} else {

		echo new \association\MembershipUi()->membership();

	}

	if($data->cHistory->count() > 0) {

		echo '<h2 class="mt-2">'.s("Historique").'</h2>';

		echo new \association\HistoryUi()->display($data->cHistory);

	}


});
