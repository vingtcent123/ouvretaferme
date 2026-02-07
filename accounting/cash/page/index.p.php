<?php
new Page()
	->get('/journal-de-caisse', function($data) {

		$data->cRegister = \cash\RegisterLib::getAll();
		$data->eRegisterCurrent = new \cash\Register();

		if($data->cRegister->empty()) {

			$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-cash');
			$data->tipNavigation = 'inline';

			$data->eRegisterCreate = new \cash\Register([
				'cPaymentMethod' => \payment\MethodLib::getByFarm($data->eFarm, FALSE)
			]);

		} else {

			$register = GET('register', '?int');

			if(
				$register !== NULL and
				$data->cRegister->offsetExists($register)
			) {

				$data->eRegisterCurrent = $data->cRegister[$register];

				\farm\FarmerLib::setView('viewAccountingCashRegister', $data->eFarm, $data->eRegisterCurrent);

			} else if($data->eFarm->getView('viewAccountingCashRegister')->notEmpty()) {

				$data->eRegisterCurrent = $data->cRegister[$data->eFarm->getView('viewAccountingCashRegister')['id']] ?? $data->cRegister->first();

			} else {
				$data->eRegisterCurrent = $data->cRegister->first();
			}

			if($data->eRegisterCurrent['operations'] > 0) {

				$data->page = GET('page', 'int');

				$data->search = new Search([
					'type' => GET('type'),
				]);

				$data->ccCash = \cash\CashLib::getByRegister($data->eRegisterCurrent, $data->page, $data->search);

				$data->cCashflow = \bank\CashflowLib::getForCash($data->eRegisterCurrent['paymentMethod'], $data->eRegisterCurrent['closedAt']);
				$data->cInvoice = \selling\InvoiceLib::getForCash($data->eFarm, $data->eRegisterCurrent['paymentMethod'], $data->eRegisterCurrent['closedAt']);
				$data->cSale = \selling\SaleLib::getForCash($data->eFarm, $data->eRegisterCurrent['paymentMethod'], $data->eRegisterCurrent['closedAt']);

			}

		}

		throw new ViewAction($data);

	});
?>
