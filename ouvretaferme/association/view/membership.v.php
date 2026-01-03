<?php
new AdaptativeView('/ferme/{farm}/adherer', function($data, FarmTemplate $t) {

	$t->nav = 'selling';
	$t->subNav = '';

	$t->title = s("Adhérer à l'association");
	$t->mainTitle = '<h1>'.$t->title.'</h1>';

	if($data->eFarm->isMembership() === FALSE) {
		$t->mainTitle .= '<p>'.s("Votre ferme <i>{farmName}</i> n'a pas encore adhéré à l'association Ouvretaferme pour l'année {year}.", ['farmName' => encode($data->eFarm['name']), 'year' => date('Y')]).'</p>';
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

		echo '<div class="util-block util-overflow-sm">';
		echo '<table style="font-size: 1.2rem" class="tr-bordered">';
			echo '<thead>';
				echo '<tr>';
					echo '<th></th>';
					echo '<th>'.s("Logiciel").'</th>';
					echo '<th>'.s("Tarif annuel").'</th>';
					echo '<th></th>';
					echo '<th></th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
				echo '<tr>';
					echo '<td rowspan="3">';
						echo '<span class="util-circle util-circle-lg bg-production mr-1">'.Asset::icon('leaf').'</span>';
						echo s("Production");
					echo '</td>';
					echo '<td>Elzeard</td>';
					echo '<td>330 - 990 €</td>';
					echo '<td rowspan="12" class="text-center bg-background" style="font-size: 1.5rem; font-weight: bold">Ouvretaferme</td>';
					echo '<td rowspan="7" class="text-center" style="font-size: 1.5rem; font-weight: bold">0 €</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>Brinjel</td>';
					echo '<td>50 - 300 €</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>Permatechnics</td>';
					echo '<td>220 - 494 €</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td rowspan="4">';
						echo '<span class="util-circle util-circle-lg bg-commercialisation mr-1">'.Asset::icon('basket3').'</span>';
						echo s("Commercialisation");
					echo '</td>';
					echo '<td>Socleo</td>';
					echo '<td>Minimum 360 €</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>Kuupanda</td>';
					echo '<td>420 – 1500 €</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>Ciboulette</td>';
					echo '<td>2 % des ventes (60 – 480 €)</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>Cagette</td>';
					echo '<td>2 à 6 % des ventes (max 1400 €)</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td rowspan="3">';
						echo '<span class="util-circle util-circle-lg bg-production mr-1">'.Asset::icon('bank').'</span>';
						echo s("Comptabilité");
					echo '</td>';
					echo '<td>Isagri</td>';
					echo '<td>420 - 1000 € et plus</td>';
					echo '<td rowspan="5" class="text-center" style="font-size: 1.5rem; font-weight: bold">100 €</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>Macompta</td>';
					echo '<td>159 €</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>Istea</td>';
					echo '<td>320 €</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td rowspan="2">';
						echo '<span class="util-circle util-circle-lg bg-private mr-1">'.Asset::icon('receipt').'</span>';
						echo s("Facturation électronique");
					echo '</td>';
					echo '<td>Votre banque</td>';
					echo '<td>100 - 300 €</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>?</td>';
					echo '<td>?</td>';
				echo '</tr>';
			echo '</thead>';
		echo '</table>';
		echo '</div>';

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
