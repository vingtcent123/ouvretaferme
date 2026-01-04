<?php
new AdaptativeView('/ferme/{farm}/adherer', function($data, FarmTemplate $t) {

	$t->nav = 'selling';
	$t->subNav = '';

	$t->title = s("Adhérer à l'association");
	$t->mainTitle = '<h1>'.$t->title.'</h1>';

	if($data->eFarm->isMembership() === FALSE) {
		echo '<p>'.s("Votre ferme <i>{farmName}</i> n'a pas encore adhéré à l'association Ouvretaferme pour l'année {year}.<br/><b>Rejoignez les {count} adhérents de l'association !</b>", ['farmName' => encode($data->eFarm['name']), 'year' => date('Y'), 'count' => $data->members	]).'</p>';
	}

	if(get_exists('membership')) {
		echo new \association\MembershipUi()->getMembershipSuccess($data->cHistory);
	}

	if(get_exists('donation')) {

		echo new \association\MembershipUi()->getDonationSuccess();

	} else {

		echo new \association\MembershipUi()->getMembership($data->eFarm, $data->hasJoinedForNextYear);

	}

	if($data->eFarm['membership'] === FALSE) {

		echo new \association\MembershipUi()->getJoinForm($data->eFarm, $data->eUser);

		echo '<br/><br/>';
		echo '<h2>'.s("Vous n'êtes pas tout à fait convaincu ?").'</h2>';
		echo '<p>'.s("Alors jetez un oeil au tableau ci-dessous pour mesurer le coût réel des services équivalents si Ouvretaferme n'existait pas.").'</p>';


		$isDiscount = \association\AssociationSetting::isDiscount($data->eFarm);

		echo new \main\LegalUi()->friends($isDiscount);

	} else {

		if(
			date('m-d') >= \association\AssociationSetting::CAN_JOIN_FOR_NEXT_YEAR_FROM and
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
