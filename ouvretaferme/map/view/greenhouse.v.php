<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \map\GreenhouseUi())->create($data->e);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \map\GreenhouseUi())->update($data->e);

});
?>
