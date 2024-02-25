<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \map\PlotUi())->create($data->eZone);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \map\PlotUi())->update($data->e);

});
?>
