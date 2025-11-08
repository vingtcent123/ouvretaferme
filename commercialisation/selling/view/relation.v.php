<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \selling\RelationUi()->create($data->eRelation);
});

new AdaptativeView('createCollection', function($data, PanelTemplate $t) {
	return new \selling\RelationUi()->createCollection($data->eFarm, $data->cProduct);
});
?>
