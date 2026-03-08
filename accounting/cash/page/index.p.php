<?php
new Page()
	->get('/journal-de-caisse', function($data) {

		$data->ccRegister = \cash\RegisterLib::getList();
		$data->eRegisterCurrent = new \cash\Register();

		if($data->ccRegister->empty()) {

			$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-cash');
			$data->tipNavigation = 'inline';

			$data->eRegisterCreate = new \cash\Register([
				'cPaymentMethod' => \payment\MethodLib::getForCash($data->eFarm)
			]);

			throw new ViewAction($data);

		} else {

			$register = GET('register', '?int');

			if($register === NULL) {
				$data->eRegisterCurrent = new \cash\Register();
			} else {

				$data->eRegisterCurrent = $data->ccRegister->find(fn($eRegister) => $eRegister['id'] === $register, depth: 2, limit: 1);

			}

			if($data->eRegisterCurrent->empty()) {

				if($data->ccRegister->countByDepth(2) === 1) {
					$data->eRegisterCurrent = $data->ccRegister->find(fn($eRegister) => TRUE, depth: 2, limit: 1);
				} else {
					throw new ViewAction($data);
				}


			}

			if($data->eRegisterCurrent['operations'] > 0) {

				$data->page = GET('page', 'int');

				$data->search = new Search([
					'type' => GET('type'),
					'source' => GET('source'),
					'account' => GET('account'),
				]);

				$data->ccCash = \cash\CashLib::getByRegister($data->eRegisterCurrent, $data->page, $data->search);

				$data->cCashflow = \cash\SuggestionLib::getForCashflow($data->eRegisterCurrent['paymentMethod'], $data->eRegisterCurrent['closedAt']);
				$data->cInvoice = \cash\SuggestionLib::getForInvoice($data->eRegisterCurrent['paymentMethod'], $data->eRegisterCurrent['closedAt']);
				$data->cSale = \cash\SuggestionLib::getForSale($data->eRegisterCurrent['paymentMethod'], $data->eRegisterCurrent['closedAt']);

			}

			throw new ViewAction($data, ':get');

		}

	});
?>
