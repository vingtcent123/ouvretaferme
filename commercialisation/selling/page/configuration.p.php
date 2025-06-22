<?php
new \farm\FarmPage()
	->update(function($data) {

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);

		$data->eFarm = $data->e;

		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	});

new \selling\ConfigurationPage()
	->doUpdateProperties('doUpdateDeliveryNote', ['documentTarget', 'deliveryNotePrefix'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdateProperties('doUpdateOrderForm', ['documentTarget', 'orderFormPrefix', 'orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdateProperties('doUpdateInvoice', ['invoicePrefix', 'documentInvoices', 'creditPrefix', 'invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdate(fn() => throw new ReloadAction('selling', 'Configuration::updated'), onKo: fn() => \selling\Configuration::fail('error'));
?>
