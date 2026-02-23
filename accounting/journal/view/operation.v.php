<?php

new AdaptativeView('/journal/operation/{id}', function($data, PanelTemplate $t) {

	return new \journal\OperationUi()->getView($data->eFarm, $data->e);

});
new AdaptativeView('/journal/operation/{id}/update', function($data, PanelTemplate $t) {

	return new \journal\OperationUi()->getUpdate($data->eFarm, $data->eFarm['eFinancialYear'], $data->cOperation, $data->cPaymentMethod, $data->cJournalCode, $data->eCashflow, $data->e, $data->hasVatAccounting);

});
new JsonView('/journal/operation/{id}/doUpdate', function($data, AjaxTemplate $t) {

	// Objectif : permettre à l'utilisateur de faire page arrière sans se retrouver avec une 404.
	$t->js()->replaceHistory(\company\CompanyUi::urlJournal($data->eFarm).'/operation/'.$data->cOperation->first()['id'].'/update');
	$t->js()->location($data->url);

});

new AdaptativeView('delete', function($data, PanelTemplate $t) {

	return new \journal\OperationUi()->getDelete($data->eFarm, $data->cOperation, $data->e);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

		return new \journal\OperationUi()->create(
			$data->eFarm,
			$data->e,
			$data->eFarm['eFinancialYear'],
			$data->cPaymentMethod,
			$data->cJournalCode,
			$data->hasVatAccounting
		);

});


new AdaptativeView('createDocumentCollection', function($data, PanelTemplate $t) {

	return new \journal\OperationUi()->createDocumentCollection($data->eFarm, $data->c);

});

new JsonView('selectThirdParty', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$form->open('journal-operation-create');

	$index = $data->index;
	$suffix = '['.$index.']';
	$disabled = [];

	$eOperation = new \journal\Operation(['thirdParty' => $data->eThirdParty]);

	$t->qs('[data-wrapper="thirdParty'.$suffix.'"]')->innerHtml($form->dynamicField($eOperation, 'thirdParty'.$suffix, function($d) use($form, $index, $disabled, $suffix) {
		$d->autocompleteDispatch = '[data-third-party="'.$form->getId().'"][data-index="'.$index.'"]';
		$d->attributes['data-index'] = $index;
		if(in_array('thirdParty', $disabled) === TRUE) {
			$d->attributes['disabled'] = TRUE;
		}
		$d->attributes['data-third-party'] = $form->getId();
		$d->default = fn($e, $property) => get('thirdParty');
	}));

});

new JsonView('selectAccount', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$form->open('journal-operation-create');

	$index = $data->index;
	$suffix = '['.$index.']';
	$eOperation = new \journal\Operation(['account' => $data->eAccount, 'accountLabel' => \account\AccountLabelLib::pad($data->eAccount['class'])]);
	$disabled = [];

	$t->qs('[data-wrapper="account'.$suffix.'"]')->innerHtml($form->dynamicField($eOperation, 'account'.$suffix, function($d) use($form, $index, $disabled, $suffix) {
		$d->autocompleteDispatch = '[data-account="'.$form->getId().'"][data-index="'.$index.'"]';
		$d->attributes['data-index'] = $index;
		if(in_array('account', $disabled) === TRUE) {
			$d->attributes['disabled'] = TRUE;
		}
		$d->attributes['data-account'] = $form->getId();
		$d->default = fn($e, $property) => get('account');
	}));

	$t->qs('[data-wrapper="accountLabel'.$suffix.'"]')->innerHtml($form->dynamicField($eOperation, 'accountLabel'.$suffix, function($d) use($form, $index, $suffix) {
		$d->autocompleteDispatch = '[data-account-label="'.$form->getId().'"][data-index="'.$index.'"]';
		$d->attributes['data-wrapper'] = 'accountLabel'.$suffix;
		$d->attributes['data-index'] = $index;
		$d->attributes['data-account-label'] = $form->getId();
		$d->label .=  ' '.\util\FormUi::asterisk();
	}));

});

new JsonView('addOperation', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$form->open('journal-operation-create');
	$defaultValues = [];

	$t->qs('.operation-create-several-container')->setAttribute('data-columns', $data->index + 1);
	$t->qs('.operation-create[data-index="'.($data->index - 1).'"]')->insertAdjacentHtml(
		'afterend',
		new \journal\OperationUi()::getFieldsCreateGrid(
			$form,
			$data->eOperation,
			new \bank\Cashflow(),
			$data->eFarm['eFinancialYear'],
			'['.$data->index.']',
			$defaultValues,
			[],
			$data->cPaymentMethod,
			$data->hasVatAccounting,
		)
	);
	$t->qs('#add-operation')->setAttribute('post-index', $data->index + 1);
	if($data->index >= 8) {
		$t->qs('#add-operation')->addClass('not-visible');
	}
	$t->js()->eval('Operation.showOrHideDeleteOperation()');
	$t->js()->eval('Operation.preFillNewOperation('.$data->index.')');

});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = [];
	$found = FALSE;
	$notLinked = FALSE;

	$nThirdParty = $data->cOperation->find(fn($e) => $e['isThirdParty'] === TRUE)->count();

	foreach($data->cOperation as $eOperation) {

		if($nThirdParty === 0 and $notLinked === FALSE) {

			$results[] = [
				'type' => 'title',
				'itemHtml' => '<div>'.s("Toutes les écritures comptables (non liées au tiers)").'</div>',
				'itemText' => s("Toutes les écritures comptables (non liées au tiers)"),
			];

			$notLinked = TRUE;

		} else if($found === FALSE and $eOperation['isThirdParty'] === TRUE) {

			$results[] = [
				'type' => 'title',
				'itemHtml' => '<div>'.p("Écriture comptable liée au tiers", "Écritures comptables liées au tiers", $nThirdParty).'</div>',
				'itemText' => p("Écriture comptable liée au tiers", "Écritures comptables liées au tiers", $nThirdParty),
			];

			$found = TRUE;

		} else if($found === TRUE and $eOperation['isThirdParty'] === FALSE) {

			$results[] = [
				'type' => 'title',
				'itemHtml' => '<div>'.s("Toutes les autres écritures comptables").'</div>',
				'itemText' => s("Toutes les autres écritures comptables"),
			];

			$found = NULL;

		}

		$results[] = \journal\OperationUi::getAutocomplete($data->eCashflow, $eOperation);

	}


	$t->push('results', $results);

});

new JsonView('queryForDeferral', function($data, AjaxTemplate $t) {

	$results = [];

	foreach($data->cOperation as $eOperation) {
		$results [] = \journal\OperationUi::getAutocompleteDeferral($data->eFarm, $eOperation);
	}

	$t->push('results', $results);

});


new JsonView('queryDescription', function($data, AjaxTemplate $t) {

	$results = [];
	foreach($data->descriptions as $description) {
		$results[] = \journal\OperationUi::getAutocompleteDescriptions($description);
	}

	$t->push('results', $results);

});


?>
