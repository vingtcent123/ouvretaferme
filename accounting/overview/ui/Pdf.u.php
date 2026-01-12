<?php
namespace overview;

class PdfUi {

	public function __construct() {

		\Asset::css('pdf', 'pdf.css');

	}

	public function getBalanceSheet(
		\farm\Farm $eFarm,
		array $balanceSheetData,
		array $totals,
		\Collection $cAccount,
	): string {

		$eFinancialYear = $eFarm['eFinancialYear'];
		$now = date('Y-m-d');
		if($eFinancialYear['endDate'] < $now) {
			$date = $eFinancialYear['endDate'];
		} else {
			$date = $now;
		}

		$h = '<style>@page {	size: A4; margin: calc(var(--margin-bloc-height) + 2cm) 1cm 1cm; }</style>';

		if(get_exists('test') === TRUE) {
			$h .= \pdf\PdfUi::getHeader(s("{farm} - exercice {year}<br />Bilan au {date}", ['farm' => $eFarm['legalName'], 'year' => $eFinancialYear->getLabel(), 'date' => \util\DateUi::numeric($date)]), $eFinancialYear);
		}

		$h .= '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				$h .= '<table class="pdf-table-bordered" style="margin: 0 auto 1rem;">';

					$h .= '<thead>';
						$h .= new BalanceSheetUi()->getPdfTHead('assets');
					$h .= '</thead>';

					$h .= new BalanceSheetUi()->getPdfTBodyAssets('assets', $balanceSheetData, $totals, $cAccount);

				$h .= '</table>';

				$h .= '<table class="pdf-table-bordered" style="margin: auto;">';

					$h .= '<thead>';
						$h .= new BalanceSheetUi()->getPdfTHead('liabilities');
					$h .= '</thead>';

					$h .= new BalanceSheetUi()->getPdfTBodyAssets('liabilities', $balanceSheetData, $totals, $cAccount);

				$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		if(get_exists('test') === TRUE) {
			$h .= \pdf\PdfUi::getFooter();
		}
		return $h;

	}
}
?>
