<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	$t->title = s("Gérer les fermes");

	$uiAdmin = new \company\AdminUi();

	$t->header = '<div class="admin-navigation stick-xs">';
		$t->header .= (new \main\AdminUi())->getNavigation('company');
		$t->header .= $uiAdmin->getNavigation('company');
		$t->header .= $uiAdmin->getCompaniesForm($data->search, $data->nCompany);
	$t->header .= '</div>';

	echo $uiAdmin->displayCompanies($data->cCompany, $data->nCompany, $data->page, $data->search);

});
?>
