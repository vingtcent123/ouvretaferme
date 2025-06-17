<?php
new Page()
	->post('weekChange', function($data) {

		$data->id = POST('id');
		$data->year = POST('year', 'int');
		$data->minYear = POST('minYear', '?int');
		$data->maxYear = POST('maxYear', '?int');
		$data->linkWeeks = POST('linkWeeks');
		$data->linkMonths = POST('linkMonths');
		$data->default = POST('default');
		$data->onclickWeeks = POST('onclickWeeks', '?string');

		throw new ViewAction($data);

	});
?>
