<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \association\AdminUi()->create($data->eFarm, $data->cHistory, $data->cMethod);
});
?>
