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

new AdaptativeView('getFields', function($data, AjaxTemplate $t) {

	if($data->eTask['hasTools']->empty()) {
		$t->push('tools', '');
	} else {
		$t->push('tools', (new \util\FormUi())->dynamicGroup($data->eTask, 'toolsList'));
	}

	if($data->eTask['hasMethods']->empty()) {
		$t->push('methods', '');
	} else {
		$t->push('methods', (new \util\FormUi())->dynamicGroup($data->eTask, 'methods'));
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

	if($data->eSeries->notEmpty()) {
		return (new \series\TaskUi())->createFromOneSeries($data->e, $data->eSeries);
	} else {
		return (new \series\TaskUi())->createFromAllSeries($data->e, $data->cSeries);
	}

});

new AdaptativeView('createFromScratch', function($data, PanelTemplate $t) {
	return (new \series\TaskUi())->createFromScratch($data->e, $data->cAction, $data->cCategory);
});

new JsonView('doCreateFromSeriesCollection', function($data, AjaxTemplate $t) {

	if($data->e['status'] === \series\Task::DONE) {

		$eTaskFirst = $data->c->first();

		$eAction = $eTaskFirst['action'];

		if($eTaskFirst['doneDate']) {
			$date = '&date='.$eTaskFirst['doneDate'];
		} else if($eTaskFirst['doneWeek']) {
			if($eTaskFirst['doneWeek'] === currentWeek()) {
				$date = '&date='.currentDate();
			} else {
				$date = '&date='.week_date_starts($eTaskFirst['doneWeek']);
			}
		} else {
			$date = '';
		}

		$ids = $data->c->makeArray(fn($e) => 'ids[]='.$e['id']);

		if($eAction['fqn'] === ACTION_RECOLTE) {
			$t->ajaxRedirect('/series/task:updateHarvestCollection?'.implode('&', $ids).$date, purgeLayers: TRUE);
			$t->ajaxReload(purgeLayers: FALSE); // Le contexte principal ne doit pas interférer
		} else {

			if($data->eFarm->hasFeatureTime()) {
				$t->ajaxRedirect('/series/timesheet?'.implode('&', $ids).$date, purgeLayers: TRUE);
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

	if($data->e['status'] === \series\Task::DONE) {

		if($data->e['doneDate']) {
			$date = '&date='.$data->e['doneDate'];
		} else if($data->e['doneWeek']) {
			if($data->e['doneWeek'] === currentWeek()) {
				$date = '&date='.currentDate();
			} else {
				$date = '&date='.week_date_starts($data->e['doneWeek']);
			}
		} else {
			$date = '';
		}

		if($data->e['action']['fqn'] === ACTION_RECOLTE) {

			$t->ajaxRedirect('/series/task:updateHarvestCollection?ids[]='.$data->e['id'].$date, purgeLayers: TRUE);

		} else {

			if($data->eFarm->hasFeatureTime()) {
				$t->ajaxRedirect('/series/timesheet?ids[]='.$data->e['id'].$date, purgeLayers: true);
			} else {
				$t->ajaxRedirect('/tache/'.$data->e['id'], purgeLayers: true);
			}

		}

	} else {
		$t->ajaxRedirect('/tache/'.$data->e['id'], purgeLayers: true);
	}

	$t->ajaxReload(purgeLayers: FALSE);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \series\TaskUi())->update($data->e, $data->cAction);
});

new AdaptativeView('updateHarvestCollection', function($data, PanelTemplate $t) {
	return (new \series\TaskUi())->updateHarvestCollection($data->c, $data->cProductStock, $data->eProductBookmark);
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

new JsonView('doUpdateUserCollection', function($data, AjaxTemplate $t) {

	switch(POST('reload')) {

		case 'context' :
			$t->ajaxReload();
			break;

		case 'layer' :
			$t->ajaxReloadLayer();
			$t->ajaxReload(purgeLayers: FALSE); // Le contexte principal ne doit pas interférer
			break;

	};

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

	$t->qs('#task-create-size')->innerHtml($data->eTask['cSize']->notEmpty() ? (new \series\TaskUi())->getHarvestSizeField(
		new \util\FormUi(),
		$data->eTask
	) : '');

});

new JsonView('getStockField', function($data, AjaxTemplate $t) {

	$t->qs('#task-harvest-stock')->outerHtml((new \series\TaskUi())->getStockField(
		new \util\FormUi(),
		$data->e,
		$data->cProductStock,
		$data->eProductBookmark
	));

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->ajaxReload();
	$t->js()->success('series', 'Task::deleted');

});
?>
