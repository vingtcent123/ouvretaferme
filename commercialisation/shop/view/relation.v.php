<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \shop\RelationUi()->create($data->eRelation);
});

new AdaptativeView('createCollection', function($data, PanelTemplate $t) {
	return new \shop\RelationUi()->createCollection($data->eFarm, $data->eDate, $data->eCatalog, $data->cProduct);
});
?>
