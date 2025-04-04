<?php
new \farm\FarmPage()
	->read('tasks', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');
		$data->e->saveFeaturesAsSettings();

		$data->page = GET('page', 'int');

		$data->year = GET('year', 'int');
		$data->month = GET('month', fn($value) => Filter::check(['int', 'min' => 1, 'max' => 12], $value));
		$data->week = GET('week', fn($value) => Filter::check('?week', $value));
		$data->years = \series\AnalyzeLib::getYears($data->e);

		$data->eFarm = $data->e;

		$data->search = new Search([
			'year' => $data->year,
			'month' => $data->month,
			'week' => $data->week,
			'plant' => get_exists('plant') ? \plant\PlantLib::getById(GET('plant')) : NULL,
			'user' => get_exists('user') ? \user\UserLib::getById(GET('user')) : new \user\User(),
			'action' => get_exists('action') ? \farm\ActionLib::getByFarm($data->eFarm, id: GET('action')) : NULL,
			'series' => get_exists('series') ? \series\SeriesLib::getById(GET('series'), ['id', 'name']) : NULL,
			'category' => get_exists('category') ? \farm\CategoryLib::getByFarm($data->eFarm, id: GET('category')) : NULL,
			'status' => \series\Task::GET('status', 'status'),
		]);

		if(get_exists('noSeries')) {
			$data->search->set('series', new \series\Series());
		}

		[$data->cTask, $nTask] = \series\TaskLib::getByFarm($data->e, $data->page, $data->search);
		$data->cUserFarm = \farm\FarmerLib::getUsersByFarm($data->e);

		\farm\ActionLib::getMainByFarm($data->e);

		$data->pages = $nTask / 100;

		throw new ViewAction($data);

	});
?>
