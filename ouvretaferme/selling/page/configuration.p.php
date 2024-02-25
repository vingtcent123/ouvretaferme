<?php
(new \farm\FarmPage())
	->update(function($data) {

		$data->e['selling'] = \selling\ConfigurationLib::getByFarm($data->e);

		\farm\FarmerLib::register($data->e);

		$data->eSaleExample = \selling\SaleLib::getById(Setting::get('selling\exampleSale'));
		$data->eSaleExample['hasVat'] = $data->e['selling']['hasVat'];
		$data->eSaleExample['customer']['legalName'] = 'Client';
		$data->eSaleExample['customer']['invoiceStreet1'] = '123 rue des Ours';
		$data->eSaleExample['customer']['invoiceStreet2'] = NULL;
		$data->eSaleExample['customer']['invoicePostcode'] = '63000';
		$data->eSaleExample['customer']['invoiceCity'] = 'Clermont-Ferrand';
		$data->eSaleExample['customer']['email'] = 'client@email.com';
		$data->eSaleExample['orderFormPaymentCondition'] = $data->e['selling']['orderFormPaymentCondition'];
		$data->eSaleExample['invoice']['document'] = '287';
		$data->eSaleExample['invoice']['priceExcludingVat'] = 123.57;
		$data->eSaleExample['invoice']['date'] = currentDate();
		$data->eSaleExample['invoice']['paymentCondition'] = $data->e['selling']['invoicePaymentCondition'];

		$data->cItemExample = \selling\SaleLib::getItems($data->eSaleExample);

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	});

(new \selling\ConfigurationPage())
	->doUpdateProperties('doUpdateOrderForm', ['orderFormDelivery', 'orderFormPaymentCondition', 'orderFormHeader', 'orderFormFooter'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdateProperties('doUpdateInvoice', ['invoicePaymentCondition', 'invoiceHeader', 'invoiceFooter'], fn() => throw new ReloadAction('selling', 'Configuration::updated'))
	->doUpdate(fn() => throw new ReloadAction('selling', 'Configuration::updated'));
?>
