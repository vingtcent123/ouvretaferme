<?php

new AdaptativeView('/declaration-de-tva', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("La TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/declaration-de-tva';

	$title = '<div class="util-action">';
		$title .= '<h1>';
			$title .= '<a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/etats-financiers/"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$title .= s("La TVA");
		$title .= '</h1>';
		$title .= '<div>';
			$title .= '<a href="/farm/configuration:updateVat?id='.$data->eFarm['id'].'" class="btn btn-primary">'.Asset::icon('gear-fill').'</a>';
		$title .= '</div>';
	$title .= '</div>';

	$t->mainTitle = $title;

	if($data->eFarm->isVatAccountingConfigured() === FALSE) {

		echo '<div class="util-block-help">';
			echo \farm\AlertUi::getError('Farm::notVatAccounting', [
				'farm' => $data->eFarm,
				'btn' => 'btn-secondary'
			]);
		echo '</div>';

	} else {

		echo '<div class="tabs-h" id="vat">';

		echo new \overview\VatUi()->getVatTabs($data->eFarm, $data->vatParameters, $data->tab);

		switch($data->tab) {

			case NULL:
				echo new \overview\VatUi()->getGeneralTab($data->eFarm, $data->eFarm['eFinancialYear'], $data->vatParameters);
				break;

			case 'journal-buy':
			case 'journal-sell':
				echo new \overview\VatUi()->getOperationsTab($data->eFarm, mb_substr($data->tab, mb_strlen('journal') + 1), $data->cOperation, $data->vatParameters);
				break;

			case 'check':
				if(empty($data->check['sales']) and empty($data->check['taxes'])) {
					echo '<div class="util-empty">';
						echo s("Il semblerait que la période du {from} au {to} ne contienne aucune donnée pertinente à afficher pour le contrôle de TVA.", ['from' => \util\DateUi::numeric($data->vatParameters['from']), 'to' => \util\DateUi::numeric($data->vatParameters['to'])]);
					echo '</div>';
				} else {
					echo new \overview\VatUi()->getCheck($data->check, $data->vatParameters);
				}
				break;

			case 'cerfa':
				echo new \overview\VatUi()->getCerfa($data->eFarm, $data->eFarm['eFinancialYear'], $data->cerfa, $data->precision, $data->vatParameters, hasData: empty($data->check['sales']) === FALSE and empty($data->check['taxes']) === FALSE);
				break;

			case 'history':
				if($data->cVatDeclaration->empty()) {
					echo '<div class="util-empty">';
						echo s("Il n'y a aucune déclaration de TVA à afficher pour la période du {from} au {to}.", ['from' => \util\DateUi::numeric($data->vatParameters['from']), 'to' => \util\DateUi::numeric($data->vatParameters['to'])]);
					echo '</div>';
				} else {
					echo new \overview\VatDeclarationUi()->getHistory($data->eFarm, $data->eFarm['eFinancialYear'], $data->cVatDeclaration, $data->allPeriods);
				}
				break;
		}

		echo '</div>';

	}

});

new AdaptativeView('/etats-financiers/declaration-de-tva/operations', function($data, PanelTemplate $t) {

	return new \overview\VatUi()->showSuggestedOperations(
		$data->eFarm, $data->eFarm['eFinancialYear'], $data->eVatDeclaration, $data->cOperation, $data->cerfaCalculated, $data->cerfaDeclared,
	);

});
