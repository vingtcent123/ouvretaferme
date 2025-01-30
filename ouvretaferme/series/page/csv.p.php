<?php
(new \farm\FarmPage())
	->read('exportTasks', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$year = GET('year', 'int');

		$export = \series\CsvLib::getExportTasks($data->e, $year);
		array_unshift($export, (new \series\CsvUi())->getExportTasksHeader());

		throw new CsvAction($export, 'planning-'.$year.'.csv');

	})
	->read('exportHarvests', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$year = GET('year', 'int');

		$export = \series\CsvLib::getExportHarvests($data->e, $year);
		array_unshift($export, (new \series\CsvUi())->getExportHarvestsHeader());

		throw new CsvAction($export, 'harvest-'.$year.'.csv');

	})
	->read('exportCultivations', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$year = GET('year', 'int');

		$maxVarieties = NULL;
		$export = \series\CsvLib::getExportCultivations($data->e, $year, $maxVarieties);

		array_unshift($export, (new \series\CsvUi())->getExportCultivationsHeader($maxVarieties));

		throw new CsvAction($export, 'production-'.$year.'.csv');


	})
	->read('exportSoil', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$year = GET('year', 'int');

		$maxSpecies = NULL;
		$export = \series\CsvLib::getExportSoil($data->e, $year, $maxSpecies);

		array_unshift($export, (new \series\CsvUi())->getExportSoilHeader($maxSpecies));

		throw new CsvAction($export, 'soil-'.$year.'.csv');


	})
	->read('importCultivations', function($data) {

		$data->eFarm = $data->e;

		\farm\FarmerLib::register($data->eFarm);

		if(get_exists('reset')) {
			\series\CsvLib::reset($data->eFarm);
		}

		$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, fqn: [ACTION_SEMIS_DIRECT, ACTION_SEMIS_PEPINIERE, ACTION_PLANTATION, ACTION_RECOLTE], index: 'fqn');
		$data->data = \series\CsvLib::getCultivations($data->eFarm);

		throw new ViewAction($data, $data->data ? ':importFile' : NULL);

	}, validate: ['canWrite'])
	->write('doImportCultivations', function($data) {

		$fw = new FailWatch();

		\series\CsvLib::uploadCultivations($data->e);

		if($fw->ok()) {
			throw new RedirectAction('/series/csv:importCultivations?id='.$data->e['id']);
		} else {
			throw new RedirectAction('/series/csv:importCultivations?id='.$data->e['id'].'&error='.$fw->getLast());
		}


	})
	->write('doCreateCultivations', function($data) {

		$data->data = \series\CsvLib::getCultivations($data->e);

		if(
			$data->data === NULL or
			$data->data['errorsCount'] > 0
		) {
			throw new RedirectAction('/series/csv:importCultivations?id='.$data->e['id']);
		}

		$fw = new FailWatch();

		\series\CsvLib::importCultivations($data->e, $data->data['import']);

		$fw->validate();

		throw new RedirectAction('/series/csv:importCultivations?id='.$data->e['id'].'&created');

	});
?>
