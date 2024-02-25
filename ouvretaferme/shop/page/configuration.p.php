<?php
(new \shop\ShopPage())
	->update(function($data) {

		$data->e['stripe'] = \payment\StripeLib::getByFarm($data->e['farm']);

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		\farm\FarmerLib::register($data->eFarm);

		throw new ViewAction($data);

	});

(new \shop\ShopPage())
	->applyElement(function($data, \shop\Shop $e) {
		$e['stripe'] = \payment\StripeLib::getByFarm($e['farm']);
	})
	->doUpdate(fn() => throw new ReloadAction('shop', 'Shop::updated'))
	->doUpdateProperties('doUpdatePayment', function(\shop\Shop $e) {
		$properties = ['paymentOfflineHow'];
		if($e['stripe']->notEmpty()) {
			$properties[] = 'paymentCard';
			$properties[] = 'paymentOnlineOnly';
		}
		return $properties;
	}, fn() => throw new ReloadAction('shop', 'Shop::updated'))
	->doUpdateProperties('doUpdateTerms', ['terms', 'termsField'], fn() => throw new ReloadAction('shop', 'Shop::updated'));
?>
