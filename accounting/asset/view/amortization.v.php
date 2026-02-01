<?php
new AdaptativeView('/immobilisations', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'assets';

	$t->title = s("Les immobilisations de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/immobilisations';

	$t->mainTitle = new \farm\FarmUi()->getAccountingAssetsTitle($data->eFarm, $data->view, $data->hasAsset, $data->eFinancialYearDocument);

	if($data->nAmortizations === 0) {

		echo '<div class="util-empty">';
			echo s("Il n'y a aucune fiche d'immobilisation enregistrée pour l'instant.");
			echo '<a href="/doc/accounting:asset" class="btn btn-xs btn-outline-primary ml-1">'.\Asset::icon('person-raised-hand').' '.s("Lire l'aide sur l'import d'immobilisations").'</a>';
		echo '</div>';
		echo '<a href="'.\company\CompanyUi::urlAsset($data->eFarm).'/csv" class="btn btn-primary mr-1">'.s("Importer un fichier CSV d'immobilisations").'</a>';
		echo '<a href="'.\company\CompanyUi::urlAsset($data->eFarm).'/:create" class="btn btn-primary">'.s("Créer ma première fiche d'immobilisation").'</a>';

	} else {

		if($data->eFarm['eFinancialYear']['status'] !== \account\FinancialYearElement::CLOSE) {

			echo '<div class="util-warning">';
				echo s("Vous visualisez actuellement les immobilisations d'un exercice comptable encore ouvert : il s'agit donc d'une projection à la fin de l'exercice dans le cas où les immobilisations ne changent pas dans le courant de l'exercice.");
			echo '</div>';

		}

		if($data->nOperationMissingAsset > 0) {

			echo '<a class="btn btn-success bg-accounting border-accounting mb-1" href="'.\farm\FarmUi::urlConnected($data->eFarm).'/journal/livre-journal?needsAsset=1">';
				echo \Asset::icon('exclamation-triangle').' ';
				echo p("{value} fiche d'immobilisation à créer", "{value} fiches d'immobilisation à créer", $data->nOperationMissingAsset);
			echo '</a>';

		}

		echo '<div class="tabs-h" id="asset-amortization">';

			echo '<div class="tabs-item">';
				echo '<a class="tab-item '.($data->selectedTab === 'asset' ? 'selected' : '').'" data-tab="amortization-asset" href="'.$t->canonical.'?tab=asset">'.s("Immobilisations").'</a>';
				echo '<a class="tab-item '.($data->selectedTab === 'grant' ? 'selected' : '').' " data-tab="amortization-grant" href="'.$t->canonical.'?tab=grant">'.s("Subventions").'</a>';
			echo '</div>';

			echo '<div class="tab-panel '.($data->selectedTab === 'asset' ? 'selected' : '').'" data-tab="amortization-asset">';
				echo new \asset\AmortizationUi()->getDepreciationTable($data->eFarm, $data->amortizations);
			echo '</div>';

			echo '<div class="tab-panel '.($data->selectedTab === 'grant' ? 'selected' : '').'" data-tab="amortization-grant">';
				if(count($data->amortizations) > 0) {
					echo new \asset\AmortizationUi()->getDepreciationTable($data->eFarm, $data->amortizations);
				} else {
					echo '<div class="util-empty">';
						if($data->selectedTab === 'asset') {
							echo s("Il n'y a aucun amortissement à afficher.");
						} else {
							echo s("Il n'y a aucune subvention à afficher.");
						}
					echo '</div>';
				}
			echo '</div>';

		echo '</div>';

	}


});


new AdaptativeView('/immobilisations/acquisitions', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'assets';

	$t->title = s("Les acquisitions de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlAsset($data->eFarm).'/acquisition';

	$t->mainTitle = new \farm\FarmUi()->getAccountingAssetsTitle($data->eFarm, $data->view, 0, $data->eFinancialYearDocument);

	if($data->cAsset->empty() and $data->cAssetSubvention->empty()) {

		echo '<div class="util-empty">';
			echo s("Il n'y a pas encore d'immobilisation nouvellement acquise sur cet exercice comptable.");
		echo '</div>';

	} else {

		if($data->eFarm['eFinancialYear']['status'] !== \account\FinancialYearElement::CLOSE) {

			echo '<div class="util-warning">';
				echo s("Vous visualisez actuellement les immobilisations d'un exercice comptable encore ouvert : il s'agit donc d'une projection à la fin de l'exercice dans le cas où les immobilisations ne changent pas dans le courant de l'exercice.");
			echo '</div>';

		}

		if($data->nOperationMissingAsset > 0) {

			echo '<a class="btn btn-success bg-accounting border-accounting mb-1" href="'.\farm\FarmUi::urlConnected($data->eFarm).'/journal/livre-journal?needsAsset=1">';
				echo \Asset::icon('exclamation-triangle').' ';
				echo p("{value} fiche d'immobilisation à créer", "{value} fiches d'immobilisation à créer", $data->nOperationMissingAsset);
			echo '</a>';

		}

		echo '<div class="tabs-h" id="asset-acquisition" onrender="'.encode('Lime.Tab.restore(this, "acquisition-asset")').'">';

			echo '<div class="tabs-item">';
			echo '<a class="tab-item selected" data-tab="acquisition-asset" onclick="Lime.Tab.select(this)">'.s("Immobilisations").'</a>';
			echo '<a class="tab-item" data-tab="acquisition-subvention" onclick="Lime.Tab.select(this)">'.s("Subventions").'</a>';
			echo '</div>';

			echo '<div class="tab-panel" data-tab="acquisition-asset">';
			echo new \asset\AssetUi()->getAcquisitionTable($data->eFarm, $data->cAsset, 'asset');
			echo '</div>';

			echo '<div class="tab-panel" data-tab="acquisition-subvention">';
			echo new \asset\AssetUi()->getAcquisitionTable($data->eFarm, $data->cAssetSubvention, 'grant');
			echo '</div>';

		echo '</div>';

	}

});
