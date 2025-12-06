<?php
new Page()
	->post('cities', function($data) {

		$query = POST('query');
		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');

		$data->locations = \main\PlaceLib::searchCitiesByName($eFarm, $query);

		throw new ViewAction($data);

	});
?>
