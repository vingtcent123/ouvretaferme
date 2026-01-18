<?php
namespace asset;

class PdfUi {

	public function __construct() {

		\Asset::css('company', 'pdf.css');

	}

	public function getAcquisitions(
		\Collection $cAsset,
		\Collection $cAssetGrant,
	): string {

		$h = '<div class="pdf-document-wrapper">';

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

		return $h;

	}

	public function getAmortizations(
		\farm\Farm $eFarm,
		array $assetAmortizations,
		array $grantAmortizations,
	): string {

		$showExcessColumns = (array_find($assetAmortizations, fn($amortization) => $amortization['excess']['currentFinancialYearRecovery'] > 0) !== NULL or
			array_find($grantAmortizations, fn($amortization) => $amortization['excess']['currentFinancialYearRecovery'] > 0) !== NULL);


		$h = '<div class="'.($showExcessColumns ? 'pdf-document-wrapper-landscape' : 'pdf-document-wrapper').'">';

			$h .= '<div class="pdf-document-content">';

				if(count($assetAmortizations) > 0) {

					$h .= '<table class="pdf-table-bordered" style="margin: 0 auto 1rem;">';

						$h .= '<thead>';
							$h .= new AmortizationUi()->getPdfTHead($assetAmortizations, 'asset');
						$h .= '</thead>';

						$h .= '<tbody>';
							$h .= new AmortizationUi()->getTBody($eFarm, $assetAmortizations);
						$h .= '</tbody>';

					$h .= '</table>';

				}

				if(count($grantAmortizations) > 0) {

					$h .= '<table class="pdf-table-bordered" style="margin: auto;">';

						$h .= '<thead>';
							$h .= new AmortizationUi()->getPdfTHead($grantAmortizations, 'grant');
						$h .= '</thead>';

						$h .= '<tbody>';
							$h .= new AmortizationUi()->getTBody($eFarm, $grantAmortizations);
						$h .= '</tbody>';

					$h .= '</table>';
				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;


	}

}
