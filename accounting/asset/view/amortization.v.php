<?php
new AdaptativeView('/immobilisations', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'assets';

	$t->title = s("Les immobilisations de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/immobilisations';

	$t->mainTitle = new \farm\FarmUi()->getAccountingAssetsTitle($data->eFarm, $data->view, $data->nOperationMissingAsset);

	if(empty($data->amortizations)) {

		echo '<div class="util-info">';
			echo s("Il n'y a pas d'information à afficher pour le moment.");
		echo '</div>';

	} else {

		if($data->eFarm['eFinancialYear']['status'] !== \account\FinancialYearElement::CLOSE) {

			echo '<div class="util-warning">';
				echo s("Vous visualisez actuellement les immobilisations d'un exercice comptable encore ouvert : il s'agit donc d'une projection à la fin de l'exercice dans le cas où les immobilisations ne changent pas dans le courant de l'exercice.");
			echo '</div>';

		}

		echo '<div class="tabs-h" id="asset-amortization">';

			echo '<div class="tabs-item">';
				echo '<a class="tab-item '.($data->selectedTab === 'asset' ? 'selected' : '').'" data-tab="amortization-asset" href="'.$t->canonical.'?tab=asset">'.s("Immobilisations").'</a>';
				echo '<a class="tab-item '.($data->selectedTab === 'grant' ? 'selected' : '').' " data-tab="amortization-grant" href="'.$t->canonical.'?tab=grant">'.s("Subventions").'</a>';
			echo '</div>';

			echo '<div class="tab-panel '.($data->selectedTab === 'asset' ? 'selected' : '').'" data-tab="amortization-asset">';
				echo \asset\AmortizationUi::getDepreciationTable($data->eFarm, $data->amortizations);
			echo '</div>';

			echo '<div class="tab-panel '.($data->selectedTab === 'grant' ? 'selected' : '').'" data-tab="amortization-grant">';
				if(count($data->amortizations) > 0) {
					echo \asset\AmortizationUi::getDepreciationTable($data->eFarm, $data->amortizations);
				} else {
					echo '<div class="util-info">';
						echo s("Il n'y a aucun amortissement à afficher.");
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

	$t->mainTitle = new \farm\FarmUi()->getAccountingAssetsTitle($data->eFarm, $data->view, 0);

	echo '<div class="tabs-h" id="asset-acquisition" onrender="'.encode('Lime.Tab.restore(this, "acquisition-asset")').'">';

		echo '<div class="tabs-item">';
		echo '<a class="tab-item selected" data-tab="acquisition-asset" onclick="Lime.Tab.select(this)">'.s("Immobilisations").'</a>';
		echo '<a class="tab-item" data-tab="acquisition-subvention" onclick="Lime.Tab.select(this)">'.s("Subventions").'</a>';
		echo '</div>';

		echo '<div class="tab-panel" data-tab="acquisition-asset">';
		echo new \asset\AssetUi()->getAcquisitionTable($data->eFarm, $data->cAsset, 'asset');
		echo '</div>';

		echo '<div class="tab-panel" data-tab="acquisition-subvention">';
		echo new \asset\AssetUi()->getAcquisitionTable($data->eFarm, $data->cAssetSubvention, 'subvention');
		echo '</div>';

	echo '</div>';


});
