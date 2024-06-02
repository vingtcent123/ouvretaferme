<?php
(new \farm\FarmPage())
	->read('export', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');
		$data->e['selling'] = \selling\ConfigurationLib::getByFarm($data->e);

		$year = GET('year', 'int');
		$market = GET('market', 'bool', FALSE);

		$export = \selling\AnalyzeLib::getExport($data->e, $year, $market);
		array_unshift($export, (new \selling\AnalyzeUi())->getExportHeader($data->e));

		throw new CsvAction($export, 'sales-'.$year.'.csv');

	});
?>
