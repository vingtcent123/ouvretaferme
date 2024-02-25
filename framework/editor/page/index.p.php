<?php
(new Page())
	->post('configureVideo', fn($data) => throw new ViewAction($data))
	->post('configureGrid', fn($data) => throw new ViewAction($data))
	->post('configureMedia', function($data) {

		$data->instanceId = POST('instanceId');
		$data->url = POST('url');
		$data->xyz = POST('xyz', '?string');
		$data->title = POST('title');
		$data->link = POST('link');
		$data->figureSize = POST('figureSize', 'int');

		throw new ViewAction($data);

	});
?>
