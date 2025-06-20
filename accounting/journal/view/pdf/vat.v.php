<?php
new HtmlView('index', function($data, PdfTemplate $t) {
	echo new \journal\PdfUi()->getVat($data->eFarm, $data->cccOperation, $data->eFinancialYear);
});

?>
