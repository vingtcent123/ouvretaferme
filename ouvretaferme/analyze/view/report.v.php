<?php
new AdaptativeView('/rapport/{id}', function($data, FarmTemplate $t) {

	$t->title = \analyze\ReportUi::getName($data->e);
	$t->tab = 'analyze';
	$t->subNav = (new \farm\FarmUi())->getAnalyzeSubNav($data->eFarm);

	echo (new \analyze\ReportUi())->getOne($data->e, $data->cCultivation);
	echo (new \analyze\ReportUi())->getTest($data->e, $data->eTest);
	echo (new \analyze\ReportUi())->getProducts($data->e, $data->ccProduct);
	echo (new \analyze\ReportUi())->getHarvested($data->e, $data->cCultivation);
	echo (new \analyze\ReportUi())->getSiblings($data->e, $data->cReportSiblings);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \analyze\ReportUi())->create($data->e, $data->workingTimeNoSeries, $data->cProduct);
});

new AdaptativeView('products', function($data, AjaxTemplate $t) {
	$t->qs('#report-create-products')->innerHtml((new \analyze\ReportUi())->getProductsField($data->cProduct));
	$t->qs('#report-create-submit')->removeClass('hide');
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \analyze\ReportUi())->update($data->e);
});
?>
