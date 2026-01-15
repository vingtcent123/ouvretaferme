<?php
new HtmlView('amortization', function($data, PdfTemplate $t) {
	echo new \asset\PdfUi()->getAmortizations($data->eFarm, $data->assetAmortizations, $data->grantAmortizations);
});

new HtmlView('acquisition', function($data, PdfTemplate $t) {
	echo new \asset\PdfUi()->getAcquisitions($data->eFarm, $data->cAsset, $data->cAssetGrant);
});

?>
