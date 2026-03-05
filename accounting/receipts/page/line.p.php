<?php
new \receipts\LinePage()
	->getCreateElement(function($data) {

		$eLine = new \receipts\Line([
			'book' => \receipts\BookLib::getById(INPUT('book'))->validate('acceptLine'),
			'source' => \receipts\Line::INPUT('source', 'source', fn() => throw new NotExpectedAction()),
			'type' => \receipts\Line::INPUT('type', 'type', fn() => throw new NotExpectedAction()),
			'date' => \receipts\Line::INPUT('date', 'date')
		]);

		if($eLine['book']->acceptOperation($eLine['source'], $eLine['type']) === FALSE) {
			throw new NotExpectedAction();
		}

		$fw = new FailWatch();

		if(
			Page::getName() === 'doCreate' or
			(Page::getName() === 'create' and $eLine['date'] !== NULL)
		) {
			$eLine->build(['date'], ['date' => $eLine['date']]);
		}

		$fw->validate();

		return $eLine;

	})
	->create(function($data) {

		if($data->e['date'] !== NULL) {

			\receipts\LineLib::fill($data->e);

		}

		throw new ViewAction($data);

	}, method: ['get', 'post'])
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlReceipts().'&success=receipts\\Line::created'));

new \receipts\LinePage()
	->update(function($data) {

		\receipts\LineLib::fill($data->e);

		throw new ViewAction($data);

	})
	->doUpdate(fn($data) => throw new ReloadAction('receipts', 'Line::updated'))
	->write('doValidate', function($data) {

		\receipts\LineLib::validateUntil($data->e);

		throw new RedirectAction(\farm\FarmUi::urlReceipts().'&success=receipts\\Line::validated');

	}, validate: ['canWrite', 'acceptValidate'])
	->doDelete(fn($data) => throw new ReloadAction('receipts', 'Line::deleted'));
