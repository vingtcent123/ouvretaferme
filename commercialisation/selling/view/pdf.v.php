<?php
new HtmlView('getLabels', function($data, PdfTemplate $t) {
	echo new \selling\PdfUi()->getLabels($data->eFarm, $data->cSale);
});

new HtmlView('getDocument', function($data, PdfTemplate $t) {
	$t->title = new \selling\PdfUi()->getFilename($data->type, $data->eFarm, $data->e);
	echo new \selling\PdfUi()->getDocument($data->e, $data->type, $data->eFarm, $data->cItem);
});

new HtmlView('getDocumentInvoice', function($data, PdfTemplate $t) {

	$t->title = new \selling\PdfUi()->getFilename(\selling\Pdf::INVOICE, $data->eFarm, $data->e);

	if(count($data->e['sales']) > 1) {

		echo new \selling\PdfUi()->getDocumentInvoice($data->e, $data->eFarm, $data->cSale);

	} else {

		$eSale = $data->cSale->first();
		$eSale['invoice'] = $data->e;

		$cItem = $eSale['cItem'];

		echo new \selling\PdfUi()->getDocument($eSale, \selling\Pdf::INVOICE, $data->eFarm, $cItem);

	}

});
?>
