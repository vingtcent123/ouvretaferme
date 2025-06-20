<?php
$do = function($data) {

	$data->cFlow = \sequence\FlowLib::getBySequence($data->eSequence);
	$data->events = \sequence\FlowLib::reorder($data->eSequence, $data->cFlow);

	throw new ViewAction($data);

};

new \sequence\FlowPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$sequence = INPUT('sequence', '?int');

		$data->eSequence = \sequence\SequenceLib::getById($sequence)->validate('canWrite');

		$data->cAction = \farm\ActionLib::getByFarm($data->eSequence['farm'], category: CATEGORIE_CULTURE);

	})
	->create()
	->getCreateElement(function($data) {

		$e = new \sequence\Flow([
			'sequence' => $data->eSequence,
			'farm' => $data->eSequence['farm']
		]);

		return $e;

	})
	->doCreate(fn() => throw new ReloadAction());

new \sequence\FlowPage()
	->applyElement(function($data, \sequence\Flow $e) {

		$e['sequence'] = \sequence\SequenceLib::getById($e['sequence'])->validate('canWrite');

		$data->eSequence = $e['sequence'];

	})
	->update(function($data) {

		$data->e['hasMethods'] = \farm\MethodLib::getForWork($data->e['sequence']['farm'], $data->e['action']);
		$data->e['hasTools'] = \farm\ToolLib::getForWork($data->e['sequence']['farm'], $data->e['action']);

		$data->cAction = \farm\ActionLib::getByFarm($data->e['sequence']['farm'], category: CATEGORIE_CULTURE);

		throw new ViewAction($data);

	})
	->doUpdate($do)
	->write('doPosition', function($data) use($do) {

		$positions = POST('positions', 'json', []);

		\sequence\FlowLib::updatePosition($data->e['sequence'], $positions);

		$do($data);

	})
	->write('doIncrementWeek', function($data) use($do) {

		$increment = POST('increment', 'int');
		\sequence\FlowLib::incrementWeek($data->e, $increment);

		$do($data);

	})
	->doDelete($do);

new Page()
	->post('getFields', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');
		$eAction = \farm\ActionLib::getById(POST('action'))->validate('canRead');

		$data->eFlow = new \sequence\Flow([
			'sequence' => new \sequence\Sequence([
				'farm' => $eFarm
			]),
			'action' => $eAction,
			'cTool?' => fn() => new Collection(),
			'hasTools' => \farm\ToolLib::getForWork($eFarm, $eAction),
			'cMethod?' => fn() => new Collection(),
			'hasMethods' => \farm\MethodLib::getForWork($eFarm, $eAction)
		]);

		throw new \ViewAction($data);

	});


(new Page(function($data) {

		$data->c = \sequence\FlowLib::getByIds(REQUEST('ids', 'array'), properties: \sequence\Flow::getSelection() + [
			'sequence' => ['cycle'],
			'plant' => ['name']
		]);

		\sequence\Flow::validateBatch($data->c);

		$data->eFarm = $data->c->first()['farm'];

	}))
	->get('incrementWeekCollection', function($data) {

		throw new ViewAction($data);

	})
	->post('doIncrementWeekCollection', function($data) {

		\sequence\FlowLib::incrementWeekCollection($data->c, POST('increment', 'int'));

		throw new ReloadAction();

	})
	->post('doDeleteCollection', function($data) {

		\sequence\FlowLib::deleteCollection($data->c);

		throw new ReloadAction();

	});
?>
