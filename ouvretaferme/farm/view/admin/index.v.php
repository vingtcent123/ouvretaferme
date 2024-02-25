<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	$t->title = s("Gérer les fermes");

	$uiAdmin = new \farm\AdminUi();

	$t->header = '<div class="admin-navigation stick-xs">';
		$t->header .= (new \main\AdminUi())->getNavigation('farm');
		$t->header .= $uiAdmin->getNavigation('farm');
		$t->header .= $uiAdmin->getFarmsForm($data->search, $data->nFarm);
	$t->header .= '</div>';

	echo $uiAdmin->displayFarms($data->cFarm, $data->nFarm, $data->page, $data->search);

});
?>
