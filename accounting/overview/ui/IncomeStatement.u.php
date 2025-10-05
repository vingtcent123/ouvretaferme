<?php
namespace overview;

class IncomeStatementUi {

	public function __construct() {
		\Asset::css('overview', 'overview.css');
	}

	public function getTitle(): string {
		return '<h1>'.s("Compte de Résultat").'</h1>';
	}

	public function getTable(\account\FinancialYear $eFinancialYear, \Collection $cOperation, \Collection $cAccount): string {

		$totals = [
			'operatingExpense' => 0,
			'financialExpense' => 0,
			'exceptionalExpense' => 0,
			'operatingIncome' => 0,
			'financialIncome' => 0,
			'exceptionalIncome' => 0,
		];
		// Charges d'exploitation, financières et exceptionnelles
		$operatingExpenses = [];
		$financialExpenses = [];
		$exceptionalExpenses = [];

		// Produits d'exploitation, financiers et exceptionnels
		$operatingIncomes = [];
		$financialIncomes = [];
		$exceptionalIncomes = [];

		foreach($cOperation as $eOperation) {
			switch((int)substr($eOperation['class'], 0, 2)) {

				case \account\AccountSetting::CHARGE_FINANCIAL_ACCOUNT_CLASS:
					$financialExpenses[] = $eOperation;
					$totals['financialExpense'] += $eOperation['amount'];
					break;
				case \account\AccountSetting::CHARGE_EXCEPTIONAL_ACCOUNT_CLASS:
					$exceptionalExpenses[] = $eOperation;
					$totals['exceptionalExpense'] += $eOperation['amount'];
					break;

				case \account\AccountSetting::PRODUCT_FINANCIAL_ACCOUNT_CLASS:
					$financialIncomes[] = $eOperation;
					$totals['financialIncome'] += $eOperation['amount'];
					break;

				case \account\AccountSetting::PRODUCT_EXCEPTIONAL_ACCOUNT_CLASS:
					$exceptionalIncomes[] = $eOperation;
					$totals['exceptionalIncome'] += $eOperation['amount'];
					break;

				default:
					if((int)substr($eOperation['class'], 0, 1) === \account\AccountSetting::CHARGE_ACCOUNT_CLASS) {
						$operatingExpenses[] = $eOperation;
						$totals['operatingExpense'] += $eOperation['amount'];
					} else {
						$operatingIncomes[] = $eOperation;
						$totals['operatingIncome'] += $eOperation['amount'];
					}

			}
		}

		$h = '';

		if($eFinancialYear['endDate'] > date('Y-m-d')) {
			$date = date('Y-m-d');
		} else {
			$date = $eFinancialYear['endDate'];
		}

		$h .= '<table class="overview_income-statement tr-hover">';

			$h .= '<tr class="overview_income-statement_row-title">';
				$h .= '<th class="text-center" colspan="6">'.s("Compte de résultat au {date}", ['date' => \util\DateUi::numeric($date)]).'</th>';
			$h .= '</tr>';

			$h .= '<tr class="overview_income-statement_group-title">';
				$h .= '<th colspan="3">'.s("Charges").'</th>';
				$h .= '<th colspan="3">'.s("Produits").'</th>';
			$h .= '</tr>';

			$h .= $this->displaySubCategoryLines($operatingExpenses, $operatingIncomes, $cAccount);

			$h .= '<tr class="overview_income-statement_group-total row-bold">';

				$h .= '<th colspan="2">'.s("Total charges d'exploitation").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['operatingExpense']).'</td>';
				$h .= '<th colspan="2">'.s("Total produits d'exploitation").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['operatingIncome']).'</td>';

			$h .= '</tr>';

			$h .= $this->displaySubCategoryLines($financialExpenses, $financialIncomes, $cAccount);

			$h .= '<tr class="overview_income-statement_group-total row-bold">';

				$h .= '<th colspan="2">'.s("Total charges financières").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['financialExpense']).'</td>';
				$h .= '<th colspan="2">'.s("Total produits financiers").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['financialIncome']).'</td>';

			$h .= '</tr>';

			$h .= $this->displaySubCategoryLines($exceptionalExpenses, $exceptionalIncomes, $cAccount);

			$h .= '<tr class="overview_income-statement_group-total row-bold">';

				$h .= '<th colspan="2">'.s("Total charges exceptionnelles").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['exceptionalExpense']).'</td>';
				$h .= '<th colspan="2">'.s("Total produits exceptionnels").'</th>';
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['exceptionalIncome']).'</td>';

			$h .= '</tr>';

			$totalExpenses = $totals['operatingExpense'] + $totals['financialExpense'] + $totals['exceptionalExpense'];
			$totalIncomes = $totals['operatingIncome'] + $totals['financialIncome'] + $totals['exceptionalIncome'];
			$difference = $totalIncomes - $totalExpenses;

			$h .= '<tr class="overview_income-statement_group-total">';

				if($difference > 0) {

					$h .= '<th colspan="2">'.s("Solde (bénéfice)").'</th>';
					$h .= '<td class="text-end">'.\util\TextUi::money($difference).'</td>';
					$h .= '<td colspan="2"></td>';
					$h .= '<td></td>';

				} else {

					$h .= '<td colspan="2"></td>';
					$h .= '<td></td>';
					$h .= '<th colspan="2">'.s("Solde (perte)").'</th>';
					$h .= '<td class="text-end">'.\util\TextUi::money(abs($difference)).'</td>';

				}

			$h .= '</tr>';

		$h .= '<tr class="overview_income-statement_group-total row-bold">';

			$h .= '<th colspan="2">'.s("Total général").'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totalExpenses + ($difference > 0 ? $difference : 0)).'</td>';
			$h .= '<th colspan="2">'.s("Total produits exceptionnels").'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totalIncomes + ($difference < 0 ? abs($difference) : 0)).'</td>';

		$h .= '</tr>';

		$h .= '</table>';

		return $h;

	}

	private function displaySubCategoryLines(array $expenses, array $incomes, \Collection $cAccount): string {

		$h = '';

		while(count($expenses) > 0 or count($incomes) > 0) {

			$expense = array_shift($expenses);
			$income = array_shift($incomes);

			$h .= '<tr class="overview_income-statement_line">';

				if($expense !== null) {

					$h .= '<td class="text-end td-min-content">'.encode($expense['class']).'</td>';
					$h .= '<td>';
						if($cAccount->offsetExists($expense['class'])) {
							$eAccount = $cAccount->offsetGet($expense['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($expense['class'], 0, 2));
						}
						$h .= encode($eAccount['description']);
					$h .= '</td>';
					$h .= '<td class="text-end">'.\util\TextUi::money($expense['amount']).'</td>';

				} else {
					$h .= '<td></td>';
					$h .= '<td></td>';
					$h .= '<td></td>';
				}

				if($income !== null) {

					$h .= '<td class="text-end td-min-content">'.encode($income['class']).'</td>';
					$h .= '<td>';
						if($cAccount->offsetExists($income['class'])) {
							$eAccount = $cAccount->offsetGet($income['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($income['class'], 0, 2));
						}
						$h .= encode($eAccount['description']);
					$h .= '</td>';
					$h .= '<td class="text-end">'.\util\TextUi::money($income['amount']).'</td>';

				} else {
					$h .= '<td></td>';
					$h .= '<td></td>';
					$h .= '<td></td>';
				}

			$h .= '</tr>';

		}

		return $h;
	}

}
?>
