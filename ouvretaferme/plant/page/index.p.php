<?php
(new Page())
	->post('query', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm', '?int'));

		if($eFarm->notEmpty()) {
			$eFarm->validate('canWrite');
		}

		if(post_exists('season')) {
			$ids = \series\CultivationLib::getPlantsBySeason($eFarm, POST('season', 'int'))->getIds();
		} else {
			$ids = POST('ids', 'array');
		}

		$search = new Search([
			'ids' => $ids
		]);

		$data->cPlant = \plant\PlantLib::getFromQuery(POST('query'), $eFarm, $search);

		throw new \ViewAction($data);

	});
?>
