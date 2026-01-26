<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	$t->title = s("Suivi de la comptabilité");

	$t->header = '<div class="admin-navigation stick-xs">';
		$t->header .= new \main\AdminUi()->getNavigation('company');
	$t->header .= '</div>';

	echo new \company\AdminUi()->displayStats($data->nFarms);
	echo new \company\AdminUi()->displayFarms($data->cFarm, $data->search);


});

?>
