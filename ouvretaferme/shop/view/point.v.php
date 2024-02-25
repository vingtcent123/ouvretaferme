<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \shop\PointUi())->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \shop\PointUi())->update($data->e);
});
?>
