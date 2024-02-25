<?php
new HtmlView('getLabels', function($data, PdfTemplate $t) {
	echo (new \selling\PdfUi())->getLabels($data->eFarm, $data->cSale);
});

new HtmlView('getDocument', function($data, PdfTemplate $t) {
	echo (new \selling\PdfUi())->getDocument($data->e, $data->type, $data->eFarm, $data->cItem);
});

new HtmlView('getDocumentInvoice', function($data, PdfTemplate $t) {
	echo (new \selling\PdfUi())->getDocumentInvoice($data->e, $data->eFarm, $data->cSale);
});
?>
