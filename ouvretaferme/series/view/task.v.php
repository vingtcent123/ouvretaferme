<?php
new AdaptativeView('/tache/{id}', function($data, PanelTemplate $t) {
	return (new \series\TaskUi())->getOne($data->e, $data->cPlace, $data->cPhoto, $data->cUser, $data->cComment);
});

new AdaptativeView('incrementPlannedCollection', function($data, PanelTemplate $t) {
	return (new \series\TaskUi())->updateIncrementPlannedCollection($data->c);
});

new AdaptativeView('doCheck', function($data, AjaxTemplate $t) {

	$t->qsa('.flow-timeline-description[data-task="'.$data->e['id'].'"]')->outerHtml((new \series\TaskUi())->getDescription($data->e));

});

new AdaptativeView('getToolsField', function($data, AjaxTemplate $t) {

	if($data->cToolAvailable->empty()) {
		$t->push('field', '');
	} else {
		$t->push('field', (new \util\FormUi())->dynamicGroup($data->eTask, 'toolsList'));
	}


});

new AdaptativeView('getVarietiesField', function($data, AjaxTemplate $t) {

	if($data->eCultivation['cSlice']->empty()) {
		$t->push('field', '');
	} else {

		$cVariety = $data->eCultivation['cSlice']->getColumnCollection('variety');

		$t->push('field', (new \series\TaskUi())->getVarietyGroup(new \util\FormUi(), $data->e, $cVariety));
	}


});

new AdaptativeView('createFromSeries', function($data, PanelTemplate $t) {

	$t->js()->replaceHistory(LIME_URL);

	if($data->eSeries->notEmpty()) {
		return (new \series\TaskUi())->createFromOneSeries($data->e, $data->eSeries, $data->cToolAvailable);
	} else {
		return (new \series\TaskUi())->createFromAllSeries($data->e, $data->cSeries, $data->cToolAvailable);
	}

});

new AdaptativeView('createFromScratch', function($data, PanelTemplate $t) {
	$t->js()->replaceHistory(LIME_URL);
	return (new \series\TaskUi())->createFromScratch($data->e, $data->cAction, $data->cCategory, $data->cZone, $data->cToolAvailable);
});

new JsonView('doCreateFromSeriesCollection', function($data, AjaxTemplate $t) {

	if($data->e['status'] === \series\Task::DONE) {

		$eAction = $data->c->first()['action'];

		$ids = $data->c->makeArray(fn($e) => 'ids[]='.$e['id']);

		if($eAction['fqn'] === ACTION_RECOLTE) {
			$t->ajaxRedirect('/series/task:updateHarvestCollection?'.implode('&', $ids), purgeLayers: TRUE);
			$t->ajaxReload(purgeLayers: FALSE); // Le contexte principal ne doit pas interférer
		} else {

			if($data->eFarm->hasFeatureTime()) {
				$t->ajaxRedirect('/series/timesheet?'.implode('&', $ids), purgeLayers: TRUE);
				$t->ajaxReload(purgeLayers: FALSE); // Le contexte principal ne doit pas interférer
			} else {
				$t->ajaxReload();
			}

		}


	} else {
		$t->ajaxReload();
	}

});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	if(POST('soil', 'bool')) {

		$t->ajaxRedirect('/series/place:update?task='.$data->e['id'], purgeLayers: TRUE);
		$t->ajaxReload(purgeLayers: FALSE); // Le contexte principal ne doit pas interférer

	} else {

		if($data->e['status'] === \series\Task::DONE) {

			if($data->e['doneDate']) {
				$date = '&date='.$data->e['doneDate'];
			} else {
				$date = '';
			}

			if($data->e['action']['fqn'] === ACTION_RECOLTE) {

				$t->ajaxRedirect('/series/task:updateHarvestCollection?ids[]='.$data->e['id'].$date, purgeLayers: TRUE);
				$t->ajaxReload(purgeLayers: FALSE);

			} else {

				if($data->eFarm->hasFeatureTime()) {
					$t->ajaxRedirect('/series/timesheet?ids[]='.$data->e['id'].$date, purgeLayers: true);
					$t->ajaxReload(purgeLayers: FALSE);
				} else {
					$t->ajaxReload();
				}

			}

		} else {
			$t->ajaxReload();
		}

	}

});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \series\TaskUi())->update($data->e, $data->cAction, $data->cZone, $data->cToolAvailable);
});

new AdaptativeView('updateHarvestCollection', function($data, PanelTemplate $t) {
	return (new \series\TaskUi())->updateHarvestCollection($data->c);
});

new AdaptativeView('updatePlannedCollection', function($data, PanelTemplate $t) {
	return (new \series\TaskUi())->updatePlannedCollection($data->c);
});

new JsonView('doUpdateHarvestCollection', function($data, AjaxTemplate $t) {

	$ids = $data->c->makeArray(fn($e) => 'ids[]='.$e['id']);

	if($data->eFarm->hasFeatureTime()) {
		$t->ajaxRedirect('/series/timesheet?'.implode('&', $ids).'&date='.$data->harvestDate, purgeLayers: TRUE);
		$t->ajaxReload(purgeLayers: FALSE); // Le contexte principal ne doit pas interférer
	} else {
		$t->ajaxReload();
	}

});

new AdaptativeView('updateCultivation', function($data, PanelTemplate $t) {
	return (new \series\TaskUi())->updateCultivation($data->e, $data->cCultivation);
});

new JsonView('doUpdateCultivation', function($data, AjaxTemplate $t) {

	$t->ajaxRedirect(\series\SeriesUi::url($data->e['series']), purgeLayers: true);
	$t->js()->success('series', 'Task::cultivationUpdated');

});

new JsonView('getCreateCollectionFields', function($data, AjaxTemplate $t) {

	$t->qs('#task-create-variety')->innerHtml((new \series\TaskUi())->getVarietyGroup(
		new \util\FormUi(),
		new \series\Task(),
		$data->cVariety,
		$data->varietiesIntersect
	));

	$t->qs('#task-create-quality')->innerHtml($data->eTask['cQuality']->notEmpty() ? (new \series\TaskUi())->getHarvestQualityField(
		new \util\FormUi(),
		$data->eTask
	) : '');

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->ajaxReload();
	$t->js()->success('series', 'Task::deleted');

});
?>
