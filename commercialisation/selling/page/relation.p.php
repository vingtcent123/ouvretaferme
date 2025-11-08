<?php
new Page(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

	})
	->get('createCollection', function($data) {

		$data->cProduct = \selling\ProductLib::getByIds(GET('products', 'array'));

		if($data->cProduct->notEmpty()) {
			$data->cProduct->validateProperty('farm', $data->eFarm);
		}

		throw new \ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$fw = new \FailWatch();

		[$eProduct, $cRelation] = \selling\RelationLib::prepareCollection($data->eFarm, $_POST);

		$fw->validate();

		\selling\RelationLib::createCollection($eProduct, $cRelation);

		throw new RedirectAction(\selling\ProductUi::url($eProduct).'?success=selling:Product::createdGroup');

	});

new \selling\RelationPage()
	->getCreateElement(function($data) {

		$data->eProduct = \selling\ProductLib::getById(INPUT('parent'));

		return new \selling\Relation([
			'parent' => $data->eProduct,
			'farm' => $data->eProduct['farm']
		]);

	})
	->doCreate(fn($data) => throw new ReloadAction())
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\selling\RelationLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	})
	->doDelete(fn($data) => throw new ReloadAction());
?>
