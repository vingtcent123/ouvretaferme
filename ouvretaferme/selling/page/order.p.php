<?php
(new Page(function($data) {
		\user\ConnectionLib::checkLogged();
	}))
	->get('/commandes/particuliers', function($data) {

		$data->ccCustomer = \selling\CustomerLib::getPrivateByUser($data->eUserOnline);
		$data->cSale = \selling\SaleLib::getByCustomers($data->ccCustomer, limit: NULL);

		throw new ViewAction($data);

	})
	->get('/commandes/professionnels/{farm}', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate();
		$data->eCustomer = \selling\CustomerLib::getByUserAndFarm($data->eUserOnline, $data->eFarm)->validate('isPro');

		$data->cSale = \selling\SaleLib::getByCustomer($data->eCustomer);

		throw new ViewAction($data);

	});

(new \selling\SalePage())
	->read('/commande/{id}', function($data) {

		$data->eFarm = $data->e['farm'];

		\farm\FarmerLib::register($data->eFarm);

		$data->cItem = \selling\SaleLib::getItems($data->e);
		$data->e['shopPoint'] = \shop\PointLib::getById($data->e['shopPoint']);

		throw new ViewAction($data);

	}, validate: ['canAccess']);
?>
