<?php
namespace journal;

class PdfUi {

	public function __construct() {

		\Asset::css('company', 'pdf.css');

	}

	public function balance(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYearPrevious, array $trialBalanceData, array $trialBalancePreviousData): string {

		$eFinancialYear = $eFarm['eFinancialYear'];

		$h = '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				$h .= '<table class="pdf-table-bordered" style="margin: 0 auto 1rem;">';

					$h .= '<thead>';
						$h .= new BalanceUi()->getPdfTHead($eFinancialYear, $eFinancialYearPrevious);
					$h .= '</thead>';

					$h .= '<tbody>';
						$h .= new BalanceUi()->getPdfTBody($eFinancialYearPrevious, $trialBalanceData, $trialBalancePreviousData);
					$h .= '</tbody>';

				$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

}
?>
