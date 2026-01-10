<?php
new \farm\FarmPage()
	->update(function($data) {

		$data->eFarm = $data->e;

		if($data->eFarm->hasAccounting()) {
			\company\CompanyLib::connectDatabase($data->e);
			$data->cAccount = \account\AccountLib::getAll();
		} else {
			$data->cAccount = new Collection();
		}

		throw new ViewAction($data);

	})
	->update(function($data) {

		$data->e->validate('isTax');
		$data->e->validateLegal();

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);
		$data->eFarm = $data->e;
		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	}, page: 'updateOrderForm')
	->update(function($data) {

		$data->e->validate('isTax');
		$data->e->validateLegal();

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);
		$data->eFarm = $data->e;
		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	}, page: 'updateDeliveryNote')
	->update(function($data) {

		$data->e->validate('isTax');
		$data->e->validateLegal();

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);
		$data->eFarm = $data->e;
		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	}, page: 'updateInvoice');

new \farm\ConfigurationPage()
	->doUpdateProperties('doUpdateTax', ['taxCountry'], fn() => throw new ReloadAction());

new \farm\ConfigurationPage()
	->applyElement(function($data, \farm\Configuration $eConfiguration) {

		$eConfiguration['farm'] = \farm\FarmLib::getById($eConfiguration['farm'])->validateTax();

	})
	->doUpdateProperties('doUpdateTax', ['taxCountry'], fn() => throw new ReloadAction())
	->doUpdateProperties('doUpdateDeliveryNote', ['documentTarget', 'deliveryNoteHeader', 'deliveryNoteFooter'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdateProperties('doUpdateOrderForm', ['documentTarget', 'orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdateProperties('doUpdateInvoice', ['invoicePrefix', 'documentInvoices', 'creditPrefix', 'invoiceDue', 'invoiceDueDays', 'invoiceDueMonth', 'invoiceReminder', 'invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdate(fn() => throw new ReloadAction('farm', 'Configuration::updated'), onKo: fn() => \farm\Configuration::fail('error'))
	->doUpdateProperties('doUpdateProfileAccount', ['profileAccount'], fn() => throw new ReloadAction('farm', 'Configuration::updated'));
?>
