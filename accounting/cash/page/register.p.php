<?php
new \cash\RegisterPage()
	->getCreateElement(function($data) {

		return new \cash\Register([
			'cPaymentMethod' => \payment\MethodLib::getByFarm($data->eFarm, FALSE)
		]);

	})
	->create()
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Register::created'));

new \cash\RegisterPage()
	->update()
	->doUpdate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Register::updated'))
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ReloadAction())
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Register::deleted'));
