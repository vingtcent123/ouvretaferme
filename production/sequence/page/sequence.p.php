<?php
new \sequence\SequencePage()
	->read('/itineraire/{id}', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->e->validate('canRead');

		$data->eFarm = \farm\FarmLib::getById($data->e['farm'])->validate('active');

		$data->cFlow = \sequence\FlowLib::getBySequence($data->e);

		if($data->cFlow->count() === 2) {
			$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'sequence-weeks');
			$data->tipNavigation = 'inline';
		}

		$data->cPhoto = \gallery\PhotoLib::getBySequence($data->e);

		$data->ccSeries = \series\SeriesLib::getBySequence($data->e);

		$data->harvests = \sequence\CropLib::getHarvestsFromFlow($data->cFlow, date('Y'), group: 'month');
		$data->events = \sequence\FlowLib::reorder($data->e, $data->cFlow);

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);

		throw new ViewAction($data, path: ':display');

	});

new \sequence\SequencePage(function($data) {

		\user\ConnectionLib::checkLogged();

	})
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \sequence\Sequence([
			'farm' => $data->eFarm,
		]);

	})
	->create(function($data) {

		$data->e->add([
			'cycle' => \sequence\Sequence::ANNUAL,
			'bedWidth' => $data->eFarm['defaultBedWidth'],
			'alleyWidth' => $data->eFarm['defaultAlleyWidth'],
		]);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(\sequence\SequenceUi::url($data->e).'?success=sequence:Sequence::created');
	});

new \sequence\SequencePage()
	->applyElement(function($data, \sequence\Sequence $e) {
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
	->doUpdate(fn($data) => throw new ReloadAction('sequence', 'Sequence::updated'))
	->doUpdateProperties('doUpdateStatus', ['status'], fn() => throw new ReloadAction('sequence', 'Sequence::updated'))
	->write('doDuplicate', function($data) {

		$data->eSequenceNew = \sequence\SequenceLib::duplicate($data->e);

		throw new RedirectAction(\sequence\SequenceUi::url($data->eSequenceNew).'?success=sequence:Sequence::duplicated');

	})
	->doDelete(fn($data) => throw new RedirectAction(\farm\FarmUi::urlCultivationSequences($data->e['farm']).'?success=sequence:Sequence::deleted'));

new Page()
	->post('query', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canManage');

		$search = new Search([
			'sequences' => POST('ids', 'array')
		]);

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);
		$data->cSequence = \sequence\CropLib::getFromQuery($data->eFarm, $data->cActionMain, POST('query'), $search);

		throw new \ViewAction($data);

	});
?>
