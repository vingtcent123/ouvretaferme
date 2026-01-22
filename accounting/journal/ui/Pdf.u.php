<?php
namespace journal;

class PdfUi {

	public function __construct() {
	}

	public function balance(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYearPrevious, array $trialBalanceData, array $trialBalancePreviousData, string $type): string {

		$eFinancialYear = $eFarm['eFinancialYear'];
		$header = \account\PdfUi::getHeader($eFarm, new \account\PdfUi()->getTitle($type, $eFinancialYear->isClosed() === FALSE), $eFinancialYear);

		$h = '<table class="pdf-table-bordered">';

			$h .= '<thead>';
				$h .= new BalanceUi()->getPdfTHead($header, $eFinancialYear, $eFinancialYearPrevious);
			$h .= '</thead>';

			$h .= '<tbody>';
				$h .= new BalanceUi()->getPdfTBody($eFinancialYearPrevious, $trialBalanceData, $trialBalancePreviousData, $type);
			$h .= '</tbody>';

		$h .= '</table>';

		return $h;

	}

}
?>
