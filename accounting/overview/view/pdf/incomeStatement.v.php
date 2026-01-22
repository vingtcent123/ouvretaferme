<?php
new HtmlView('index', function($data, PdfTemplate $t) {

	echo new \overview\PdfUi()->getIncomeStatement($data->eFarm, $data->type, $data->eFinancialYear, $data->eFinancialYearComparison, $data->resultData, $data->cAccount);

});

?>
<?php
