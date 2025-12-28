<?php
new \bank\BankAccountPage(
	function($data) {
	}
)
	->get('index', function($data) {

		$data->cBankAccount = \bank\BankAccountLib::getAll();
		throw new ViewAction($data);

	});

new \bank\BankAccountPage()
	->quick(['label', 'description']);
?>
