<?php
new \shop\CatalogPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \shop\Catalog([
			'farm' => $data->eFarm,
		]);

	})
	->create()
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlShopCatalog($data->e['farm']).'?catalog='.$data->e['id'].'&success=shop:Catalog::created'));

new \shop\CatalogPage()
	->read('show', function($data) {

		$data->e['cProduct'] = \shop\ProductLib::getByCatalog($data->e, onlyActive: FALSE, reorderChildren: TRUE);
		$data->e['cCategory'] = \selling\CategoryLib::getByFarm($data->e['farm'], index: 'id');
		$data->e['cCustomer'] = \selling\CustomerLib::getLimitedByProducts($data->e['cProduct']);
		$data->e['cGroup'] = \selling\CustomerGroupLib::getLimitedByProducts($data->e['cProduct']);

		throw new ViewAction($data);

	})
	->update()
	->doUpdate(fn($data) => throw new ReloadAction('shop', 'Catalog::updated'))
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlShopCatalog($data->e['farm']).'?success=shop:Catalog::deleted'));
?>
