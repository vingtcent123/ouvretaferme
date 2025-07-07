<?php
new HtmlView('index', function($data, PdfTemplate $t) {
	echo new \journal\PdfUi()->getVatDeclaration($data->eFarm, $data->cOperation, $data->eFinancialYear, $data->eVatDeclaration);
});

?>
