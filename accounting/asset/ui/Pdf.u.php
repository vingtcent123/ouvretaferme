<?php
namespace asset;

class PdfUi {

	public function getAcquisitions(
		\farm\Farm $eFarm,
		\Collection $cAsset,
		\Collection $cAssetGrant,
	): string {

		$eFinancialYear = $eFarm['eFinancialYear'];
		$header = new \account\PdfUi()->getHeader($eFarm, new \account\PdfUi()->getTitle(\account\FinancialYearDocumentLib::ASSET_ACQUISITION, $eFinancialYear->isClosed() === FALSE), $eFinancialYear);

		$h = '';

		if($cAsset->notEmpty()) {

			$h .= '<table class="pdf-table-bordered">';

				$h .= '<thead>';
					$h .= new AssetUi()->getTHead('asset', $header);
				$h .= '</thead>';

				$h .= '<tbody>';
					$h .= new AssetUi()->getPdfTBody($cAsset, 'asset');
				$h .= '</tbody>';

			$h .= '</table>';

		}

		if($cAssetGrant->notEmpty()) {

			$h .= '<table class="pdf-table-bordered" style="margin: auto;">';

				$h .= '<thead>';
					$h .= new AssetUi()->getTHead('grant', $header);
				$h .= '</thead>';

				$h .= '<tbody>';
					$h .= new AssetUi()->getPdfTBody($cAssetGrant, 'grant');
				$h .= '</tbody>';

			$h .= '</table>';

			$h .= '</table>';
		}

		return $h;

	}

	public function getAmortizations(
		\farm\Farm $eFarm,
		array $assetAmortizations,
		array $grantAmortizations,
	): string {

		$eFinancialYear = $eFarm['eFinancialYear'];
		$header = new \account\PdfUi()->getHeader($eFarm, new \account\PdfUi()->getTitle(\account\FinancialYearDocumentLib::ASSET_AMORTIZATION, $eFinancialYear->isClosed() === FALSE), $eFinancialYear);

		$h = '';
		if(count($assetAmortizations) > 0) {

			$h .= '<table class="pdf-table-bordered">';

				$h .= '<thead>';
					$h .= new AmortizationUi()->getPdfTHead($header, $assetAmortizations, 'asset');
				$h .= '</thead>';

				$h .= '<tbody>';
					$h .= new AmortizationUi()->getTBody($eFarm, $assetAmortizations);
				$h .= '</tbody>';

			$h .= '</table>';

		}

		if(count($grantAmortizations) > 0) {

			$h .= '<table class="pdf-table-bordered" style="margin: auto;">';

				$h .= '<thead>';
					$h .= new AmortizationUi()->getPdfTHead($header, $grantAmortizations, 'grant');
				$h .= '</thead>';

				$h .= '<tbody>';
					$h .= new AmortizationUi()->getTBody($eFarm, $grantAmortizations);
				$h .= '</tbody>';

			$h .= '</table>';
		}

		return $h;


	}

}
