<?php
namespace overview;

class IncomeStatementUi {

	public function __construct() {
		\Asset::css('overview', 'overview.css');
	}

	public function getTitle(): string {
		return '<h1>'.s("Compte de Résultat").'</h1>';
	}

	public function getTable(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYearPrevious, \account\FinancialYear $eFinancialYear, array $resultData, \Collection $cAccount): string {

		$hasPrevious = $eFinancialYearPrevious->notEmpty();

		$totals = [
			'operatingExpense' => ['current' => 0, 'previous' => 0],
			'financialExpense' => ['current' => 0, 'previous' => 0],
			'exceptionalExpense' => ['current' => 0, 'previous' => 0],
			'operatingIncome' => ['current' => 0, 'previous' => 0],
			'financialIncome' => ['current' => 0, 'previous' => 0],
			'exceptionalIncome' => ['current' => 0, 'previous' => 0],
		];
		// Charges d'exploitation, financières et exceptionnelles
		$operatingExpenses = [];
		$financialExpenses = [];
		$exceptionalExpenses = [];

		// Produits d'exploitation, financiers et exceptionnels
		$operatingIncomes = [];
		$financialIncomes = [];
		$exceptionalIncomes = [];

		foreach(array_merge($resultData['expenses'], $resultData['incomes']) as $data) {
			switch((int)substr($data['class'], 0, 2)) {

				case \account\AccountSetting::CHARGE_FINANCIAL_ACCOUNT_CLASS:
					$financialExpenses[] = $data;
					$totals['financialExpense']['current'] += $data['current'];
					$totals['financialExpense']['previous'] += $data['previous'];
					break;
				case \account\AccountSetting::CHARGE_EXCEPTIONAL_ACCOUNT_CLASS:
					$exceptionalExpenses[] = $data;
					$totals['exceptionalExpense']['current'] += $data['current'];
					$totals['exceptionalExpense']['previous'] += $data['previous'];
					break;

				case \account\AccountSetting::PRODUCT_FINANCIAL_ACCOUNT_CLASS:
					$financialIncomes[] = $data;
					$totals['financialIncome']['current'] += $data['current'];
					$totals['financialIncome']['previous'] += $data['previous'];
					break;

				case \account\AccountSetting::PRODUCT_EXCEPTIONAL_ACCOUNT_CLASS:
					$exceptionalIncomes[] = $data;
					$totals['exceptionalIncome']['current'] += $data['current'];
					$totals['exceptionalIncome']['previous'] += $data['previous'];
					break;

				default:
					if((int)substr($data['class'], 0, 1) === \account\AccountSetting::CHARGE_ACCOUNT_CLASS) {
						$operatingExpenses[] = $data;
						$totals['operatingExpense']['current'] += $data['current'] ?? 0;
						$totals['operatingExpense']['previous'] += $data['previous'] ?? 0;
					} else {
						$operatingIncomes[] = $data;
						$totals['operatingIncome']['current'] += $data['current'] ?? 0;
						$totals['operatingIncome']['previous'] += $data['previous'] ?? 0;
					}

			}
		}

		$h = '<div class="util-overflow-md stick-xs">';

		if($eFinancialYear['endDate'] > date('Y-m-d')) {
			$date = date('Y-m-d');
		} else {
			$date = $eFinancialYear['endDate'];
		}

		$h .= '<table class="overview_income-statement tr-hover'.($hasPrevious ? ' overview_income-statement_has_previous' : '').'">';

			$h .= '<tr class="overview_income-statement_row-title">';
				$h .= '<th class="text-center" colspan="'.($hasPrevious ? 8 : 6).'">'.s("{farm} - exercice {year}<br />Compte de résultat au {date}", ['farm' => $eFarm['legalName'], 'year' => \account\FinancialYearUi::getYear($eFinancialYear), 'date' => \util\DateUi::numeric($date)]).'</th>';
			$h .= '</tr>';

			$h .= '<tr class="overview_income-statement_group-title">';
				$h .= '<th colspan="2">'.s("Charges").'</th>';
				$h .= '<th class="text-center">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYear)).'</th>';
				if($hasPrevious) {
					$h .= '<th class="text-center">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYearPrevious)).'</th>';
				}
				$h .= '<th colspan="2">'.s("Produits").'</th>';
				$h .= '<th class="text-center">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYear)).'</th>';
				if($hasPrevious) {
					$h .= '<th class="text-center">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYearPrevious)).'</th>';
				}
			$h .= '</tr>';

			$h .= $this->displaySubCategoryLines($operatingExpenses, $operatingIncomes, $cAccount, $hasPrevious);

			$h .= $this->displaySubTotal($totals, 'operating', $hasPrevious);

			$h .= $this->displaySubCategoryLines($financialExpenses, $financialIncomes, $cAccount, $hasPrevious);

			$h .= $this->displaySubTotal($totals, 'financial', $hasPrevious);

			$h .= $this->displaySubCategoryLines($exceptionalExpenses, $exceptionalIncomes, $cAccount, $hasPrevious);

			$h .= $this->displaySubTotal($totals, 'exceptional', $hasPrevious);

			$totalExpensesCurrent = $totals['operatingExpense']['current'] + $totals['financialExpense']['current'] + $totals['exceptionalExpense']['current'];
			$totalIncomesCurrent = $totals['operatingIncome']['current'] + $totals['financialIncome']['current'] + $totals['exceptionalIncome']['current'];
			$differenceCurrent = $totalIncomesCurrent - $totalExpensesCurrent;

			if($hasPrevious) {
				$totalExpensesPrevious = $totals['operatingExpense']['previous'] + $totals['financialExpense']['previous'] + $totals['exceptionalExpense']['previous'];
				$totalIncomesPrevious = $totals['operatingIncome']['previous'] + $totals['financialIncome']['previous'] + $totals['exceptionalIncome']['previous'];
				$differencePrevious = $totalIncomesPrevious - $totalExpensesPrevious;
			}

			$h .= '<tr class="overview_income-statement_group-total">';

					$h .= '<th colspan="2">'.s("Résultat d'exploitation (bénéfice)").'</th>';
					$h .= '<td class="text-end">'.($differenceCurrent > 0 ? \util\TextUi::money($differenceCurrent) : '').'</td>';
					if($hasPrevious) {
						$h .= '<td class="text-end">'.($differencePrevious > 0 ? \util\TextUi::money($differencePrevious) : '').'</td>';
					}
					$h .= '<th colspan="2">'.s("Résultat d'exploitation (perte)").'</th>';
					$h .= '<td class="text-end">'.($differenceCurrent < 0 ? \util\TextUi::money(abs($differenceCurrent)) : '').'</td>';
					if($hasPrevious) {
						$h .= '<td class="text-end">'.($differencePrevious < 0 ? \util\TextUi::money(abs($differencePrevious)) : '').'</td>';
					}

			$h .= '</tr>';

		$h .= '<tr class="overview_income-statement_group-total row-bold">';

			$h .= '<th colspan="2">'.s("Total général").'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totalExpensesCurrent + ($differenceCurrent > 0 ? $differenceCurrent : 0)).'</td>';
			if($hasPrevious) {
				$h .= '<td class="text-end">'.\util\TextUi::money($totalExpensesPrevious + ($differencePrevious > 0 ? $differencePrevious : 0)).'</td>';
			}
			$h .= '<th colspan="2">'.s("Total général").'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totalIncomesCurrent + ($differenceCurrent < 0 ? abs($differenceCurrent) : 0)).'</td>';
			if($hasPrevious) {
				$h .= '<td class="text-end">'.\util\TextUi::money($totalIncomesPrevious + ($differencePrevious < 0 ? abs($differencePrevious) : 0)).'</td>';
			}

		$h .= '</tr>';

		$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	private function displaySubTotal(array $totals, string $type, bool $hasPrevious): string {

		switch($type) {
			case 'exceptional':
				$expensesTitle = s("Total charges exceptionnelles");
				$incomesTitle = s("Total produits exceptionnels");
				break;

			case 'financial':
				$expensesTitle = s("Total charges financières");
				$incomesTitle = s("Total produits financiers");
				break;

			case 'operating':
				$expensesTitle = s("Total charges d'exploitation");
				$incomesTitle = s("Total produits d'exploitation");
				break;
		}

		$h = '<tr class="overview_income-statement_group-total row-bold">';

			$h .= '<th colspan="2">'.$expensesTitle.'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Expense']['current']).'</td>';
			if($hasPrevious) {
				$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Expense']['previous']).'</td>';
			}
			$h .= '<th colspan="2">'.$incomesTitle.'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Income']['current']).'</td>';
			if($hasPrevious) {
				$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Income']['previous']).'</td>';
			}

		$h .= '</tr>';

		return $h;

	}

	private function displaySubCategoryLines(array $expenses, array $incomes, \Collection $cAccount, bool $hasPrevious): string {

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
					$h .= '<td class="text-end">'.\util\TextUi::money($expense['current']).'</td>';
					if($hasPrevious) {
						$h .= '<td class="text-end">'.\util\TextUi::money($expense['previous']).'</td>';
					}

				} else {
					$h .= '<td></td>';
					$h .= '<td></td>';
					$h .= '<td></td>';
					if($hasPrevious) {
						$h .= '<td></td>';
					}
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
					$h .= '<td class="text-end">'.\util\TextUi::money($income['current']).'</td>';
					if($hasPrevious) {
						$h .= '<td class="text-end">'.\util\TextUi::money($income['previous']).'</td>';
					}

				} else {
					$h .= '<td></td>';
					$h .= '<td></td>';
					$h .= '<td></td>';
					if($hasPrevious) {
						$h .= '<td></td>';
					}
				}

			$h .= '</tr>';

		}

		return $h;
	}

}
?>
