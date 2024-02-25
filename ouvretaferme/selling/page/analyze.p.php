<?php
(new \farm\FarmPage())
	->read('export', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');
		$data->e['selling'] = \selling\ConfigurationLib::getByFarm($data->e);

		$year = GET('year', 'int');

		$export = \selling\AnalyzeLib::getExport($data->e, $year);
		array_unshift($export, (new \selling\AnalyzeUi())->getExportHeader($data->e));

		throw new CsvAction($export, 'sales-'.$year.'.csv');

	});
?>
