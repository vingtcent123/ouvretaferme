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
	->read('importCultivations', function($data) {

		$data->eFarm = $data->e;

		\farm\FarmerLib::register($data->eFarm);

		$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, fqn: [ACTION_SEMIS_DIRECT, ACTION_PLANTATION, ACTION_RECOLTE], index: 'fqn');
		$data->cultivation = \series\CsvLib::importCultivations($data->eFarm);

		throw new ViewAction($data, $data->cultivation ? ':importFile' : NULL);

	})
	->write('doImportCultivations', function($data) {

		$fw = new FailWatch();

		\series\CsvLib::saveCultivations($data->e);

		if($fw->ok()) {
			throw new RedirectAction('/series/csv:importCultivations?id='.$data->e['id']);
		} else {
			throw new RedirectAction('/series/csv:importCultivations?id='.$data->e['id'].'&error='.$fw->getLast());
		}


	});
?>
