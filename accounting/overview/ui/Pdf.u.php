<?php
namespace overview;

class PdfUi {

	public function __construct() {

		\Asset::css('company', 'pdf.css');

	}

	public function getBalanceSheet(
		\farm\Farm $eFarm,
		array $balanceSheetData,
		array $totals,
		\Collection $cAccount,
		bool $isDetailed,
	): string {

		$h = '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				$h .= '<table class="pdf-table-bordered" style="margin: 0 auto 1rem;">';

					$h .= '<thead>';
						$h .= new BalanceSheetUi()->getPdfTHead('assets');
					$h .= '</thead>';

					$h .= new BalanceSheetUi()->getPdfTBodyAssets('assets', $balanceSheetData, $totals, $cAccount, $isDetailed);

				$h .= '</table>';

				$h .= '<table class="pdf-table-bordered" style="margin: auto;">';

					$h .= '<thead>';
						$h .= new BalanceSheetUi()->getPdfTHead('liabilities');
					$h .= '</thead>';

					$h .= new BalanceSheetUi()->getPdfTBodyAssets('liabilities', $balanceSheetData, $totals, $cAccount, $isDetailed);

				$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getIncomeStatement(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearComparison, array $resultData, \Collection $cAccount): string {

		$hasComparison = $eFinancialYearComparison->notEmpty();
		$totals = new IncomeStatementUi()->generateTotals($resultData);

		$totalExpensesCurrent = $totals['operatingExpense']['current'] + $totals['financialExpense']['current'] + $totals['exceptionalExpense']['current'];
		$totalIncomesCurrent = $totals['operatingIncome']['current'] + $totals['financialIncome']['current'] + $totals['exceptionalIncome']['current'];
		$differenceCurrent = $totalIncomesCurrent - $totalExpensesCurrent;

		if($hasComparison) {
			$totalExpensesPrevious = $totals['operatingExpense']['comparison'] + $totals['financialExpense']['comparison'] + $totals['exceptionalExpense']['comparison'];
			$totalIncomesPrevious = $totals['operatingIncome']['comparison'] + $totals['financialIncome']['comparison'] + $totals['exceptionalIncome']['comparison'];
			$differencePrevious = $totalIncomesPrevious - $totalExpensesPrevious;
		}

		$h = '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';

				$h .= '<table class="pdf-table-bordered" style="margin: 0 auto 1rem;">';

					$h .= '<thead>';
						$h .= new \overview\IncomeStatementUi()->getPdfTHead('expenses', $eFinancialYear, $eFinancialYearComparison);
					$h .= '</thead>';

					$h .= '<tbody>';

						$h .= new \overview\IncomeStatementUi()->getPdfTBody('expenses', $eFarm, $cAccount, $resultData['expenses'], $eFinancialYearComparison);

						// Bénéfice ou perte
						if($differenceCurrent > 0 or ($hasComparison and $differencePrevious > 0)) {

							$h .= '<tr>';

								$h .= '<td></td>';
								$h .= '<th>'.s("Résultat d'exploitation (bénéfice)").'</th>';
								$h .= '<td class="text-end">'.($differenceCurrent > 0 ? \util\TextUi::money($differenceCurrent, precision: 0) : '').'</td>';
								if($hasComparison) {
									$h .= '<td class="text-end">'.($differencePrevious > 0 ? \util\TextUi::money($differencePrevious) : '').'</td>';
								}

							$h .= '</tr>';
						}

						// Total des charges
						$h .= '<tr class="overview_group-total tr-bold">';
							$h .= '<th colspan="2">'.s("Total général").'</th>';
							$h .= '<td class="text-end">'.\util\TextUi::money($totalExpensesCurrent + ($differenceCurrent > 0 ? $differenceCurrent : 0), precision: 0).'</td>';
							if($hasComparison) {
								$h .= '<td class="text-end">'.\util\TextUi::money($totalExpensesPrevious + ($differencePrevious > 0 ? $differencePrevious : 0), precision: 0).'</td>';
							}
						$h .= '</tr>';

					$h .= '</tbody>';
				$h .= '</table>';

				$h .= '<table class="pdf-table-bordered" style="margin: auto;">';

					$h .= '<thead>';
						$h .= new \overview\IncomeStatementUi()->getPdfTHead('incomes', $eFinancialYear, $eFinancialYearComparison);
					$h .= '</thead>';

					$h .= '</tbody>';
						$h .= new \overview\IncomeStatementUi()->getPdfTBody('incomes', $eFarm, $cAccount, $resultData['incomes'], $eFinancialYearComparison);

						// Bénéfice ou perte
						if($differenceCurrent < 0 or $differencePrevious < 0) {

							$h .= '<tr>';

								$h .= '<td></td>';
								$h .= '<th>'.s("Résultat d'exploitation (perte)").'</th>';
								$h .= '<td class="text-end">'.($differenceCurrent < 0 ? \util\TextUi::money(abs($differenceCurrent), precision: 0) : '').'</td>';
								if($hasComparison) {
									$h .= '<td class="text-end">'.($differencePrevious < 0 ? \util\TextUi::money(abs($differencePrevious)) : '').'</td>';
								}

							$h .= '</tr>';
						}

						// Total produits
						$h .= '<tr class="overview_group-total tr-bold">';
							$h .= '<th colspan="2">'.s("Total général").'</th>';
							$h .= '<td class="text-end">'.\util\TextUi::money($totalIncomesCurrent + ($differenceCurrent < 0 ? abs($differenceCurrent) : 0), precision: 0).'</td>';
							if($hasComparison) {
								$h .= '<td class="text-end">'.\util\TextUi::money($totalIncomesPrevious + ($differencePrevious < 0 ? abs($differencePrevious) : 0), precision: 0).'</td>';
							}
						$h .= '</tr>';

					$h .= '</tbody>';

				$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}
	public function getSig(\account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearComparison, array $values): string {

		$h = '<div class="pdf-document-wrapper">';

			$h .= '<div class="pdf-document-content">';
				$h .= '<table class="pdf-table-bordered" style="margin: 0 auto 1rem;">';

					$h .= '<thead>';
						$h .= new \overview\SigUi()->getTHead($eFinancialYear, $eFinancialYearComparison, 'pdf');
					$h .= '</thead>';

				$h .= '<tbody>';
					$h .= new \overview\SigUi()->getTBody($eFinancialYear, $eFinancialYearComparison, $values, 'pdf');
				$h .= '</tbody>';

				$h .= '</table>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}
}
?>
