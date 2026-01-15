<?php
namespace asset;

class PdfUi {

	public function __construct() {

		\Asset::css('company', 'pdf.css');

	}

	public function getAcquisitions(
		\farm\Farm $eFarm,
		\Collection $cAsset,
		\Collection $cAssetGrant,
	): string {

		$eFinancialYear = $eFarm['eFinancialYear'];

		$h = '<style>@page {	size: A4; margin: calc(var(--margin-bloc-height) + 2cm) 1cm 1cm; }</style>';

		if(get_exists('test') === TRUE) {
			$h .= \account\PdfUi::getHeader($eFarm, new \account\PdfUi()->getTitle(\account\FinancialYearDocumentLib::ASSET_ACQUISITION, $eFinancialYear->isClosed() === FALSE), $eFinancialYear);
		}

		$h .= '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				if($cAsset->notEmpty()) {

					$h .= '<table class="pdf-table-bordered" style="margin: 0 auto 1rem;">';

						$h .= '<thead>';
							$h .= new AssetUi()->getTHead('asset');
						$h .= '</thead>';

						$h .= '<tbody>';
							$h .= new AssetUi()->getPdfTBody($cAsset, 'asset');
						$h .= '</tbody>';

					$h .= '</table>';

				}

				if($cAssetGrant->notEmpty()) {

					$h .= '<table class="pdf-table-bordered" style="margin: auto;">';

						$h .= '<thead>';
							$h .= new AssetUi()->getTHead('grant');
						$h .= '</thead>';

						$h .= '<tbody>';
							$h .= new AssetUi()->getPdfTBody($cAssetGrant, 'grant');
						$h .= '</tbody>';

					$h .= '</table>';

					$h .= '</table>';
				}

			$h .= '</div>';

		$h .= '</div>';

		if(get_exists('test') === TRUE) {
			$h .= \account\PdfUi::getFooter();
		}
		return $h;

	}

	public function getAmortizations(
		\farm\Farm $eFarm,
		array $assetAmortizations,
		array $grantAmortizations,
	): string {

		$eFinancialYear = $eFarm['eFinancialYear'];

		$h = '<style>@page {	size: A4; margin: calc(var(--margin-bloc-height) + 2cm) 1cm 1cm; }</style>';

		if(get_exists('test') === TRUE) {
			$h .= \account\PdfUi::getHeader($eFarm, new \account\PdfUi()->getTitle(\account\FinancialYearDocumentLib::ASSET_AMORTIZATION, $eFinancialYear->isClosed() === FALSE), $eFinancialYear);
		}

		$h .= '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				if(count($assetAmortizations) > 0) {

					$h .= '<h1>'.s("Immobilisations").'</h1>';
					$h .= '<table class="pdf-table-bordered" style="margin: 0 auto 1rem;">';

						$h .= '<thead>';
							$h .= new AmortizationUi()->getPdfTHead($assetAmortizations);
						$h .= '</thead>';

						$h .= '<tbody>';
							$h .= new AmortizationUi()->getTBody($eFarm, $assetAmortizations);
						$h .= '</tbody>';

					$h .= '</table>';

				}

				if(count($grantAmortizations) > 0) {

					$h .= '<h1>'.s("Subventions").'</h1>';
					$h .= '<table class="pdf-table-bordered" style="margin: auto;">';

						$h .= '<thead>';
							$h .= new AmortizationUi()->getPdfTHead($grantAmortizations);
						$h .= '</thead>';

						$h .= '<tbody>';
							$h .= new AmortizationUi()->getTBody($eFarm, $grantAmortizations);
						$h .= '</tbody>';

					$h .= '</table>';
				}

			$h .= '</div>';

		$h .= '</div>';

		if(get_exists('test') === TRUE) {
			$h .= \account\PdfUi::getFooter();
		}
		return $h;


	}

}
