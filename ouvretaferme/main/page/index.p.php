<?php
new Page()
	->get('index', function($data) {

		if($data->eUserOnline->empty()) {

			if(FEATURE_GAME) {
				$data->ePlayer = new \game\Player();
			}

			throw new ViewAction($data, path: ':anonymous');
		}

		$data->cCustomerPro = \selling\CustomerLib::getProByUser($data->eUserOnline);
		$data->cCustomerPrivate = \selling\CustomerLib::getPrivateByUser($data->eUserOnline);

		$data->cShop = \shop\ShopLib::getByCustomers(
			new Collection()
				->mergeCollection($data->cCustomerPrivate)
				->mergeCollection($data->cCustomerPro)
		);

		if($data->cCustomerPrivate->notEmpty()) {
			$data->cSale = \selling\SaleLib::getByCustomers($data->cCustomerPrivate, limit: 5);
			$data->cInvoice = \selling\InvoiceLib::getByCustomers($data->cCustomerPrivate, limit: 5);
		} else {
			$data->cSale = new Collection();
			$data->cInvoice = new Collection();
		}

		$data->cNews = \website\NewsLib::getLastForBlog();

		if(FEATURE_GAME) {
			$data->ePlayer = \game\PlayerLib::getOnline();
		}

		throw new ViewAction($data, path: ':logged');

	})
	->get('/presentation/invitation', fn($data) => throw new ViewAction($data))
	->get('/presentation/producteur', fn($data) => throw new ViewAction($data))
	->get('/presentation/formations', fn($data) => throw new ViewAction($data))
	->get('/presentation/faq', fn($data) => throw new ViewAction($data))
	->get('/presentation/service', fn($data) => throw new ViewAction($data))
	->get('/presentation/adhesion', fn($data) => throw new ViewAction($data))
	->get('/facturation-electronique-les-mains-dans-les-poches', fn($data) => throw new ViewAction($data));
?>
