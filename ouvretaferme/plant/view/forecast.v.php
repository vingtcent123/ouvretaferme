<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \plant\ForecastUi())->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \plant\ForecastUi())->update($data->e);
});
?>
