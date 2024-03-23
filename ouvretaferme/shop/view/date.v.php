<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \shop\DateUi())->create($data->e, $data->cProduct, $data->eDateBase);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \shop\DateUi())->update($data->e);

});

new AdaptativeView('/ferme/{farm}/boutique/{shop}/date/{id}', function($data, FarmTemplate $t) {

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);
	$t->title = \shop\DateUi::name($data->e);

	\Asset::js('shop', 'manage.js');

	echo '<div class="util-action">';
		echo '<h1>';
			echo $t->title;
		echo '</h1>';
		echo '<div>';
			if($data->e->canWrite()) {
				echo (new \shop\DateUi())->getMenu($data->e['shop'], $data->e, $data->e['sales']['count'], $data->e['sales']['countValid'], 'btn-primary');
			}
		echo '</div>';
	echo '</div>';
	echo '<div class="util-action-subtitle">';
		echo '<a href="'.\shop\ShopUi::url($data->eShop).'">'.\shop\ShopUi::url($data->eShop, showProtocol: FALSE).'</a>';;
	echo '</div>';

	echo (new \shop\DateUi())->getDetails($data->eShop, $data->e);

	echo (new \shop\DateUi())->getContent($data->eFarm, $data->eShop, $data->e, $data->cSale);

});

new HtmlView('getSales', function($data, PdfTemplate $t) {

	echo (new \shop\PdfUi())->getSales($data->e, $data->cSale, $data->cItem);

});

?>