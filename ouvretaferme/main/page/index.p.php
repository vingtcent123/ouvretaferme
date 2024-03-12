<?php
(new Page())
	->get('index', function($data) {

		if($data->eUserOnline->empty()) {
			throw new ViewAction($data, path: ':anonymous');
		}

		$data->cCustomerPro = \selling\CustomerLib::getProByUser($data->eUserOnline);
		$data->cCustomerPrivate = \selling\CustomerLib::getPrivateByUser($data->eUserOnline);

		$data->cShop = \shop\ShopLib::getByCustomers($data->cCustomerPrivate);

		if($data->cCustomerPrivate->notEmpty()) {
			$data->cSale = \selling\SaleLib::getByCustomers($data->cCustomerPrivate, limit: 5);
		} else {
			$data->cSale = new Collection();
		}

		$data->eNews = \website\NewsLib::getLastForBlog();

		throw new ViewAction($data, path: ':logged');

	})
	->get('/presentation/invitation', fn($data) => throw new ViewAction($data))
	->get('/presentation/producteur', fn($data) => throw new ViewAction($data))
	->get('/presentation/formations', fn($data) => throw new ViewAction($data))
	->get('/presentation/faq', fn($data) => throw new ViewAction($data))
	->get('/presentation/legal', fn($data) => throw new ViewAction($data))
	->get('/presentation/service', fn($data) => throw new ViewAction($data));
?>
