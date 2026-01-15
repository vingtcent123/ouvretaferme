<?php
new HtmlView('balance', function($data, PdfTemplate $t) {
	echo new \journal\PdfUi()->balance($data->eFarm, $data->eFinancialYearPrevious, $data->trialBalanceData, $data->trialBalancePreviousData, $data->search);
});

?>
