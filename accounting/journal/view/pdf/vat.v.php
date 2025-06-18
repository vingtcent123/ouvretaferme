<?php
new HtmlView('index', function($data, PdfTemplate $t) {
	echo new \journal\PdfUi()->getVat($data->eCompany, $data->cccOperation, $data->eFinancialYear);
});

?>
