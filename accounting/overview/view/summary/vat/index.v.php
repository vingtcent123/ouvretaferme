<?php
new AdaptativeView('noVat', function($data, FarmTemplate $t) {

	$t->nav = 'summary';
	$t->subNav = 'vat/';

	$t->title = s("La TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlSummary($data->eFarm).'/vat/';

	$t->mainTitle = new \overview\VatUi()->getTitle($data->eFarm, $data->cFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function (\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlSummary($data->eFarm).'/vat/?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo '<div class="util-info">';
		echo s("Cet exercice comptable n'a pas été configuré pour être assujetti à la TVA.").' ';
		if($data->eFinancialYear['status'] === \account\FinancialYear::OPEN) {
			echo s("(<link>modifier les paramètres</link>).", ['link' => '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/:update?id='.$data->eFinancialYear['id'].'">']);
		} else {
			echo s("Les paramètres de l'exercice ne sont pas modifiables car il est terminé (<link>voir les exercices</link>).", ['link' => '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/">']);
		}
	echo '</div>';
});
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'summary';
	$t->subNav = 'vat/';

	$t->title = s("La TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlSummary($data->eFarm).'/vat/';

	$t->mainTitle = new \overview\VatUi()->getTitle($data->eFarm, $data->cFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlSummary($data->eFarm).'/vat/?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo '<div class="tabs-h" id="vat">';

		echo new \overview\VatUi()->getVatTabs($data->eFarm, $data->eFinancialYear, $data->tab);

		switch($data->tab) {

			case NULL:
				echo new \overview\VatUi()->getGeneralTab($data->eFarm, $data->eFinancialYear);
				break;

			case 'journal-buy':
			case 'journal-sell':
				echo new \overview\VatUi()->getOperationsTab($data->eFarm, mb_substr($data->tab, mb_strlen('journal') + 1), $data->cOperation, $data->search, TRUE);
				break;

			case 'check':
				echo new \overview\VatUi()->getCheck($data->eFarm, $data->check);
		}

	echo '</div>';

});

new AdaptativeView('history', function($data, FarmTemplate $t) {

	$t->nav = 'summary';
	$t->subNav = 'vat/';

	$t->title = s("L'historique de TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlSummary($data->eFarm).'/vat/:history';

	$t->mainTitle = new \overview\VatUi()->getHistoryTitle($data->eFarm, $data->cFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlSummary($data->eFarm).'/vat/:history?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);


});
