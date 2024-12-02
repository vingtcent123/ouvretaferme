<?php
new AdaptativeView('tasks', function($data, FarmTemplate $t) {

	$t->title = s("Liste des interventions");
	$t->tab = 'home';

	$t->subNav = (new \farm\FarmUi())->getPlanningSubNav($data->e);
	$t->mainTitle = '<h1>'.s("Liste des interventions").'</h1>';

	echo (new \series\AnalyzeUi())->getTasks($data->e, $data->cTask, $data->cUserFarm, $data->pages, $data->page, $data->search);
});
?>