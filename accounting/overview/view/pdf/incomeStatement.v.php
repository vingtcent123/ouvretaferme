<?php
new HtmlView('index', function($data, PdfTemplate $t) {

	echo new \account\PdfUi()->getPdfPage(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		$data->type,
		new \overview\PdfUi()->getIncomeStatement($data->eFarm, $data->eFinancialYear, $data->eFinancialYearComparison, $data->resultData, $data->cAccount),
	);

});

?>
<?php
