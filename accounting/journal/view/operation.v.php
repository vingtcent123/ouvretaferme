<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

		return new \journal\OperationUi()->create(
			$data->eFarm,
			$data->e,
			$data->eFinancialYear,
			['grant' => $data->cAssetGrant, 'asset' => $data->cAssetToLinkToGrant],
			$data->cPaymentMethod,
		);

});

new AdaptativeView('createPayment', function($data, PanelTemplate $t) {

		return new \journal\OperationUi()->createPayment($data->eFarm, $data->eFinancialYear, $data->e, $data->cBankAccount);

});

new AdaptativeView('createCommentCollection', function($data, PanelTemplate $t) {

		return new \journal\OperationUi()->createCommentCollection($data->eFarm);

});

new AdaptativeView('createDocumentCollection', function($data, PanelTemplate $t) {

		return new \journal\OperationUi()->createDocumentCollection($data->eFarm);

});

new JsonView('readInvoice', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$form->open('journal-operation-create');

	$t->qs('.operation-invoice-preview')->removeHide();
	$t->js()->eval('Operation.deactivateInvoiceImport()');

	if($data->operation['mimetype'] === 'application/pdf') {

		$t->qs('.operation-invoice-preview > embed')->setAttribute('src', 'data:'.$data->operation['mimetype'].';base64,'.base64_encode(file_get_contents($data->operation['filepath'])));
		$t->qs('.operation-invoice-preview > embed')->removeHide();

	} else {

		$t->qs('.operation-invoice-preview > img')->setAttribute('src', 'data:'.$data->operation['mimetype'].';base64,'.base64_encode(file_get_contents($data->operation['filepath'])));
		$t->qs('.operation-invoice-preview > img')->removeHide();

	}

	$t->qs('input[name="invoiceFile"]')->setAttribute('value', $data->operation['filename']);

	// Saisie de la 1ère opération
	$t->qs('input[name="date[0]"]')->setAttribute('value', $data->operation['date']);
	$t->qs('input[name="amount[0]-calculation"]')->setAttribute('value', $data->operation['prices']['amount'] ?? 0);
	$t->qs('input[name="amount[0]"]')->setAttribute('value', $data->operation['prices']['amount'] ?? 0);
	$t->qs('input[name="type[0]"][value="'.$data->operation['type'].'"]')->setAttribute('checked', 'checked' ?? 0);
	$t->qs('input[name="vatRate[0]"]')->setAttribute('value', $data->operation['prices']['vatRate'] ?? 0);
	$t->qs('input[name="vatValue[0]-calculation"]')->setAttribute('value', $data->operation['prices']['amountVat'] ?? 0);
	$t->qs('input[name="vatValue[0]"]')->setAttribute('value', $data->operation['prices']['amountVat'] ?? 0);
	$t->qs('input[name="amountIncludingVAT[0]-calculation"]')->setAttribute('value', $data->operation['prices']['amountIncludingVAT'] ?? 0);
	$t->qs('input[name="amountIncludingVAT[0]"]')->setAttribute('value', $data->operation['prices']['amountIncludingVAT'] ?? 0);

	$t->js()->eval('Operation.setIsWrittenAmount("vatValue", 0); Operation.checkVatConsistency(0);');

	$t->js()->eval('Operation.prefillThirdParty(0, '.($data->operation['eThirdParty']->notEmpty() ? $data->operation['eThirdParty']['id'] : 'null').', "'.$data->operation['thirdParty']['name'].'", "'.$data->operation['thirdParty']['vatNumber'].'");');

	// Saisie des frais de port s'ils existent en 2è opération
	if(count($data->operation['shipping']) > 0) {

		$index = 1;
		$defaultValues = $data->operation['shipping'] + ['type' => $data->operation['type']];
		$eOperation = new \journal\Operation($defaultValues);

		$t->qs('#operation-create-list')->setAttribute('data-columns', $index + 1);
		$t->qs('.operation-create[data-index="'.($index - 1).'"]')->insertAdjacentHtml(
			'afterend',
			new \journal\OperationUi()::getFieldsCreateGrid(
				$data->eFarm,
				$form,
				$eOperation,
				$data->eFinancialYear,
				'['.$index.']',
				$defaultValues,
				[], [],
				$data->cPaymentMethod
			)
		);
		$t->qs('#add-operation')->setAttribute('post-index', $index + 1);
		$t->js()->eval('Operation.showOrHideDeleteOperation()');
		$t->js()->eval('Operation.preFillNewOperation('.$index.')');

		// Sélectionner à nouveau le tiers
		$t->js()->eval('Operation.prefillThirdParty('.$index.', '.($data->operation['eThirdParty']->notEmpty() ? $data->operation['eThirdParty']['id'] : 'null').', "'.$data->operation['thirdParty']['name'].'", "'.$data->operation['thirdParty']['vatNumber'].'");');

		// Sélectionner un compte également
		$t->js()->eval('Operation.selectAccount('.$index.', '.$data->operation['shipping']['account']['id'].', '.$data->operation['shipping']['vatRate'].')');

	}

	if($data->ePartner->empty()) {
		$t->qs('[data-help="dropbox"]')->removeHide();
	}

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
	$eOperation = new \journal\Operation(['account' => $data->eAccount, 'accountLabel' => \account\ClassLib::pad($data->eAccount['class'])]);
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

new JsonView('getWaiting', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$form->open('journal-operation-create-payment');

	if($data->cOperation->count() > 0) {
		$t->qs('#waiting-operations-list')->innerHtml(new \journal\OperationUi()->listWaitingOperations($data->eFarm, $form, $data->cOperation));
		$t->qs('#waiting-operations-list-info')->innerHtml(new \journal\OperationUi()->$this->letteringInfo());
		$t->qs('#waiting-operations-list-container')->removeHide();
	}

});

new JsonView('addOperation', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$form->open('journal-operation-create');
	$defaultValues = [];

	$t->qs('#operation-create-list')->setAttribute('data-columns', $data->index + 1);
	$t->qs('.operation-create[data-index="'.($data->index - 1).'"]')->insertAdjacentHtml(
		'afterend',
		new \journal\OperationUi()::getFieldsCreateGrid(
			$data->eFarm,
			$form,
			$data->eOperation,
			$data->eFinancialYear,
			'['.$data->index.']',
			$defaultValues,
			[],
			['grant' => $data->cAssetGrant, 'asset' => $data->cAssetToLinkToGrant],
			$data->cPaymentMethod
		)
	);
	$t->qs('#add-operation')->setAttribute('post-index', $data->index + 1);
	if($data->index >= 4) {
		$t->qs('#add-operation')->addClass('not-visible');
	}
	$t->js()->eval('Operation.showOrHideDeleteOperation()');
	$t->js()->eval('Operation.preFillNewOperation('.$data->index.')');

	// On désactive l'import de facture
	$t->js()->eval('Operation.deactivateInvoiceImport()');

});

?>
