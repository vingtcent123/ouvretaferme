<?php
new AdaptativeView('create', function($data, FarmTemplate $t) {

	$t->tab = 'selling';
	$t->subNav = new \farm\FarmUi()->getShopSubNav($data->eFarm);
	$t->title = s("Créer une nouvelle vente");

	\Asset::js('shop', 'manage.js');

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\shop\ShopUi::adminUrl($data->eFarm, $data->e['shop']).'" class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= $t->title;
		$h .= '</h1>';
	$h .= '</div>';
	
	$t->mainTitle = $h;

	echo new \shop\DateUi()->create($data->e, $data->cProduct, $data->eDateBase);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \shop\DateUi()->update($data->e);

});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#date-switch-'.$data->e['id'])->toggleSwitch('post-status', [\shop\Date::ACTIVE, \shop\Date::CLOSED]);
});

new JsonView('doUpdatePoint', function($data, AjaxTemplate $t) {
	$t->qs('#point-switch-'.$data->ePoint['id'])->toggleSwitch('post-status', [TRUE, FALSE]);
});

new AdaptativeView('/boutique/{shop}/date/{id}', function($data, FarmTemplate $t) {

	$t->tab = 'shop';
	$t->subNav = new \farm\FarmUi()->getShopSubNav($data->eFarm);
	$t->title = \shop\DateUi::name($data->e);

	\Asset::js('shop', 'manage.js');

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\shop\ShopUi::adminUrl($data->eFarm, $data->e['shop']).'" class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= $t->title;
		$h .= '</h1>';
		$h .= '<div>';
			if($data->e->canWrite()) {
				$h .= new \shop\DateUi()->getMenu($data->e['shop'], $data->e, $data->e['sales']['count'], 'btn-primary');
			}
		$h .= '</div>';
	$h .= '</div>';
	
	$t->mainTitle = $h;

	echo new \shop\DateUi()->getDetails($data->eShop, $data->e);

	echo new \shop\DateUi()->getContent($data->eFarm, $data->eShop, $data->e, $data->cSale);

});

new HtmlView('getSales', function($data, PdfTemplate $t) {

	echo new \selling\PdfUi()->getSalesByDate($data->e, $data->cSale, $data->cItem);

});

?>