<?php
(new Page())
	->post('query', function($data) {

		$data->cFarm = \farm\FarmLib::getFromQuery(POST('query'));

		throw new \ViewAction($data);

	});
?>