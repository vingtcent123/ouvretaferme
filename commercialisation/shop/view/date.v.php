<?php
new AdaptativeView('create', function($data, FarmTemplate $t) {

	$t->nav = 'shop';
	$t->subNav = 'shop';

	$t->title = match($data->e['shop']['opening']) {
		\shop\Shop::ALWAYS => s("Ouvrir les ventes"),
		\shop\Shop::FREQUENCY => s("Ajouter une nouvelle livraison"),
	};

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
	$t->qs('#date-switch-'.$data->e['id'])->toggleSwitch('post-status', [\shop\Date::ACTIVE, \shop\Date::INACTIVE]);
});

new JsonView('doUpdatePoint', function($data, AjaxTemplate $t) {
	$t->qs('#point-switch-'.$data->ePoint['id'])->toggleSwitch('post-status', [TRUE, FALSE]);
});

new JsonView('doUpdateCatalog', function($data, AjaxTemplate $t) {
	$t->qs('#catalog-switch-'.$data->eCatalog['id'])->toggleSwitch('post-status', [TRUE, FALSE]);
});

new AdaptativeView('/ferme/{id}/date/{date}', function($data, FarmTemplate $t) {

	$t->nav = 'shop';
	$t->subNav = 'shop';

	$t->title = \shop\DateUi::name($data->eDate);

	\Asset::js('shop', 'manage.js');

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\shop\ShopUi::adminUrl($data->e, $data->eShop).'" class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= $t->title;
		$h .= '</h1>';
		$h .= '<div>';
			if($data->eDate->canWrite() or $data->eShop->canWrite()) {
				$h .= new \shop\DateUi()->getMenu($data->eFarm, $data->eShop, $data->eDate, $data->eDate['sales']['count'], 'btn-primary');
			}
		$h .= '</div>';
	$h .= '</div>';
	
	$t->mainTitle = $h;

	echo new \shop\DateUi()->getDetails($data->eShop, $data->eDate);

	echo new \shop\DateUi()->getContent($data->e, $data->eShop, $data->eDate, $data->cPaymentMethod);

});

new HtmlView('getSales', function($data, PdfTemplate $t) {

	echo new \selling\PdfUi()->getSalesByDate($data->e, $data->cSale, $data->cItem);

});

?>
