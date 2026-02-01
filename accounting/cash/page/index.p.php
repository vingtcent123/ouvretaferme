<?php
new Page()
	->get('/cahier-de-caisse', function($data) {

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
			} else {
				$data->eRegisterCurrent = $data->cRegister->first();
			}

		}

		throw new ViewAction($data);

	});
?>
