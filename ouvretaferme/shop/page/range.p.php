<?php
new \shop\RangePage(function($data) {

		\user\ConnectionLib::checkLogged();

	})
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canWrite');
		$data->eShop = \shop\ShopLib::getById(INPUT('shop'))->validateShareRead($data->eFarm);

		return new \shop\Range([
			'shop' => $data->eShop,
			'farm' => $data->eFarm,
			'cCatalog' => \shop\CatalogLib::getForRange($data->eFarm, $data->eShop)
		]);

	})
	->create()
	->doCreate(fn($data) => throw new RedirectAction(\shop\ShopUi::adminUrl($data->e['shop']['farm'], $data->e['shop']).'?tab=farmers&success=Shop:Range::created'));

new \shop\RangePage()
	->doUpdateProperties('doUpdateStatus', ['status'], fn() => throw new ReloadAction())
	->doUpdateProperties('doUpdateDepartment', ['department'], fn() => throw new ReloadAction())
	->read('dissociate', fn($data) => throw new ViewAction($data))
	->write('doDissociate', function($data) {

		if(POST('date') === '') {
			throw new \FailAction('shop\Range::missingDate');
		}

		\shop\RangeLib::dissociate($data->e, POST('date', 'bool'));

		throw new ReloadAction('shop', 'Range::deleted');

	});
?>
