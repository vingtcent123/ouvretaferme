<?php
new \cash\CashPage()
	->getCreateElement(function($data) {

		$eCash = new \cash\Cash([
			'register' => \cash\RegisterLib::getById(INPUT('register'))->validate('acceptCash'),
			'source' => \cash\Cash::INPUT('source', 'source', fn() => throw new NotExpectedAction()),
			'type' => \cash\Cash::INPUT('type', 'type', fn() => throw new NotExpectedAction()),
			'date' => \cash\Cash::INPUT('date', 'date')
		]);

		if($eCash['register']->acceptOperation($eCash['source'], $eCash['type']) === FALSE) {
			throw new NotExpectedAction();
		}

		$fw = new FailWatch();

		if(
			Page::getName() === 'doCreate' or
			(Page::getName() === 'create' and $eCash['date'] !== NULL)
		) {
			$eCash->build(['date'], ['date' => $eCash['date']]);
		}

		$fw->validate();

		return $eCash;

	})
	->create(function($data) {

		if($data->e['date'] !== NULL) {

			\cash\CashLib::fill($data->e);

		}

		throw new ViewAction($data);

	}, method: ['get', 'post'])
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCash($data->e['register']).'&success=cash\\Cash::created'));

new \cash\RegisterPage()
	->read('updateBalance', function($data) {

		throw new ViewAction($data);

	}, validate: ['acceptUpdateBalance', 'canWrite'])
	->write('doUpdateBalance', function($data) {

		$balance = POST('balance', 'float');

		$eCash = new \cash\Cash([
			'register' => $data->e,
			'source' => \cash\Cash::BALANCE
		]);

		if($balance < 0) {
			throw new FailAction('cash\Cash::balance.negative');
		} else if($balance !== $data->e['balance']) {

			if($balance < $data->e['balance']) {

				$eCash->merge([
					'type' => \cash\Cash::DEBIT,
					'amountIncludingVat' => $data->e['balance'] - $balance
				]);

			} else {

				$eCash->merge([
					'type' => \cash\Cash::CREDIT,
					'amountIncludingVat' => $balance - $data->e['balance']
				]);

			}

			$fw = new FailWatch();

			$eCash->build(['date', 'description'], $_POST);

			$fw->validate();

			\cash\CashLib::create($eCash);

		}

		throw new RedirectAction(\farm\FarmUi::urlCash($data->e).'&success=cash\\Cash::updatedBalance');

	}, validate: ['acceptUpdateBalance', 'canWrite']);

new \cash\CashPage()
	->update(function($data) {

		\cash\CashLib::fill($data->e);

		throw new ViewAction($data);

	})
	->doUpdate(fn($data) => throw new ReloadAction('cash', 'Cash::updated'))
	->write('doValidate', function($data) {

		\cash\CashLib::validateUntil($data->e);

		throw new RedirectAction(\farm\FarmUi::urlCash($data->e['register']).'&success=cash\\Cash::validated');

	}, validate: ['canWrite', 'acceptValidate'])
	->doDelete(fn($data) => throw new ReloadAction('cash', 'Cash::deleted'));
