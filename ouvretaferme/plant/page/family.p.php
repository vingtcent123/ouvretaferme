<?php
(new Page())
	/**
	 * Form to close its account
	 */
	->get('/famille/{fqn@fqn}', function($data) {

		$data->eFamily = \plant\FamilyLib::getByFqn(REQUEST('fqn'));

		if($data->eFamily->empty()) {
			throw new RedirectAction('/familles');
		}

		$data->cPlant =\plant\PlantLib::getByFamily($data->eFamily, new \farm\Farm());

		throw new ViewAction($data, path: ':family');

	})
	/**
	 * Form to close its account
	 */
	->get('/ferme/{farm}/famille/{family}', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canRead');
		$data->eFamily = \plant\FamilyLib::getById(GET('family'));

		if($data->eFamily->empty()) {
			throw new RedirectAction('/familles');
		}

		$data->cPlant =\plant\PlantLib::getByFamily($data->eFamily, $data->eFarm);

		throw new ViewAction($data, path: ':family');

	});
?>
