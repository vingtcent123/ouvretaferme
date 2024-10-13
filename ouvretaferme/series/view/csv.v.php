<?php
new AdaptativeView('import', function($data, FarmTemplate $t) {

	$t->title = s("Importer un plan de culture");
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->e);

	echo '<h1>'.s("Exporter les données").'</h1>';
	echo (new \util\CsvLib())->export($data->e, $data->year, $data->hasMarket);

});
?>
