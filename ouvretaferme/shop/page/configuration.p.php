<?php
new \shop\ShopPage()
	->update(function($data) {

		$data->e['stripe'] = \payment\StripeLib::getByFarm($data->e['farm']);
		$data->e['cCustomer'] = \selling\CustomerLib::getByIds($data->e['limitCustomers'], sort: ['lastName' => SORT_ASC, 'firstName' => SORT_ASC]);

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		\farm\FarmerLib::register($data->eFarm);

		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm, $data->e);
		$data->eSaleExample = \selling\SaleLib::getExample($data->eFarm, \selling\Customer::PRIVATE, $data->e);
		$data->eSaleExample['paymentMethod'] = $data->e['hasPayment'] ? \selling\Sale::GET('paymentMethod', 'paymentMethod', \selling\Sale::OFFLINE) : NULL;

		throw new ViewAction($data);

	});

new \shop\ShopPage()
	->applyElement(function($data, \shop\Shop $e) {
		$e['stripe'] = \payment\StripeLib::getByFarm($e['farm']);
	})
	->doUpdate(fn() => throw new ReloadAction('shop', 'Shop::updated'))
	->doUpdateProperties('doUpdatePayment', function(\shop\Shop $e) {
		$properties = ['paymentOffline', 'paymentOfflineHow', 'paymentTransfer', 'paymentTransferHow'];
		if($e['stripe']->notEmpty()) {
			$properties[] = 'paymentCard';
		}
		return $properties;
	}, fn() => throw new ReloadAction('shop', 'Shop::updated'))
	->doUpdateProperties('doCustomize', ['customBackground', 'customColor', 'customFont', 'customTitleFont'], fn() => throw new ReloadAction('shop', 'Shop::customized'))
	->doUpdateProperties('doUpdateTerms', ['terms', 'termsField'], fn() => throw new ReloadAction('shop', 'Shop::updated'));
?>
