<?php
new \bank\BankAccountPage()
	->get('index', function($data) {

		$data->cBankAccount = \bank\BankAccountLib::getAll();
		throw new ViewAction($data);

	});
new \bank\BankAccountPage()
	->applyElement(function($data, \bank\BankAccount $e) {
		$e['farm'] = $data->eFarm;
	})
	->quick(['label', 'description']);
?>
