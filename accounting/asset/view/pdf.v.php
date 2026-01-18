<?php
new HtmlView('amortization', function($data, PdfTemplate $t) {

	echo new \account\PdfUi()->getPdfPage(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		\account\FinancialYearDocumentLib::ASSET_AMORTIZATION,
		new \asset\PdfUi()->getAmortizations($data->eFarm, $data->assetAmortizations, $data->grantAmortizations)
	);

});

new HtmlView('acquisition', function($data, PdfTemplate $t) {

	echo new \account\PdfUi()->getPdfPage(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		\account\FinancialYearDocumentLib::ASSET_ACQUISITION,
		new \asset\PdfUi()->getAcquisitions($data->cAsset, $data->cAssetGrant)
	);
});

?>
