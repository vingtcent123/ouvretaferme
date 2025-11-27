<?php
new \farm\FarmPage()
	->read('exportSales', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$year = GET('year', 'int');

		$export = \selling\AnalyzeLib::getExportSales($data->e, $year);
		array_unshift($export, new \selling\AnalyzeUi()->getExportSalesHeader($data->e));

		throw new CsvAction($export, 'ventes-'.$year.'.csv');

	})
	->read('exportItems', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$year = GET('year', 'int');

		$export = \selling\AnalyzeLib::getExportItems($data->e, $year);
		array_unshift($export, new \selling\AnalyzeUi()->getExportItemsHeader($data->e));

		throw new CsvAction($export, 'articles-'.$year.'.csv');

	})
	->read('exportProducts', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$export = \selling\AnalyzeLib::getExportProducts($data->e);
		array_unshift($export, new \selling\AnalyzeUi()->getExportProductsHeader($data->e));

		throw new CsvAction($export, 'produits.csv');

	})
	->read('exportCustomers', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$export = \selling\AnalyzeLib::getExportCustomers($data->e);
		array_unshift($export, new \selling\AnalyzeUi()->getExportCustomersHeader($data->e));

		throw new CsvAction($export, 'clients.csv');

	})
	->read('exportAccounting', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');
		$year = GET('year', 'int');

		$export = \selling\AnalyzeLib::getPreAccountingSales($data->e, $year);
		array_unshift($export, new \selling\AnalyzeUi()->getPreAccountingHeader($data->e));

		throw new CsvAction($export, 'pre-comptabilite.csv');

	});
?>
