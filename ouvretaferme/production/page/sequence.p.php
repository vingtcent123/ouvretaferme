<?php
(new \production\SequencePage())
	->read('/itineraire/{id}', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->e->validate('canRead');

		$data->eFarm = \farm\FarmLib::getById($data->e['farm'])->validate('active');

		\farm\FarmerLib::register($data->eFarm);

		$data->cFlow = \production\FlowLib::getBySequence($data->e);

		if($data->cFlow->count() === 2) {
			$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'sequence-weeks');
			$data->tipNavigation = 'inline';
		}

		$data->cPhoto = \gallery\PhotoLib::getBySequence($data->e);

		$data->ccSeries = \series\SeriesLib::getBySequence($data->e);

		$data->harvests = \production\CropLib::getHarvestsFromFlow($data->cFlow, date('Y'), group: 'month');
		$data->events = \production\FlowLib::reorder($data->e, $data->cFlow);

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);

		throw new ViewAction($data, path: ':display');

	});

(new \production\SequencePage(function($data) {

		\user\ConnectionLib::checkLogged();

	}))
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \production\Sequence([
			'farm' => $data->eFarm,
		]);

	})
	->create(function($data) {

		$eFarmer = \farm\FarmerLib::getOnlineByFarm($data->eFarm);

		$data->e->add([
			'cycle' => \production\Sequence::ANNUAL,
			'bedWidth' => $data->eFarm['defaultBedWidth'],
			'alleyWidth' => $data->eFarm['defaultAlleyWidth'],
		]);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(\production\SequenceUi::url($data->e).'?success=production:Sequence::created');
	});

(new \production\SequencePage())
	->applyElement(function($data, \production\Sequence $e) {
		$e->validate('canWrite');
	})
	->read('restoreComment', function($data) {
		throw new ViewAction($data, path: ':getComment');
	}, method: 'post')
	->read('updateComment', fn($data) => throw new ViewAction($data), method: 'post')
	->doUpdateProperties('doUpdateComment', ['comment'], function($data) {
		throw new ViewAction($data, path: ':getComment');
	})
	->quick(['name', 'bedWidth', 'mode'])
	->update()
	->doUpdate(fn($data) => throw new ReloadAction('production', 'Sequence::updated'))
	->doUpdateProperties('doUpdateStatus', ['status'], fn() => throw new ReloadAction('production', 'Sequence::updated'))
	->write('doDuplicate', function($data) {

		$data->eSequenceNew = \production\SequenceLib::duplicate($data->e);

		throw new RedirectAction(\production\SequenceUi::url($data->eSequenceNew).'?success=production:Sequence::duplicated');

	})
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCultivationSequences($data->e['farm']).'?success=production:Sequence::deleted'));

(new Page())
	->post('query', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canManage');

		$search = new Search([
			'sequences' => POST('ids', 'array')
		]);

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);
		$data->cSequence = \production\CropLib::getFromQuery($data->eFarm, $data->cActionMain, POST('query'), $search);

		throw new \ViewAction($data);

	});
?>
