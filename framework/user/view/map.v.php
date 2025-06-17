<?php
new JsonView('getPosition', function($data, AjaxTemplate $t) {

	$t->push('latitude', $data->position['latitude']);
	$t->push('longitude', $data->position['longitude']);
	$t->push('name', $data->position['name'] ?? '');
	$t->push('id', $data->position['id'] ?? NULL);

});
?>
