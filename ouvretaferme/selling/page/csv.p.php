<?php
(new \farm\FarmPage())
	->read('exportSales', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$year = GET('year', 'int');
		$market = GET('market', 'bool', FALSE);

		$export = \selling\AnalyzeLib::getExportSales($data->e, $year, $market);
		array_unshift($export, (new \selling\AnalyzeUi())->getExportSalesHeader($data->e));

		throw new CsvAction($export, 'ventes-'.$year.'.csv');

	})
	->read('exportProducts', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$export = \selling\AnalyzeLib::getExportProducts($data->e);
		array_unshift($export, (new \selling\AnalyzeUi())->getExportProductsHeader($data->e));

		throw new CsvAction($export, 'produits.csv');

	});
?>
