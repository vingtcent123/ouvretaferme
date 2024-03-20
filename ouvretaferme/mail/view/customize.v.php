<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \mail\CustomizeUi())->create($data->eCustomizeExisting, $data->eSaleExample);
});
?>
