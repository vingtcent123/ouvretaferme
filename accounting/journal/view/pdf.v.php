<?php
new HtmlView('balance', function($data, PdfTemplate $t) {

	echo new \account\PdfUi()->getPdfPage(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		$data->type,
		new \journal\PdfUi()->balance($data->eFarm, $data->eFinancialYearPrevious, $data->trialBalanceData, $data->trialBalancePreviousData, $data->type)
	);

});

?>
