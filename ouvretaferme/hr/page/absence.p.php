<?php
(new \hr\AbsencePage(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

	}))
	->getCreateElement(function($data) {

		$e = new \hr\Absence([
			'farm' => $data->eFarm,
			'cUser' => \farm\FarmerLib::getUsersByFarm($data->eFarm)
		]);

		if(request_exists('user')) {
			$e['user'] = $e['cUser'][REQUEST('user', 'int')] ?? new \user\User();
		} else {
			$e['user'] = new \user\User();
		}

		return $e;

	})
	->create(function($data) {

		throw new \ViewAction($data);

	})
	->doCreate(function($data) {
		throw new BackAction('hr', 'Absence::created');
	});

(new \hr\AbsencePage())
	->update()
	->doUpdate(function($data) {
		throw new BackAction('hr', 'Absence::updated');
	})
	->doDelete(function($data) {
		throw new ReloadLayerAction('hr', 'Absence::deleted');
	});
?>
