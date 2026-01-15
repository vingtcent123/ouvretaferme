<?php
namespace journal;

class PdfUi {

	public function __construct() {

		\Asset::css('company', 'pdf.css');

	}

	public function balance(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYearPrevious, array $trialBalanceData, array $trialBalancePreviousData, \Search $search): string {

		$eFinancialYear = $eFarm['eFinancialYear'];

		$h = '<style>@page {	size: A4; margin: calc(var(--margin-bloc-height) + 2cm) 1cm 1cm; }</style>';

		if(get_exists('test') === TRUE) {
			$type = $search->get('precision') === 8 ? \account\FinancialYearDocumentLib::BALANCE_DETAILED : \account\FinancialYearDocumentLib::BALANCE;
			$h .= \account\PdfUi::getHeader($eFarm, new \account\PdfUi()->getTitle($type, $eFinancialYear->isClosed() === FALSE), $eFinancialYear);
		}

		$h .= '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

					$h .= '<table class="pdf-table-bordered" style="margin: 0 auto 1rem;">';

						$h .= '<thead>';
							$h .= new BalanceUi()->getPdfTHead($eFinancialYear, $eFinancialYearPrevious);
						$h .= '</thead>';

						$h .= '<tbody>';
							$h .= new BalanceUi()->getTBody($eFinancialYearPrevious, $trialBalanceData, $trialBalancePreviousData, $search, 'pdf');
						$h .= '</tbody>';

					$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		if(get_exists('test') === TRUE) {
			$h .= \account\PdfUi::getFooter();
		}
		return $h;


	}

}
?>
