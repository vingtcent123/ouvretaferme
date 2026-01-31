<?php
new Page()
	->get('/cahier-de-caisse', function($data) {

		//$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-cash');
		//$data->tipNavigation = 'inline';

		$data->cRegister = \cash\RegisterLib::getAll();

		if($data->cRegister->empty()) {

			$data->eRegister = new \cash\Register([
				'cMethod' => \payment\MethodLib::getByFarm($data->eFarm, FALSE)
			]);

		}

		throw new ViewAction($data);

	});

?>
