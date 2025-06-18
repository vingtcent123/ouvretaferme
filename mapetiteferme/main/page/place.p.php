<?php
(new Page())
	->post('cities', function($data) {

		$query = POST('query');
		$data->locations = \main\PlaceLib::searchCitiesByName($query);

		throw new ViewAction($data);

	});
?>
