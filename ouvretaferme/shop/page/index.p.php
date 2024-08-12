<?php
(new shop\ShopPage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \shop\Shop([
			'farm' => $data->eFarm
		]);

	})
	->create()
	->doCreate(function($data) {
		throw new RedirectAction(\shop\ShopUi::adminUrl($data->eFarm, $data->e));
	})
	->read('website', function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		$data->eWebsite = \website\WebsiteLib::getByFarm($data->eFarm);
		$data->eDate = \shop\DateLib::getMostRelevantByShop($data->e, one: TRUE);

		throw new \ViewAction($data);

	}, validate: ['canWrite'])
	->read('emails', function($data) {

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);
		$data->emails = \shop\ShopLib::getEmails($data->e);

		throw new \ViewAction($data);

	}, validate: ['canWrite'])
	->doUpdateProperties('doUpdateStatus', ['status'], function($data) {
		throw new ReloadAction('shop', $data->e['status'] === \shop\Shop::OPEN ? 'Shop::opened' : 'Shop::closed');
	})
	->doUpdateProperties('doUpdatePayment', ['hasPayment'], function($data) {
		throw new ReloadAction('shop', $data->e['hasPayment'] ? 'Shop::paymentOn' : 'Shop::paymentOff');
	})
	->doUpdateProperties('doUpdatePoint', ['hasPoint'], function($data) {
		throw new ReloadAction('shop', $data->e['hasPoint'] ? 'Shop::pointOn' : 'Shop::pointOff');
	})
	->doDelete(function() {
		throw new ReloadAction('shop', 'Shop::deleted');
	});
?>
