<?php
(new \farm\FarmPage())
	->update(function($data) {

		\farm\FarmerLib::register($data->e);

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);

		$data->eFarm = $data->e;

		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	});

(new \selling\ConfigurationPage())
	->doUpdateProperties('doUpdateDeliveryNote', ['deliveryNotePrefix'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdateProperties('doUpdateOrderForm', ['orderFormPrefix', 'orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdateProperties('doUpdateInvoice', ['invoicePrefix', 'documentInvoices', 'creditPrefix', 'invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdate(fn() => throw new ReloadAction('selling', 'Configuration::updated'));
?>
