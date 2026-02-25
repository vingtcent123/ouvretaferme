<?php
new AdaptativeView('salePanel', function($data, PanelTemplate $t) {

		$h = '';

		$h .= new \selling\SaleUi()->getContent($data->e, $data->ccPdf);
		$h .= new \selling\ItemUi()->getBySale($data->e, $data->cItem);
		$h .= new \selling\SaleUi()->getMarket($data->e, $data->eFarm, $data->ccSaleMarket, $data->cPaymentMethod);
		$h .= new \selling\SaleUi()->getHistory($data->e, $data->cHistory);

		return new \Panel(
			id: 'panel-sale',
			title: \selling\SaleUi::getName($data->e),
			body: $h,
			close: 'reload'
		);

});

new AdaptativeView('salePlain', function($data, FarmTemplate $t) {

	$t->title = \selling\SaleUi::getName($data->e);

	$t->nav = 'selling';
	$t->subNav = 'sale';

	$t->mainTitle = new \selling\SaleUi()->getHeader($data->e);

	echo new \selling\SaleUi()->getContent($data->e, $data->ccPdf);
	echo new \selling\ItemUi()->getBySale($data->e, $data->cItem);
	echo new \selling\SaleUi()->getMarket($data->e, $data->eFarm, $data->ccSaleMarket, $data->cPaymentMethod);
	echo new \selling\SaleUi()->getHistory($data->e, $data->cHistory);

});

new AdaptativeView('salePreparing', function($data, FarmTemplate $t) {

	$t->title = \selling\SaleUi::getName($data->e);

	$t->nav = 'selling';
	$t->subNav = 'sale';

	$t->template .= ' farm-preparing';

	$t->mainTitle = new \selling\PreparationUi()->getHeader($data->e, $data->preparing);

	echo new \selling\SaleUi()->getHeader($data->e);
	echo new \selling\SaleUi()->getPresentation($data->e, $data->ccPdf);
	echo new \selling\PreparationUi()->getSummary($data->e, $data->cItem, $data->preparing);
	echo new \selling\ItemUi()->getBySale($data->e, $data->cItem, isPreparing: $data->e['preparationStatus'] === \selling\Sale::CONFIRMED);
	echo new \selling\SaleUi()->getMarket($data->e, $data->eFarm, $data->ccSaleMarket, $data->cPaymentMethod);
	echo new \selling\SaleUi()->getHistory($data->e, $data->cHistory);

});

new AdaptativeView('generateOrderForm', function($data, PanelTemplate $t) {
	return new \selling\PdfUi()->createOrderForm($data->e);
});

new AdaptativeView('generateDeliveryNote', function($data, PanelTemplate $t) {
	return new \selling\PdfUi()->createDeliveryNote($data->e);
});

new AdaptativeView('doGenerateDocument', function($data, AjaxTemplate $t) {

	$actions = '<div class="mt-1">';
		$actions .= '<a href="'.\selling\PdfUi::url($data->ePdf).'" data-ajax-navigation="never" class="btn btn-transparent">'.s("Télécharger").'</a>';
		if($data->ePdf->acceptSend()) {
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
	return new \selling\SaleUi()->create($data->e);
});

new AdaptativeView('createCollection', function($data, PanelTemplate $t) {
	return new \selling\SaleUi()->createCollection($data->e, $data->cCustomerGroup);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \selling\SaleUi()->update($data->e);
});

new AdaptativeView('updatePayment', function($data, PanelTemplate $t) {
	return new \selling\SaleUi()->updatePayment($data->e);
});

new AdaptativeView('duplicate', function($data, PanelTemplate $t) {
	return new \selling\SaleUi()->duplicate($data->e, $data->acceptCredit);
});

new AdaptativeView('updateShop', function($data, PanelTemplate $t) {
	return new \selling\SaleUi()->updateShop($data->e);
});

new AdaptativeView('updateCustomer', function($data, PanelTemplate $t) {
	return new \selling\SaleUi()->updateCustomer($data->e);
});

new HtmlView('getExport', function($data, PdfTemplate $t) {

	echo new \selling\PdfUi()->getSales($data->eFarm, $data->c, $data->cItem, $data->template);

});
?>
