<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \hr\AbsenceUi())->create($data->eFarm, $data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \hr\AbsenceUi())->update($data->e);
});
?>
