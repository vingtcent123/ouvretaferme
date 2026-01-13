<?php
new HtmlView('index', function($data, PdfTemplate $t) {
	echo new \overview\PdfUi()->getBalanceSheet($data->eFarm, $data->balanceSheetData, $data->totals, $data->cAccount, $data->type);
});

?>
