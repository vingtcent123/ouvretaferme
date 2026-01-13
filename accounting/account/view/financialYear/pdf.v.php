<?php

new AdaptativeView('generate', function($data, AjaxTemplate $t) {

	$t->ajaxReload();
	$t->js()->success('account', 'FinancialYear::pdf.generated', [
		'actions' => new \account\FinancialYearUi()->getSuccessActions($data->eFarm, $data->eFinancialYear, $data->type),
		'type' => $data->type,
	]);

});
