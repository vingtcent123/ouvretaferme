<?php
new AdaptativeView('/vente/{id}', function($data, FarmTemplate $t) {

	$t->title = s("Vente #{value}", $data->e->getNumber());

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	echo (new \selling\SaleUi())->getHeader($data->e);
	echo (new \selling\SaleUi())->getRelativeSales($data->e, $data->relativeSales);
	echo (new \selling\SaleUi())->getContent($data->e, $data->cPdf);

	echo (new \selling\ItemUi())->getBySale($data->e, $data->cItem);
	echo (new \selling\SaleUi())->getMarket($data->eFarm, $data->ccSaleMarket);
	echo (new \selling\SaleUi())->getHistory($data->cHistory);

});

new AdaptativeView('generateOrderForm', function($data, PanelTemplate $t) {
	return (new \selling\PdfUi())->createOrderForm($data->e, $data->ePdf);
});

new AdaptativeView('doGenerateDocument', function($data, AjaxTemplate $t) {

	$actions = '<div class="mt-1">';
		$actions .= '<a href="'.\selling\PdfUi::url($data->ePdf).'" data-ajax-navigation="never" class="btn btn-transparent">'.s("Télécharger").'</a>';
		if($data->ePdf->canSend()) {
			$actions .= ' <a data-ajax="/selling/sale:doSendDocument" post-id="'.$data->e['id'].'" post-type="'.$data->ePdf['type'].'" class="btn btn-transparent" data-confirm="'.s("Confirmer l'envoi du document au client par e-mail ?").'">'.\Asset::icon('send').' '.s("Envoyer au client par e-mail").'</a>';
		}
	$actions .= '</div>';

	$t->ajaxReload();
	$t->js()->success('selling', 'Sale::pdfCreated', [
		'type' => $data->ePdf['type'],
		'actions' => $actions
	]);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \selling\SaleUi())->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \selling\SaleUi())->update($data->e);
});

new AdaptativeView('duplicate', function($data, PanelTemplate $t) {
	return (new \selling\SaleUi())->duplicate($data->e);
});

new AdaptativeView('updateShop', function($data, PanelTemplate $t) {
	return (new \selling\SaleUi())->updateShop($data->e);
});

new AdaptativeView('updateCustomer', function($data, PanelTemplate $t) {
	return (new \selling\SaleUi())->updateCustomer($data->e);
});

new HtmlView('getExport', function($data, PdfTemplate $t) {

	echo (new \selling\PdfUi())->getSales($data->c, $data->cItem);

});
?>
