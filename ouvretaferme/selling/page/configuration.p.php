<?php
(new \farm\FarmPage())
	->update(function($data) {

		$data->e['selling'] = \selling\ConfigurationLib::getByFarm($data->e);

		\farm\FarmerLib::register($data->e);

		$data->eSaleExample = \selling\SaleLib::getExample($data->e, \selling\Customer::PRO);

		$data->eFarm = $data->e;

		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	});

(new \selling\ConfigurationPage())
	->doUpdateProperties('doUpdateOrderForm', ['orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdateProperties('doUpdateInvoice', ['invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdate(fn() => throw new ReloadAction('selling', 'Configuration::updated'));
?>
