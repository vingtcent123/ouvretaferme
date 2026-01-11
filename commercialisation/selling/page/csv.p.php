<?php
new \farm\FarmPage()
	->read('exportInvoices', function($data) {

		$data->e->validate('canAnalyze', 'canPersonalData');

		$year = GET('year', 'int');

		[$export, $vatRates] = \selling\AnalyzeLib::getExportInvoices($data->e, $year);
		array_unshift($export, new \selling\AnalyzeUi()->getExportInvoicesHeader($data->e, $vatRates));

		throw new CsvAction($export, 'factures-'.$year.'.csv');

	})
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

	});

new \farm\FarmPage()
	->read('importProducts', function($data) {

		$data->eFarm = $data->e;

		if(get_exists('reset')) {
			\selling\CsvLib::resetProducts($data->eFarm);
		}

		$data->cPlant = \plant\PlantLib::getByFarm($data->eFarm);
		$data->data = \selling\CsvLib::getProducts($data->eFarm);

		throw new ViewAction($data, $data->data ? ':importFile' : NULL);

	}, validate: ['canWrite'])
	->write('doImportProducts', function($data) {

		$fw = new FailWatch();

		\selling\CsvLib::uploadProducts($data->e);

		if($fw->ok()) {
			throw new RedirectAction('/selling/csv:importProducts?id='.$data->e['id']);
		} else {
			throw new RedirectAction('/selling/csv:importProducts?id='.$data->e['id'].'&error='.$fw->getLast());
		}


	})
	->write('doCreateProducts', function($data) {

		$data->data = \selling\CsvLib::getProducts($data->e);

		if(
			$data->data === NULL or
			$data->data['errorsCount'] > 0
		) {
			throw new RedirectAction('/selling/csv:importProducts?id='.$data->e['id']);
		}

		$fw = new FailWatch();

		\selling\CsvLib::importProducts($data->e, $data->data['import']);

		$fw->validate();

		throw new RedirectAction('/selling/csv:importProducts?id='.$data->e['id'].'&created');

	});
?>
