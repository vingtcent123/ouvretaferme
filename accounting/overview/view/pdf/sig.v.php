<?php
new HtmlView('index', function($data, AccountingPdfTemplate $t) {

	echo new \account\PdfUi()->getPdfPage(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		\account\FinancialYearDocumentLib::SIG,
		new \overview\PdfUi()->getSig($data->eFarm, $data->eFinancialYear, $data->eFinancialYearComparison, $data->values)
	);

});

?>
