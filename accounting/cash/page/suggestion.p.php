<?php
new Page(function($data) {

		$data->eFarm->validate('canAccounting');

	})
	->post('doIgnore', function($data) {

		$source = POST('source', [\cash\Cash::BANK_CASHFLOW, \cash\Cash::SELL_SALE, \cash\Cash::SELL_INVOICE], fn() => throw new NotExpectedAction());
		$reference = POST('reference', 'int');

		\cash\SuggestionLib::ignore($source, $reference);

		throw new ReloadAction();

	});

new \cash\RegisterPage()
	->write('doIgnoreByMethod', function($data) {

		\cash\SuggestionLib::ignoreByMethod($data->e['paymentMethod'], $data->e['closedAt']);

		throw new ReloadAction();

	});
