<?php
(new Page())
	->post('query', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm', '?int'));

		if($data->eFarm->notEmpty()) {
			$data->eFarm->validate('canWrite');
		}

		if(post_exists('season')) {
			$ids = \series\CultivationLib::getPlantsBySeason($data->eFarm, POST('season', 'int'))->getIds();
		} else {
			$ids = POST('ids', 'array');
		}

		$search = new Search([
			'ids' => $ids
		]);

		$data->cPlant = \plant\PlantLib::getFromQuery(POST('query'), $data->eFarm, $search);

		$data->hasNew = post_exists('new');

		throw new \ViewAction($data);

	});
?>
