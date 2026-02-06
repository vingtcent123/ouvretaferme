<?php
new \cash\CashPage()
	->getCreateElement(function($data) {

		return new \cash\Cash([
			'register' => \cash\RegisterLib::getById(INPUT('register'))->validate('acceptCash'),
			'source' => \cash\Cash::INPUT('source', 'source', fn() => throw new NotExpectedAction()),
			'type' => \cash\Cash::INPUT('type', 'type', fn() => throw new NotExpectedAction()),
			'date' => \cash\Cash::INPUT('date', 'date')
		]);

	})
	->create(function($data) {

		if($data->e['date'] !== NULL) {

			$fw = new FailWatch();

			$data->e->build(['date'], ['date' => $data->e['date']]);

			$fw->validate();

			\cash\CashLib::fill($data->e);

		}

		throw new ViewAction($data);

	}, method: ['get', 'post'])
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e['register']).'&success=cash\\Cash::created'));


new \cash\CashPage()
	->update(function($data) {

		\cash\CashLib::fill($data->e);

		throw new ViewAction($data);

	})
	->doUpdate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e['register']).'&success=cash\\Cash::updated'))
	->write('doValidate', function($data) {

		\cash\CashLib::validateUntil($data->e);

		throw new RedirectAction(\farm\FarmUi::urlCash($data->e['register']).'&success=cash\\Cash::validated');

	}, validate: ['canWrite', 'acceptValidate'])
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e['register']).'&success=cash\\Cash::deleted'));
