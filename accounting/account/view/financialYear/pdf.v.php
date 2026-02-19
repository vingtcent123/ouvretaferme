<?php
new AdaptativeView('generate', function($data, AjaxTemplate $t) {

	$t->ajaxReload();
	$t->js()->success('account', 'FinancialYear::pdf.generationStacked', [
		'type' => $data->type,
	]);

});

new HtmlView('attestation', function($data, AccountingPdfTemplate $t) {

	echo new \account\FecUi()->getAttestation($data->eFarm);

});
