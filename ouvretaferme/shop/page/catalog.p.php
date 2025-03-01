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
	->update()
	->doUpdate(fn($data) => throw new ReloadAction('shop', 'Catalog::updated'))
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlShopCatalog($data->e['farm']).'?success=shop:Catalog::deleted'));
?>
