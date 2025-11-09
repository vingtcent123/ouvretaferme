<?php
new Page(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		$data->eCatalog = input_exists('catalog') ?
			\shop\CatalogLib::getById(INPUT('catalog'))->validateProperty('farm', $data->eFarm) :
			new \shop\Catalog();

		$data->eDate = input_exists('date') ?
			\shop\DateLib::getById(INPUT('date'))->validateProperty('farm', $data->eFarm) :
			new \shop\Date();

		if($data->eCatalog->empty() and $data->eDate->empty()) {
			throw new NotExpectedAction('Missing date or catalog');
		}

	})
	->get('createCollection', function($data) {

		$cProduct = \shop\ProductLib::getByIds(GET('products', 'array'));

		if($cProduct->notEmpty()) {
			$cProduct->validateProperty('farm', $data->eFarm);
		}

		$data->cRelation = new Collection();

		foreach($cProduct as $eProduct) {
			$data->cRelation[] = new \shop\Relation([
				'child' => $eProduct
			]);
		}

		throw new \ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$fw = new \FailWatch();

		[$eProduct, $cRelation] = \shop\RelationLib::prepareCollection($data->eFarm, $data->eDate, $data->eCatalog, $_POST);

		$fw->validate();

		\shop\RelationLib::createCollection($eProduct, $cRelation);

		if($data->eDate->notEmpty()) {
			$redirect = \shop\ShopUi::adminDateUrl($data->eFarm, $data->eDate).'?';
		} else if($data->eCatalog->notEmpty()) {
			$redirect = \farm\FarmUi::urlShopCatalog($data->eFarm).'?catalog='.$data->eCatalog['id'].'&';
		}

		throw new RedirectAction($redirect.'success=shop:Product::createdGroup');

	});

new \shop\RelationPage()
	->getCreateElement(function($data) {

		$data->eProduct = \selling\ProductLib::getById(INPUT('parent'));

		return new \shop\Relation([
			'parent' => $data->eProduct,
			'farm' => $data->eProduct['farm']
		]);

	})
	->doCreate(fn($data) => throw new ReloadAction())
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\shop\RelationLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	})
	->doDelete(fn($data) => throw new ReloadAction());

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
