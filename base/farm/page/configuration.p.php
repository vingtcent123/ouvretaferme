<?php
new \farm\FarmPage()
	->update(function($data) {

		$data->eFarm = $data->e;

		if($data->eFarm->hasAccounting()) {
			\farm\FarmLib::connectDatabase($data->e);
			$data->cAccount = \account\AccountLib::getAll();
		} else {
			$data->cAccount = new Collection();
		}

		throw new ViewAction($data);

	})
	->update(function($data) {

		$data->e->validate('isVerified');
		$data->e->validateLegal();

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);
		$data->eFarm = $data->e;
		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	}, page: 'updateOrderForm')
	->update(function($data) {

		$data->e->validate('isVerified');
		$data->e->validateLegal();

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);
		$data->eFarm = $data->e;
		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	}, page: 'updateDeliveryNote')
	->update(function($data) {

		$data->e->validate('isVerified');
		$data->e->validateLegal();

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);
		$data->eFarm = $data->e;
		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	}, page: 'updateInvoice')
	->update(function($data) {

		$data->eFarm = $data->e;
		$data->eFarm['eFinancialYear'] = \account\FinancialYearLib::getById(GET('financialYear'));

		throw new ViewAction($data);

	}, page: 'updateVat');

new \farm\ConfigurationPage()
	->applyElement(function($data, \farm\Configuration $eConfiguration) {

		$eConfiguration['farm'] = \farm\FarmLib::getById($eConfiguration['farm'])->validateVerified();
		$eConfiguration['hasVatAccountingOld'] = $eConfiguration['hasVatAccounting'];

	})
	->doUpdateProperties('doUpdateDeliveryNote', ['documentTarget', 'deliveryNoteHeader', 'deliveryNoteFooter'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdateProperties('doUpdateOrderForm', ['documentTarget', 'orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdateProperties('doUpdateInvoice', ['invoicePrefix', 'documentInvoices', 'invoiceDue', 'invoiceDueDays', 'invoiceDueMonth', 'invoiceReminder', 'invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter', 'invoiceMandatoryTexts', 'invoiceCollection', 'invoiceLateFees', 'invoiceDiscount'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdateProperties('doUpdateInvoiceMention', ['invoiceMandatoryTexts', 'invoiceCollection', 'invoiceLateFees', 'invoiceDiscount'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdateProperties('doUpdateVat', ['hasVatAccounting', 'vatFrequency', 'vatChargeability'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdate(fn() => throw new ReloadAction('farm', 'Configuration::updated'), onKo: fn() => \farm\Configuration::fail('error'))
	->doUpdateProperties('doUpdateProfileAccount', ['profileAccount'], fn() => throw new ReloadAction('farm', 'Configuration::updated'));
?>
