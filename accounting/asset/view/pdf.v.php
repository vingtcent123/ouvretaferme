<?php
new HtmlView('amortization', function($data, AccountingPdfTemplate $t) {

	$t->landscape = TRUE;
	echo new \asset\PdfUi()->getAmortizations($data->eFarm, $data->assetAmortizations, $data->grantAmortizations);

});

new HtmlView('acquisition', function($data, AccountingPdfTemplate $t) {

	$t->landscape = TRUE;
	echo new \asset\PdfUi()->getAcquisitions($data->eFarm, $data->cAsset, $data->cAssetGrant);
});

?>
