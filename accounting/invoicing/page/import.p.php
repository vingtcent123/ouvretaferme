<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

})
->get('/ventes/importer', function($data) {

	$data->selectedTab = in_array(GET('tab'), ['market', 'invoice', 'sales']) ? GET('tab') : 'market';

	$from = $data->eFinancialYear['startDate'];
	$to = $data->eFinancialYear['endDate'];

	$data->c = match($data->selectedTab) {
		'market' => \invoicing\ImportLib::getMarketSales($data->eFarm, $from, $to),
		'invoice' => \invoicing\ImportLib::getInvoiceSales($data->eFarm, $from, $to),
	};

	throw new ViewAction($data);

})
->post('doImportInvoice', function($data) {

	$eFinancialYear = \account\FinancialYearLib::getById(POST('financialYear'));

	$eInvoice = \selling\InvoiceLib::getById(POST('id'), \selling\Invoice::getSelection() + [
		'cSale' => \selling\Sale::model()
			->select([
				'id',
				'cPayment' => \selling\Payment::model()
					->select(\selling\Payment::getSelection())
					->or(
					  fn() => $this->whereOnlineStatus(NULL),
					  fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
					)
					->delegateCollection('sale'),
				'cItem' => \selling\Item::model()
					->select(['id', 'price', 'priceStats', 'vatRate', 'account'])
					->delegateCollection('sale')
			])
			->wherePreparationStatus(\selling\Sale::DELIVERED)
			->delegateCollection('invoice'),]);

	$eInvoice->validate('acceptAccountingImport');

	$fw = new FailWatch();

	\invoicing\ImportLib::importInvoice($data->eFarm, $eInvoice, $eFinancialYear);

	$fw->validate();

	throw new ReloadAction('invoicing', 'Invoice::imported');

})
->post('doImportMarket', function($data) {

	$saleModule = clone \selling\Sale::model();
	$eFinancialYear = \account\FinancialYearLib::getById(POST('financialYear'));

	$eSale = \selling\SaleLib::filterForAccounting(
		$data->eFarm, new Search(['from' => $eFinancialYear['startDate'], 'to' => $eFinancialYear['endDate'], 'id' => POST('id')])
	)
		->select([
		'id',
		'document', 'invoice', 'accountingHash', 'preparationStatus', 'closed',
		'type', 'profile', 'marketParent',
		'customer' => [
			'id', 'name',
			'thirdParty' => \account\ThirdParty::model()
				->select('id', 'clientAccountLabel')
				->delegateElement('customer')
		],
		'deliveredAt',
		'cSale' => $saleModule
			->select([
				'id',
				'cPayment' => \selling\Payment::model()
	        ->select(\selling\Payment::getSelection())
	        ->or(
	          fn() => $this->whereOnlineStatus(NULL),
	          fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
	        )
	        ->delegateCollection('sale'),
				'cItem' => \selling\Item::model()
	        ->select(['id', 'price', 'priceStats', 'vatRate', 'account'])
	        ->delegateCollection('sale')
			])
			->wherePreparationStatus(\selling\Sale::DELIVERED)
			->delegateCollection('marketParent'),
	])
		->whereId(POST('id'))
		->whereAccountingHash(NULL)
		->get();

	$eSale->validate('acceptAccountingImport');

	$fw = new FailWatch();

	if($eSale['profile'] === \selling\Sale::MARKET) {
		\invoicing\ImportLib::importMarket($data->eFarm, $eSale, $eFinancialYear);
	}

	$fw->validate();

	throw new ReloadAction('invoicing', $eSale['profile'] === \selling\Sale::MARKET ? 'Sale::imported.market' : 'Sale::imported');


})
->post('doIgnoreMarket', function($data) {

	$eSale = \selling\SaleLib::getById(POST('id'))->validate('acceptAccountingIgnore');

	\invoicing\ImportLib::ignoreSale($eSale);

	throw new ReloadAction('invoicing', $eSale['profile'] === \selling\Sale::MARKET ? 'Sale::ignored.market' : 'Sale::ignored');
})
->post('doIgnoreInvoice', function($data) {

	$eInvoice = \selling\InvoiceLib::getById(POST('id'))->validate('acceptAccountingIgnore');

	\invoicing\ImportLib::ignoreInvoice($eInvoice);

	throw new ReloadAction('invoicing', 'Invoice::ignored');
})
;

?>
