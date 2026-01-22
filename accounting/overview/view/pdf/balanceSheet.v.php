<?php
new HtmlView('index', function($data, AccountingPdfTemplate $t) {

	echo new \overview\PdfUi()->getBalanceSheet($data->eFarm, $data->type, $data->balanceSheetData, $data->totals, $data->cAccount);

});

?>
