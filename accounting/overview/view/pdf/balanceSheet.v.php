<?php
new HtmlView('index', function($data, PdfTemplate $t) {

	echo new \account\PdfUi()->getPdfPage(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		$data->type,
		new \overview\PdfUi()->getBalanceSheet($data->eFarm, $data->balanceSheetData, $data->totals, $data->cAccount, $data->isDetailed)
	);

});

?>
