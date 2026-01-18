<?php
new HtmlView('index', function($data, PdfTemplate $t) {

	echo new \account\PdfUi()->getPdfPage(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		\account\FinancialYearDocumentLib::SIG,
		new \overview\PdfUi()->getSig($data->eFinancialYear, $data->eFinancialYearComparison, $data->values)
	);

});

?>
