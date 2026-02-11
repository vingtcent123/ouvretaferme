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
	->write('doImport', function($data) {

		$source = POST('source', [\cash\Cash::BANK_CASHFLOW, \cash\Cash::SELL_SALE, \cash\Cash::SELL_INVOICE], fn() => throw new NotExpectedAction());
		$reference = POST('reference', 'int');

		$fw = new FailWatch();

		\cash\SuggestionLib::import($data->e, $source, $reference);

		$fw->validate();

		throw new ReloadAction('cash', 'Cash::imported');

	}, validate: ['canUpdate', 'acceptCash'])
	->write('doIgnoreByMethod', function($data) {

		\cash\SuggestionLib::ignoreByMethod($data->e['paymentMethod'], $data->e['closedAt']);

		throw new ReloadAction();

	});
