<?php
new \farm\FarmPage()
	->update(function($data) {

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);

		$data->eFarm = $data->e;

		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		if(FEATURE_PRE_ACCOUNTING and $data->eFarm->hasAccounting()) {
			\company\CompanyLib::connectSpecificDatabaseAndServer($data->e);
			$data->cAccount = \account\AccountLib::getAll();
		} else {
			$data->cAccount = new Collection();
		}

		throw new ViewAction($data);

	});

new \farm\ConfigurationPage()
	->doUpdateProperties('doUpdateTax', ['taxCountry'], fn() => throw new ReloadAction());

new \farm\ConfigurationPage()
	->applyElement(function($data, \farm\Configuration $eConfiguration) {

		$eConfiguration['farm'] = \farm\FarmLib::getById($eConfiguration['farm'])->validateTax();

	})
	->doUpdateProperties('doUpdateTax', ['taxCountry'], fn() => throw new ReloadAction())
	->doUpdateProperties('doUpdateDeliveryNote', ['documentTarget', 'deliveryNotePrefix', 'deliveryNoteHeader', 'deliveryNoteFooter'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdateProperties('doUpdateOrderForm', ['documentTarget', 'orderFormPrefix', 'orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdateProperties('doUpdateInvoice', ['invoicePrefix', 'documentInvoices', 'creditPrefix', 'invoiceDue', 'invoiceDueDays', 'invoiceDueMonth', 'invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter'], fn() => throw new ReloadAction('farm', 'Configuration::updated'))
	->doUpdate(fn() => throw new ReloadAction('farm', 'Configuration::updated'), onKo: fn() => \farm\Configuration::fail('error'))
	->doUpdateProperties('doUpdateProfileAccount', ['profileAccount'], fn() => throw new ReloadAction('farm', 'Configuration::updated'));
?>
