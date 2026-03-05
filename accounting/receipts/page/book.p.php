<?php
new \receipts\BookPage()
	->doCreate(fn($data) => throw new RedirectAction(\farm\FarmUi::urlReceipts().'&success=receipts\\Book::created'));

new \receipts\BookPage()
	->write('doClose', function($data) {

		$date = \receipts\Book::POST('date', 'closedAt', fn() => throw new NotExpectedAction());

		\receipts\BookLib::close($data->e, $date);

		throw new ReloadAction('receipts', 'Book::updatedClosed');

	}, validate: ['canUpdate', 'acceptClose'])
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlReceipts().'&success=receipts\\Book::deleted'));
