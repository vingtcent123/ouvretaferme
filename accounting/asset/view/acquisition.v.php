<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les acquisitions de {farm}", ['farm' => $data->eFarm['name']]);
	$t->tab = 'asset';
	$t->subNav = new \company\CompanyUi()->getAssetSubNav($data->eFarm);
	$t->canonical = \company\CompanyUi::urlAsset($data->eFarm).'/acquisition';

	$t->mainTitle = new asset\AssetUi()->getAcquisitionTitle();

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlAsset($data->eFarm).'/acquisition?financialYear='.$eFinancialYear['id'];
			},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo '<div class="tabs-h" id="asset-acquisition" onrender="'.encode('Lime.Tab.restore(this, "acquisition-asset")').'">';

		echo '<div class="tabs-item">';
			echo '<a class="tab-item selected" data-tab="acquisition-asset" onclick="Lime.Tab.select(this)">'.s("Immobilisations").'</a>';
			echo '<a class="tab-item" data-tab="acquisition-subvention" onclick="Lime.Tab.select(this)">'.s("Subventions").'</a>';
		echo '</div>';

		echo '<div class="tab-panel" data-tab="acquisition-asset">';
			echo new asset\AssetUi()->getAcquisitionTable($data->cAsset, 'asset');
		echo '</div>';

		echo '<div class="tab-panel" data-tab="acquisition-subvention">';
			echo new asset\AssetUi()->getAcquisitionTable($data->cAssetSubvention, 'subvention');
		echo '</div>';

	echo '</div>';

});
