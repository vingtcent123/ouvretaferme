<?php
(new \mail\CustomizePage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \mail\Customize([
			'id' => NULL,
			'farm' => $data->eFarm,
			'type' => \mail\Customize::INPUT('type', 'type', fn() => throw new NotExpectedAction('Invalid type')),
			'shop' => input_exists('shop') ? \shop\ShopLib::getById(INPUT('shop'))->validate('canWrite') : new \shop\Shop()
		]);

	})
	->create(function($data) {

		$data->eCustomizeExisting = \mail\CustomizeLib::getExisting($data->e);

		if($data->eCustomizeExisting->exists() === FALSE) {
			$data->eCustomizeExisting = $data->e;
		} else {
			$data->eCustomizeExisting['farm'] = $data->eFarm;
		}

		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		$data->eSaleExample = \selling\SaleLib::getExample(
			$data->eFarm,
			$data->e['shop']->notEmpty() ? \selling\Customer::PRIVATE : \selling\Customer::PRO,
			$data->e['shop']
		);

		throw new ViewAction($data);

	})
	->doCreate(fn($data) => throw new ReloadAction('mail', 'Customize::created'));
?>
