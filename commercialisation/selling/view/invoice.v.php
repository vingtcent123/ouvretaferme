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

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \selling\InvoiceUi()->update($data->e);
});

new JsonView('doUpdatePaymentStatus', function($data, AjaxTemplate $t) {

	$t->qs('#invoice-switch-'.$data->e['id'])->toggleSwitch('post-payment-status', [\selling\Invoice::PAID, \selling\Invoice::NOT_PAID]);

	switch($data->e['paymentStatus']) {

		case \selling\Invoice::PAID :
			$t->qs('#invoice-list-'.$data->e['id'])->addClass('invoice-item-paid');
			$t->qs('#invoice-list-'.$data->e['id'])->removeClass('invoice-item-not-paid');
			break;

		case \selling\Invoice::NOT_PAID :
			$t->qs('#invoice-list-'.$data->e['id'])->removeClass('invoice-item-paid');
			$t->qs('#invoice-list-'.$data->e['id'])->addClass('invoice-item-not-paid');
			break;

	}

});

new AdaptativeView('createCollection', function($data, PanelTemplate $t) {

	if($data->month === NULL) {
		return new \selling\InvoiceUi()->selectMonthForCreateCollection($data->eFarm, $data->cCustomerGroup);
	} else {
		return new \selling\InvoiceUi()->createCollection($data->eFarm, $data->month, $data->type, $data->e, $data->cSale, $data->cCustomerGroup);
	}

});

?>
