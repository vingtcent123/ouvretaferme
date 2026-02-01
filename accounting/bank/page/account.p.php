<?php
new \bank\BankAccountPage()
	->get('index', function($data) {

		$data->cBankAccount = \bank\BankAccountLib::getAllWithCashflow();
		throw new ViewAction($data);

	});
new \bank\BankAccountPage()
	->applyElement(function($data, \bank\BankAccount $e) {
		$e['farm'] = $data->eFarm;
	})
	->quick(['description'])
;

new \bank\BankAccountPage()
	->applyElement(function($data, \bank\BankAccount $e) {
		$e['cCashflow'] = \bank\Cashflow::model()
			->select(\bank\Cashflow::getSelection())
      ->whereAccount($e)
      ->getCollection();
		$e['nCashflow'] = $e['cCashflow'];
	})
	->doDelete(fn($data) => throw new ReloadAction('bank', 'BankAccount::deleted'));
?>
