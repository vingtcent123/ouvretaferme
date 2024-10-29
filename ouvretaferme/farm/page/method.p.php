<?php
(new \farm\MethodPage())
	->getCreateElement(function($data) {

		$data->eAction = \farm\ActionLib::getById(INPUT('action'))->validate('canWrite');

		return new \farm\Method([
			'action' => $data->eAction,
			'farm' => $data->eAction['farm'],
		]);

	})
	->create()
	->doCreate(fn() => throw new ReloadAction('farm', 'Method::created'));

(new \farm\MethodPage())
	->quick(['name'])
	->doDelete(fn() => throw new ReloadAction('farm', 'Method::deleted'));
?>
