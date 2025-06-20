<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

		return new \journal\OperationUi()->create($data->eFarm, $data->e, $data->eFinancialYear);

});
new AdaptativeView('createPayment', function($data, PanelTemplate $t) {

		return new \journal\OperationUi()->createPayment($data->eFarm, $data->e, $data->cBankAccount);

});

new JsonView('getWaiting', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$form->open('journal-operation-create-payment');

	$t->qs('#waiting-operations-list')->innerHtml(new \journal\OperationUi()->listWaitingOperations($data->eFarm, $form, $data->cOperation));
	$t->qs('#waiting-operations-list-container')->removeHide();

});

new JsonView('addOperation', function($data, AjaxTemplate $t) {

	$form = new \util\FormUi();
	$form->open('journal-operation-create');
	$defaultValues = [];

	$t->qs('#create-operation-list')->setAttribute('data-columns', $data->index + 1);
	$t->qs('.create-operation[data-index="'.($data->index - 1).'"]')->insertAdjacentHtml(
		'afterend',
		new \journal\OperationUi()::getFieldsCreateGrid($data->eFarm, $form, $data->eOperation, $data->eFinancialYear, '['.$data->index.']', $defaultValues, [])
	);
	$t->qs('#add-operation')->setAttribute('post-index', $data->index + 1);
	if($data->index >= 4) {
		$t->qs('#add-operation')->addClass('not-visible');
	}
	$t->js()->eval('Operation.showOrHideDeleteOperation()');
	$t->js()->eval('Operation.preFillNewOperation('.$data->index.')');

});

?>
