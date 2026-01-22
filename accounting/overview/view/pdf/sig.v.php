<?php
new HtmlView('index', function($data, AccountingPdfTemplate $t) {

	echo new \overview\PdfUi()->getSig($data->eFarm, $data->eFinancialYear, $data->eFinancialYearComparison, $data->values);

});

?>
