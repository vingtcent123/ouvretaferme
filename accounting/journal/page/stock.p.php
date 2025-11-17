<?php
new \journal\StockPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
	}
)
	->create(function($data) {

		$data->e['date'] = $data->eFinancialYear['endDate'];

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		\account\LogLib::save('create', 'Stock', ['id' => $data->e['id']]);

		throw new ReloadAction('journal', 'Stock::created');

	})
	->doDelete(function($data) {

		throw new ReloadAction('journal', 'Stock::deleted');

	})
	->get('set', callback: function($data) {

		$data->e = \journal\StockLib::getById(GET('id'))->validate('canSet');
		$data->eFinancialYear = \account\FinancialYearLib::getById(GET('financialYear'))->validate('canUpdate');

		throw new ViewAction($data);

	})
	->post('doSet', function($data) {

		$eStock = \journal\StockLib::getById(POST('id'))->validate('canSet');

		\journal\StockLib::setNewStock($eStock, $_POST);

		throw new ReloadAction('journal', 'Stock::set');

	})
	->post('reset', function($data) {

		$eStock = \journal\StockLib::getById(POST('id'))->validate('canSet');

		$newValues = [
			'finalStock' => 0,
			'variation' => 0 - $eStock['finalStock'],
			'financialYear' => POST('financialYear'),
		];
		\journal\StockLib::setNewStock($eStock, $newValues);

		throw new ReloadAction('journal', 'Stock::reset');

	})
	->post('renew', function($data) {

		$eStock = \journal\StockLib::getById(POST('id'))->validate('canSet');

		$newValues = [
			'finalStock' => $eStock['finalStock'],
			'variation' => 0,
			'financialYear' => POST('financialYear'),
		];
		\journal\StockLib::setNewStock($eStock, $newValues);

		throw new ReloadAction('journal', 'Stock::renew');

	})
;
