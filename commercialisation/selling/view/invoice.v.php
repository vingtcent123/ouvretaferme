<?php
new AdaptativeView('createCustomer', function($data, PanelTemplate $t) {
	return new \selling\InvoiceUi()->createCustomer($data->eFarm);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \selling\InvoiceUi()->create($data->e, $data->cSale, $data->cSaleMore, $data->search);

});

new AdaptativeView('regenerate', function($data, PanelTemplate $t) {
	return new \selling\InvoiceUi()->regenerate($data->e);
});

new AdaptativeView('doCreate', function($data, AjaxTemplate $t) {

	$t->ajaxReload();
	$t->js()->success('selling', 'Invoice::created', [
		'actions' => new \selling\InvoiceUi()->getSuccessActions($data->e)
	]);

});

new AdaptativeView('doRegenerate', function($data, AjaxTemplate $t) {

	$t->ajaxReload();
	$t->js()->success('selling', 'Invoice::regenerated', [
		'actions' => new \selling\InvoiceUi()->getSuccessActions($data->e)
	]);

});

new AdaptativeView('updatePayment', function($data, PanelTemplate $t) {
	return new \selling\InvoiceUi()->updatePayment($data->e);
});

new AdaptativeView('updateComment', function($data, PanelTemplate $t) {
	return new \selling\InvoiceUi()->updateComment($data->e);
});

new AdaptativeView('createCollection', function($data, PanelTemplate $t) {

	if($data->month === NULL) {
		return new \selling\InvoiceUi()->selectMonthForCreateCollection($data->eFarm, $data->cCustomerGroup);
	} else {
		return new \selling\InvoiceUi()->createCollection($data->eFarm, $data->month, $data->type, $data->e, $data->cSale, $data->cCustomerGroup);
	}

});

?>
