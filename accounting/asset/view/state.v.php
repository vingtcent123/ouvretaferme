<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("L'état des immobilisations de {farm}", ['farm' => $data->eFarm['name']]);
	$t->tab = 'asset';
	$t->subNav = new \company\CompanyUi()->getAssetSubNav($data->eFarm);
	$t->canonical = \company\CompanyUi::urlAsset($data->eFarm).'/state';

	$t->mainTitle = new asset\AssetUi()->getTitle();

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlAsset($data->eFarm).'/state?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	if(empty($data->assetSummary)) {

		echo '<div class="util-info">';
			echo s("Il n'y a pas d'information à afficher pour le moment.");
		echo '</div>';
		
	} else {

		echo new asset\AssetUi()->getSummary($data->eFinancialYear, $data->assetSummary);

	}

});
