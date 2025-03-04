<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \website\MenuUi()->create($data->eWebsite, $data->cWebpage, $data->for);
});
?>
