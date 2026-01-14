<?php
new HtmlView('index', function($data, PdfTemplate $t) {

	echo new \overview\PdfUi()->getSig($data->eFarm, $data->eFinancialYear, $data->eFinancialYearComparison, $data->values);

});

?>
