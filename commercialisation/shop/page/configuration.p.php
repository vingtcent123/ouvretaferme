<?php
new \shop\ShopPage()
	->update(function($data) {

		$data->e['stripe'] = \payment\StripeLib::getByFarm($data->e['farm']);
		$data->e['cCustomer'] = \selling\CustomerLib::getByIds($data->e['limitCustomers'], sort: ['lastName' => SORT_ASC, 'firstName' => SORT_ASC]);
		$data->e['cPaymentMethod'] = \payment\MethodLib::getByFarm($data->e['farm'], FALSE);

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		$data->cCustomize = \mail\CustomizeLib::getByFarm($data->eFarm, $data->e);
		$data->eSaleExample = \selling\SaleLib::getExample($data->eFarm, \selling\Customer::PRIVATE, $data->e);
		$data->eSaleExample['paymentMethod'] = $data->e['hasPayment'] ? \payment\MethodLib::getByFqn(GET('paymentMethod', 'string', \payment\MethodLib::ONLINE_CARD)) : new \payment\Method();

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
	}, fn() => throw new ReloadAction('shop', 'Shop::updated'), validate: ['canUpdate', 'isPersonal'])
	->doUpdateProperties('doUpdatePaymentMethod', ['paymentMethod'], fn() => throw new ReloadAction('shop', 'Shop::updated'), validate: ['canUpdate', 'isPersonal'])
	->doUpdateProperties('doUpdateEmail', ['emailNewSale', 'emailEndDate'], function($data) {
		throw new ReloadAction('shop', 'Shop::updated');
	})
	->doUpdateProperties('doCustomize', ['customBackground', 'customColor', 'customFont', 'customTitleFont'], fn() => throw new ReloadAction('shop', 'Shop::customized'))
	->doUpdateProperties('doUpdateTerms', ['terms', 'termsField'], fn() => throw new ReloadAction('shop', 'Shop::updated'));
?>
