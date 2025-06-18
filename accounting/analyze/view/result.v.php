<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Le résultat de {company}", ['company' => $data->eCompany['name']]);
	$t->tab = 'analyze';
	$t->canonical = \company\CompanyUi::urlAnalyze($data->eCompany).'/result';

	$t->mainTitle = new \analyze\AnalyzeUi()->getTitle($data->eCompany);

	$t->mainYear = new \accounting\FinancialYearUi()->getFinancialYearTabs(
		function(\accounting\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlAnalyze($data->eCompany).'/result?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	$t->package('main')->updateNavAnalyze($t->canonical, 'result');

	echo '<div class="tabs-h" id="analyze-result" onrender="'.encode('Lime.Tab.restore(this, "result-month")').'">';

		echo '<div class="tabs-item">';
			echo '<a class="tab-item selected" data-tab="result-month" onclick="Lime.Tab.select(this)">'.s("Mois par mois").'</a>';
			echo '<a class="tab-item" data-tab="result-all" onclick="Lime.Tab.select(this)">'.s("Compte de résultat").'</a>';
		echo '</div>';

		echo '<div class="tab-panel" data-tab="result-month">';
			echo new \analyze\ResultUi()->getByMonth($data->eCompany, $data->eFinancialYear, $data->cOperation);
		echo '</div>';

		echo '<div class="tab-panel" data-tab="result-all">';
			echo new \analyze\ResultUi()->get($data->result, $data->cAccount);
		echo '</div>';

	echo '</div>';

});
