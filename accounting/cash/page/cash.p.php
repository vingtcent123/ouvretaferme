<?php
new \cash\CashPage()
	->getCreateElement(function($data) {

		return new \cash\Cash([
			'register' => \cash\RegisterLib::getById(INPUT('register'))->validate('acceptCash'),
			'source' => \cash\Cash::INPUT('source', 'source'),
			'type' => \cash\Cash::INPUT('type', 'type')
		]);

	})
	->create()
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e['register']).'&success=cash\\Register::created'));

/*
new \cash\CashPage()
	->update()
	->doUpdate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Register::updated'))
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ReloadAction())
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Register::deleted'));
*/