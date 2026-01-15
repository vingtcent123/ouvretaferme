<?php
new Page()
	->post('cities', function($data) {

		$query = POST('query');
		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');

		$data->locations = \main\PlaceLib::searchCitiesByName($eFarm, $query);

		throw new ViewAction($data);

	})
	->get('siret', function($data) {

		$siret = \farm\FarmLib::getSiretApi(GET('siret'));

		throw new JsonAction(['result' => $siret]);

	});
?>
