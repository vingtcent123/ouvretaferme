<?php
new HtmlView('amortization', function($data, AccountingPdfTemplate $t) {

	echo new \asset\PdfUi()->getAmortizations($data->eFarm, $data->assetAmortizations, $data->grantAmortizations);

});

new HtmlView('acquisition', function($data, AccountingPdfTemplate $t) {

	echo new \asset\PdfUi()->getAcquisitions($data->eFarm, $data->cAsset, $data->cAssetGrant);
});

?>
