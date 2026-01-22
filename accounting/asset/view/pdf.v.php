<?php
new HtmlView('amortization', function($data, AccountingPdfTemplate $t) {

	echo new \account\PdfUi()->getPdfPage(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		\account\FinancialYearDocumentLib::ASSET_AMORTIZATION,
		new \asset\PdfUi()->getAmortizations($data->eFarm, $data->assetAmortizations, $data->grantAmortizations)
	);

});

new HtmlView('acquisition', function($data, AccountingPdfTemplate $t) {

	echo new \account\PdfUi()->getPdfPage(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		\account\FinancialYearDocumentLib::ASSET_ACQUISITION,
		new \asset\PdfUi()->getAcquisitions($data->eFarm, $data->cAsset, $data->cAssetGrant)
	);
});

?>
