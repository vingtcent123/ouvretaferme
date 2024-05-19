<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \shop\DateUi())->create($data->e, $data->cProduct, $data->eDateBase);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \shop\DateUi())->update($data->e);

});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#date-switch-'.$data->e['id'])->toggleSwitch();
});

new JsonView('doUpdatePoint', function($data, AjaxTemplate $t) {
	$t->qs('#point-switch-'.$data->ePoint['id'])->toggleSwitch();
});

new AdaptativeView('/ferme/{farm}/boutique/{shop}/date/{id}', function($data, FarmTemplate $t) {

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);
	$t->title = \shop\DateUi::name($data->e);

	\Asset::js('shop', 'manage.js');

	echo '<a href="'.\shop\ShopUi::adminUrl($data->eFarm, $data->e['shop']).'" class="btn btn-outline-primary btn-sm mb-1">'.Asset::icon('arrow-left').' '.s("Revenir sur la boutique").'</a>';
	echo '<div class="util-action">';
		echo '<h1>';
			echo $t->title;
		echo '</h1>';
		echo '<div>';
			if($data->e->canWrite()) {
				echo (new \shop\DateUi())->getMenu($data->e['shop'], $data->e, $data->e['sales']['count'], 'btn-primary');
			}
		echo '</div>';
	echo '</div>';
	echo '<div class="util-action-subtitle">';
			$url = \shop\ShopUi::dateUrl($data->eShop, $data->e, showDomain: TRUE);
			echo '<a href="'.$url.'">'.$url.'</a>';
	echo '</div>';

	echo (new \shop\DateUi())->getDetails($data->eShop, $data->e);

	echo (new \shop\DateUi())->getContent($data->eFarm, $data->eShop, $data->e, $data->cSale);

});

new HtmlView('getSales', function($data, PdfTemplate $t) {

	echo (new \selling\PdfUi())->getSalesByDate($data->e, $data->cSale, $data->cItem);

});

?>