<?php
new AdaptativeView('/rapport/{id}', function($data, FarmTemplate $t) {

	$t->title = \analyze\ReportUi::getName($data->e);
	$t->tab = 'analyze';
	$t->subNav = new \farm\FarmUi()->getAnalyzeSubNav($data->eFarm);

	$t->mainTitle = new \analyze\ReportUi()->getOneTitle($data->e);

	echo new \analyze\ReportUi()->getOne($data->e, $data->cCultivation);
	echo new \analyze\ReportUi()->getProducts($data->e, $data->ccProduct);
	echo new \analyze\ReportUi()->getHarvested($data->e, $data->cCultivation);
	echo new \analyze\ReportUi()->getTest($data->e, $data->eTest);
	echo new \analyze\ReportUi()->getSiblings($data->e, $data->cReportSiblings);

});

new AdaptativeView('create', function($data, FarmTemplate $t) {

	$t->tab = 'analyze';
	$t->subNav = new \farm\FarmUi()->getAnalyzeSubNav($data->eFarm);

	if($data->e['from']->notEmpty()) {
		$url = \analyze\ReportUi::url($data->e['from']);
		$title = s("Actualiser un rapport");
	} else {
		$url = \farm\FarmUi::urlAnalyzeReport($data->eFarm);
		$title = s("Créer un rapport");
	}

	$h = '<h1>';
		$h .= '<a href="'.$url.'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= $title;
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \analyze\ReportUi()->create($data->e, $data->workingTimeNoSeries, $data->cProduct, $data->switchComposition);
});

new AdaptativeView('products', function($data, AjaxTemplate $t) {
	$t->qs('#report-create-products')->innerHtml(new \analyze\ReportUi()->getProductsField($data->ePlant['farm'], $data->cProduct, $data->switchComposition));
	$t->qs('#report-create-submit')->removeClass('hide');
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \analyze\ReportUi()->update($data->e);
});
?>
