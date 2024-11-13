<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \shop\CatalogUi())->create($data->eFarm);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \shop\CatalogUi())->update($data->e);

});
?>
