<?php
new HtmlView('index', function($data, PdfTemplate $t) {
	echo new \journal\PdfUi()->getBook($data->eFarm, $data->cOperation, $data->eFinancialYear);
});

?>
