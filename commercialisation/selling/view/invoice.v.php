<?php
new AdaptativeView('createCustomer', function($data, PanelTemplate $t) {
	return new \selling\InvoiceUi()->createCustomer($data->eFarm);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \selling\InvoiceUi()->create($data->e, $data->eSaleFirst, $data->cSaleMore, $data->search);

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


new AdaptativeView('/facture/{id}', function($data, FarmTemplate $t) {

	$t->title = \selling\InvoiceUi::getName($data->e);

	$t->nav = 'selling';
	$t->subNav = 'invoice';

	$t->mainTitle = new \selling\InvoiceUi()->getHeader($data->e);

	echo new \selling\InvoiceUi()->getContent($data->e);
	echo new \selling\PaymentLinkUi()->getList($data->e);

	if($data->e['cSale']->notEmpty()) {

		echo '<div class="mb-2">';

			echo '<h3>';
				echo s("Ventes");
				echo '  <span class="util-badge bg-primary" id="item-count">'.$data->e['cSale']->count().'</span>';
			echo '</h3>';
			echo new \selling\SaleUi()->getListSales($data->e['farm'], $data->e['cSale'], hide: ['customer', 'batch', 'paymentMethod', 'documents', 'preparationStatus']);
		echo '</div>';

	}

	echo new \selling\HistoryUi()->getList($data->e, $data->cHistory);

});
?>
