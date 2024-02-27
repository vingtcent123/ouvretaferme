<?php
$do = function($data) {

	$data->cFlow = \production\FlowLib::getBySequence($data->eSequence);
	$data->events = \production\FlowLib::reorder($data->eSequence, $data->cFlow);

	throw new ViewAction($data);

};

(new \production\FlowPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$sequence = INPUT('sequence', '?int');

		$data->eSequence = \production\SequenceLib::getById($sequence);

		$data->cAction = \farm\ActionLib::getByFarm($data->eSequence['farm'], category: CATEGORIE_CULTURE);

	}))
	->create()
	->getCreateElement(function($data) {

		$e = new \production\Flow([
			'sequence' => $data->eSequence,
			'farm' => $data->eSequence['farm']
		]);

		return $e;

	})
	->doCreate(fn() => throw new ReloadAction());

(new \production\FlowPage())
	->applyElement(function($data, \production\Flow $e) {

		$e['sequence'] = \production\SequenceLib::getById($e['sequence'])->validate('canWrite');

		$data->eSequence = $e['sequence'];

	})
	->update(function($data) {

		$data->e['cTool'] = \production\FlowLib::getTools($data->e);

		$data->cAction = \farm\ActionLib::getByFarm($data->e['sequence']['farm'], category: CATEGORIE_CULTURE);

		$data->cToolAvailable = \farm\ToolLib::getForWork($data->e['sequence']['farm'], $data->e['action']);

		throw new ViewAction($data);

	})
	->doUpdate($do)
	->write('doPosition', function($data) use ($do) {

		$positions = POST('positions', 'json', []);

		\production\FlowLib::updatePosition($data->e['sequence'], $positions);

		$do($data);

	})
	->write('doIncrementWeek', function($data) use ($do) {

		$increment = POST('increment', 'int');
		\production\FlowLib::incrementWeek($data->e, $increment);

		$do($data);

	})
	->doDelete($do);

(new Page())
	->post('getToolsField', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');
		$eAction = \farm\ActionLib::getById(POST('action'))->validate('canRead');

		$data->eFlow = new \production\Flow([
			'sequence' => new \production\Sequence([
				'farm' => $eFarm
			]),
			'action' => $eAction,
			'cTool' => new Collection()
		]);

		$data->cToolAvailable = \farm\ToolLib::getForWork($eFarm, $eAction);

		throw new \ViewAction($data);

	});


(new Page(function($data) {

		$data->c = \production\FlowLib::getByIds(REQUEST('ids', 'array'), properties: \production\Flow::getSelection() + [
			'sequence' => ['cycle'],
			'plant' => ['name']
		]);

		\production\Flow::validateBatch($data->c);

		$data->eFarm = $data->c->first()['farm'];

	}))
	->get('incrementWeekCollection', function($data) {

		throw new ViewAction($data);

	})
	->post('doIncrementWeekCollection', function($data) {

		\production\FlowLib::incrementWeekCollection($data->c, POST('increment', 'int'));

		throw new ReloadAction();

	})
	->post('doDeleteCollection', function($data) {

		\production\FlowLib::deleteCollection($data->c);

		throw new ReloadAction();

	});
?>
