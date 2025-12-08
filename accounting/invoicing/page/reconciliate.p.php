<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

	})
	->get('/ventes/rapprocher', function($data) {

		$from = $data->eFinancialYear['startDate'];
		$to = $data->eFinancialYear['endDate'];

		$data->numberImport = [
			'market' => \farm\AccountingLib::countMarkets($data->eFarm, $from, $to),
			'invoice' => \farm\AccountingLib::countInvoices($data->eFarm, $from, $to),
			'sales' => \farm\AccountingLib::countSales($data->eFarm, $from, $to)
		];

		$data->numberReconciliate = 0;

		throw new ViewAction($data);

	});
