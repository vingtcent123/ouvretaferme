<?php
new \shop\ProductPage()
	->getCreateElement(function($data) {

		$eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		$eCatalog = input_exists('catalog') ?
			\shop\CatalogLib::getById(INPUT('catalog'))->validateProperty('farm', $eFarm) :
			new \shop\Catalog();

		$eDate = input_exists('date') ?
			\shop\DateLib::getById(INPUT('date'))->validateProperty('farm', $eFarm) :
			new \shop\Date();

		if($eCatalog->empty() and $eDate->empty()) {
			throw new NotExpectedAction('Missing date or catalog');
		}

		return new \shop\Product([
			'farm' => $eFarm,
			'date' => $eDate,
			'catalog' => $eCatalog,
			'type' => $eDate->notEmpty() ? $eDate['type'] : $eCatalog['type'],
			'parent' => TRUE,
			'price' => 0
		]);

	})
	->create(function($data) {

		$cProduct = \shop\ProductLib::getByIds(GET('products', 'array'));

		if($cProduct->notEmpty()) {
			$cProduct->validateProperty('farm', $data->e['farm']);
		}

		$cRelation = new Collection();

		foreach($cProduct as $eProduct) {
			$cRelation[] = new \shop\Relation([
				'child' => $eProduct
			]);
		}

		$data->e['cRelation'] = $cRelation;

		throw new \ViewAction($data);

	})
	->doCreate(function($data) {

		if($data->e['date']->notEmpty()) {
			$redirect = \shop\ShopUi::adminDateUrl($data->e['farm'], $data->e['date']).'?';
		} else if($data->e['catalog']->notEmpty()) {
			$redirect = \farm\FarmUi::urlShopCatalog($data->e['farm']).'?catalog='.$data->e['catalog']['id'].'&';
		}

		throw new RedirectAction($redirect.'success=shop:Product::createdGroup');

	});

new \shop\RelationPage()
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\shop\RelationLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	});

new Page()
	->post('query', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');

		$eCatalog = post_exists('catalog') ?
			\shop\CatalogLib::getById(POST('catalog'))->validateProperty('farm', $eFarm) :
			new \shop\Catalog();

		$eDate = post_exists('date') ?
			\shop\DateLib::getById(POST('date'))->validateProperty('farm', $eFarm) :
			new \shop\Date();

		$withRelations = POST('relations', 'bool', TRUE);

		$cProduct = \shop\ProductLib::getFromQuery(POST('query'), $eFarm, $eCatalog, $eDate, $withRelations);
		$data->cRelation = new Collection();

		foreach($cProduct as $eProduct) {
			$data->cRelation[] = new \shop\Relation([
				'child' => $eProduct
			]);
		}

		throw new \ViewAction($data);

	});
?>
