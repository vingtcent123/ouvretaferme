<?php
new \cash\RegisterPage()
	->getCreateElement(function($data) {

		return new \cash\Register([
			'cPaymentMethod' => \payment\MethodLib::getForCash($data->eFarm)
		]);

	})
	->create()
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Register::created'));

new \cash\RegisterPage()
	->update()
	->doUpdate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Register::updated'))
	->write('doClose', function($data) {

		$date = \cash\Register::POST('date', 'closedAt', fn() => throw new NotExpectedAction());

		\cash\RegisterLib::close($data->e, $date);

		throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Register::updatedClosed');

	}, validate: ['canUpdate', 'acceptClose'])
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ReloadAction())
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Register::deleted'));
