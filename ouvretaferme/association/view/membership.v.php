<?php
new AdaptativeView('/ferme/{farm}/adherer', function($data, FarmTemplate $t) {

	$t->nav = 'selling';
	$t->subNav = '';

	$t->title = s("Adhérer à l'association");
	$t->mainTitle = Asset::image('main', 'logo.png', ['style' => 'height: 3rem; width: auto; margin-bottom: 0.5rem']).'<h1>'.$t->title.'</h1>';

	if(get_exists('membership')) {
		echo new \association\MembershipUi()->getMembershipSuccess($data->cHistory);
	}

	if(get_exists('donation')) {

		echo new \association\MembershipUi()->getDonationSuccess();

	} else {

		echo new \association\MembershipUi()->getMembership($data->eFarm, $data->hasJoinedForNextYear);

	}

	if($data->eFarm['membership'] === FALSE) {

		if($data->eFarm->isLegalComplete()) {

			echo new \association\MembershipUi()->getJoinForm($data->eFarm, $data->eUser);

		} else {

			echo '<h2>'.s("Bulletin d'adhésion").'</h2>';

			echo '<div class="util-block-help">';

				echo s("Nous avons besoin de quelques informations relatives à votre ferme pour adhérer à l'association.");

			echo '</div>';

			echo new \farm\FarmUi()->updateLegal($data->eFarm, ['legalEmail', 'siret', 'legalName']);

		}

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
