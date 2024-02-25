<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \website\NewsUi())->create($data->eWebsite);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \website\NewsUi())->update($data->e);
});
?>
