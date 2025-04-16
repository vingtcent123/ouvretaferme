<?php
new \shop\DepartmentPage(function($data) {

		\user\ConnectionLib::checkLogged();

	})
	->getCreateElement(function($data) {

		$data->eShop = \shop\ShopLib::getById(INPUT('shop'))->validate('canWrite');

		return new \shop\Department([
			'shop' => $data->eShop,
		]);

	})
	->create()
	->doCreate(fn($data) => throw new RedirectAction(\shop\ShopUi::adminUrl($data->e['shop']['farm'], $data->e['shop']).'?tab=departments&success=Shop:Department::created'));

new \shop\DepartmentPage()
	->update()
	->quick(['name'])
	->doUpdate(fn($data) => throw new ReloadAction('shop', 'Department::updated'))
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\shop\DepartmentLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	})
	->doDelete(fn($data) => throw new ViewAction($data));
?>
