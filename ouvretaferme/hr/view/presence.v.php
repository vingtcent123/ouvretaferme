<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \hr\PresenceUi())->create($data->eFarm, $data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \hr\PresenceUi())->update($data->e);
});
?>
