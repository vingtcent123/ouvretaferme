<?php
new AdaptativeView('adherer', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-commercialisation';
	$t->subNav = 'report';

	$t->title = s("Adhérer à l'association Ouvretaferme");

	$t->mainTitle = new \association\AssociationUi()->getTitle();

	echo '<h2>'.s("Mon adhésion").'</h2>';

	if($data->eFarm['membership'] === FALSE) {

		echo new \association\MembershipUi()->joinForm($data->eFarm, $data->eUser);

	} else {

		echo new \association\MembershipUi()->membership();

	}

	if($data->cHistory->count() > 0) {

		echo '<h2>'.s("Historique").'</h2>';

		echo new \association\HistoryUi()->display($data->cHistory);

	}


});
