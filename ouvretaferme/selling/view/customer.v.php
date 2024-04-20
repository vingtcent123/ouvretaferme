<?php
new AdaptativeView('/client/{id}', function($data, FarmTemplate $t) {

	$t->title = s("Client {value}", encode($data->e['name']));

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	echo (new \selling\CustomerUi())->getOne($data->e, $data->cSaleTurnover, $data->cGrid, $data->cSale, $data->cInvoice);

});

new AdaptativeView('analyze', function($data, PanelTemplate $t) {
	return (new \selling\AnalyzeUi())->getCustomer($data->e, $data->year, $data->cSaleTurnover, $data->cItemProduct, $data->cItemMonth, $data->cItemMonthBefore, $data->cItemWeek, $data->cItemWeekBefore);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \selling\CustomerUi())->create($data->eFarm);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \selling\CustomerUi())->update($data->e);
});

new AdaptativeView('updateGrid', function($data, PanelTemplate $t) {
	return (new \selling\GridUi())->updateByCustomer($data->e, $data->cProduct);
});

new JsonView('doUpdateGrid', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#customer-switch-'.$data->e['id'])->toggleSwitch();
});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cCustomer->makeArray(fn($eCustomer) => \selling\CustomerUi::getAutocomplete($eCustomer));

	$t->push('results', $results);

});

new AdaptativeView('updateOptIn', function($data, PanelTemplate $t) {
	return (new \selling\CustomerUi())->updateOptIn($data->cCustomer);
});

new AdaptativeView('/ferme/{id}/optIn', function($data, MainTemplate $t) {

	$form = new \util\FormUi();

	$t->title = s("Gérer les communications par e-mail d'un producteur");

	$h = $form->open(NULL, ['action' => '/selling/customer:doUpdateOptInByEmail']);
		$h .= $form->hidden('id', $data->eFarm['id']);
		$h .= $form->group(
			s("Producteur"),
			\farm\FarmUi::getVignette($data->eFarm, '3rem').'&nbsp;&nbsp;'.encode($data->eFarm['name'])
		);
		$h .= $form->group(
			s("Votre adresse e-mail"),
			$form->text('email')
		);
		$h .= $form->group(
			s("Recevoir les communications"),
			$form->yesNo('consent', $data->consent, [
				'yes' => s("Oui, les recevoir"),
				'no' => s("Ne rien recevoir")
			])
		);
		$h .= $form->group(
			content: $form->submit(s("Enregistrer"))
		);
	$h .= $form->close();

	echo $h;

});

new AdaptativeView('optInSaved', function($data, MainTemplate $t) {

	$h = '<div class="text-center">';
		$h .= '<br/><br/>';
		if($data->consent) {
			$h .= '<h2>'.s("Votre abonnement a bien été enregistré !").'</h2>';
			$h .= '<h4>'.s("Merci, vous recevrez désormais les communications par e-mail du producteur.").'</h4>';
		} else {
			$h .= '<h2>'.s("Votre désabonnement a bien été enregistré !").'</h2>';
			$h .= '<h4>'.s("Vous ne recevrez plus de communication par e-mail de la part du producteur.").'</h4>';
		}
		$h .= '<a href="/" class="btn btn-secondary">'.s("Revenir sur mon compte").'</a>';
		$h .= '<br/><br/>';
	$h .= '</div>';

	$t->header = $h;

});
?>
