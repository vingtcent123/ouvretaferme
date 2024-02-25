<?php
new JsonView('cities', function($data, AjaxTemplate $t) {

	$results = [];

	foreach($data->locations as $location) {

		$results[] = [
			'value' => $location['text'],
			'itemHtml' => $location['place_name'],
			'itemText' => $location['text'],
			'lngLat' => $location['center']
		];

	}
	$t->push('results', $results);

});