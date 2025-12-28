<?php
new \bank\BankAccountPage()
	->get('index', function($data) {

		$data->cBankAccount = \bank\BankAccountLib::getAll();
		throw new ViewAction($data);

	})
	->quick(['label', 'description']);
?>
