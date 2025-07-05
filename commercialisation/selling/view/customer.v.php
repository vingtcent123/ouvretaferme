<?php
new AdaptativeView('/client/{id}', function($data, FarmTemplate $t) {

	$t->title = s("Client {value}", encode($data->e['name']));

	$t->nav = 'selling';
	$t->subNav = 'customer';

	$t->mainTitle = new \selling\CustomerUi()->displayTitle($data->e);

	echo new \selling\CustomerUi()->display($data->e);
	echo new \selling\CustomerUi()->getTabs($data->e, $data->cSaleTurnover, $data->cGrid, $data->cSale, $data->cEmail, $data->cInvoice, $data->cPaymentMethod);

});

new AdaptativeView('analyze', function($data, PanelTemplate $t) {
	return new \selling\AnalyzeUi()->getCustomer($data->e, $data->year, $data->cSaleTurnover, $data->cItemProduct, $data->cItemMonth, $data->cItemMonthBefore, $data->cItemWeek, $data->cItemWeekBefore);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \selling\CustomerUi()->create($data->eFarm);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \selling\CustomerUi()->update($data->e);
});

new AdaptativeView('updateGrid', function($data, PanelTemplate $t) {
	return new \selling\GridUi()->updateByCustomer($data->e, $data->cProduct);
});

new JsonView('doUpdateGrid', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#customer-switch-'.$data->e['id'])->toggleSwitch('post-status', [\selling\Customer::ACTIVE, \selling\Customer::INACTIVE]);
});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cCustomer->makeArray(fn($eCustomer) => \selling\CustomerUi::getAutocomplete($eCustomer));

	if($data->hasNew) {
		$results[] = \selling\CustomerUi::getAutocompleteCreate($data->eFarm);
	}

	$t->push('results', $results);

});
?>
