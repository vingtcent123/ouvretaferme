<?php
(new \selling\UnitPage())
	->getCreateElement(function($data) {

		return new \selling\Unit([
			'farm' => \farm\FarmLib::getById(INPUT('farm')),
		]);

	})
	->create()
	->doCreate(fn() => throw new ReloadAction('selling', 'Unit::created'));

(new \selling\UnitPage())
	->quick(['singular', 'plural', 'short'])
	->update()
	->doUpdate(fn() => throw new ReloadAction('selling', 'Unit::updated'))
	->doDelete(fn() => throw new ReloadAction('selling', 'Unit::deleted'));

new Page()
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cUnit = \selling\UnitLib::getByFarm($data->eFarm, sort: new \Sql('fqn IS NULL, id ASC'));

		\farm\FarmerLib::register($data->eFarm);

		throw new \ViewAction($data);

	});
?>
