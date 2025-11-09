<?php
new Page(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		$data->eCatalog = input_exists('catalog') ?
			\shop\CatalogLib::getById(INPUT('catalog'))->validateProperty('farm', $data->eFarm) :
			new \shop\Catalog();

		$data->eDate = input_exists('date') ?
			\shop\DateLib::getById(INPUT('date'))->validateProperty('farm', $data->eFarm) :
			new \shop\Date();

	})
	->get('createCollection', function($data) {

		$data->cProduct = \shop\ProductLib::getByIds(GET('products', 'array'));

		if($data->cProduct->notEmpty()) {
			$data->cProduct->validateProperty('farm', $data->eFarm);
		}

		throw new \ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$fw = new \FailWatch();

		[$eProduct, $cRelation] = \shop\RelationLib::prepareCollection($data->eFarm, $_POST);

		$fw->validate();

		\shop\RelationLib::createCollection($eProduct, $cRelation);

		throw new RedirectAction(\selling\ProductUi::url($eProduct).'?success=selling:Product::createdGroup');

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
?>
