<?php
new HtmlView('index', function($data, PdfTemplate $t) {
	echo new \journal\PdfUi()->getBook($data->eCompany, $data->cOperation, $data->eFinancialYear);
});

?>
