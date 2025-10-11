<?php
namespace overview;

class IncomeStatementUi {

	public function __construct() {
		\Asset::css('overview', 'overview.css');
	}

	public function getTitle(): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Compte de Résultat");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#income-statement-search")').' class="btn btn-primary">'.\Asset::icon('filter').' '.s("Configurer la synthèse").'</a> ';
			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

	public function getSearch(\Search $search, \Collection $cFinancialYear, \account\FinancialYear $eFinancialYear): string {

		$excludedProperties = ['ids'];
		if($search->get('view') === IncomeStatementLib::VIEW_BASIC) {
			$excludedProperties[] = 'view';
		}

		$h = '<div id="income-statement-search" class="util-block-search '.($search->empty($excludedProperties) === TRUE ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH;

		$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);
		$h .= '<div>';
			$h .= $form->select('view', [
				IncomeStatementLib::VIEW_BASIC => s("Vue synthétique"),
				IncomeStatementLib::VIEW_DETAILED => s("Vue détaillée"),
			], $search->get('view'), ['mandatory' => TRUE]);

			if($cFinancialYear->count() > 1) {
				$h .= $form->select('financialYearComparison', $cFinancialYear
					->filter(fn($e) => !$e->is($eFinancialYear))
					->makeArray(function($e, &$key) {
						$key = $e['id'];
						return s("Exercice {value}", \account\FinancialYearUi::getYear($e));
					}), $search->get('financialYearComparison'), ['placeholder' => s("Comparer avec un autre exercice")]);
			}

			$h .= $form->submit(s("Valider"), ['class' => 'btn btn-secondary']);
			$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
		$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}


	public function getTable(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYearComparison, \account\FinancialYear $eFinancialYear, array $resultData, \Collection $cAccount, bool $displaySummary): string {

		$hasComparison = $eFinancialYearComparison->notEmpty();

		$totals = [
			'operatingExpense' => [
				'current' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['current'], $resultData['expenses']['operating'])),
				'comparison' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['comparison'], $resultData['expenses']['operating'])),
			],
			'financialExpense' => [
				'current' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['current'], $resultData['expenses']['financial'])),
				'comparison' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['comparison'], $resultData['expenses']['financial'])),
			],
			'exceptionalExpense' => [
				'current' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['current'], $resultData['expenses']['exceptional'])),
				'comparison' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['comparison'], $resultData['expenses']['exceptional'])),
			],
			'operatingIncome' => [
				'current' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['current'], $resultData['incomes']['operating'])),
				'comparison' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['comparison'], $resultData['incomes']['operating'])),
			],
			'financialIncome' => [
				'current' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['current'], $resultData['incomes']['financial'])),
				'comparison' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['comparison'], $resultData['incomes']['financial'])),
			],
			'exceptionalIncome' => [
				'current' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['current'], $resultData['incomes']['exceptional'])),
				'comparison' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['comparison'], $resultData['incomes']['exceptional'])),
			],
		];

		if($eFinancialYear['endDate'] > date('Y-m-d')) {
			$date = date('Y-m-d');
		} else {
			$date = $eFinancialYear['endDate'];
		}

		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="tr-even overview tr-hover'.($hasComparison ? ' overview_has_previous' : '').'">';

				$h .= '<thead class="thead-sticky">';

					$h .= '<tr class="overview_row_sizing">';
						$h .= '<td colspan="'.($hasComparison ? 5 : 4).'"></td>';
						$h .= '<td colspan="'.($hasComparison ? 5 : 4).'"></td>';
					$h .= '</tr>';

					$h .= '<tr class="overview_row-title">';
						$h .= '<th class="text-center" colspan="'.($hasComparison ? 10 : 8).'">'.s("{farm} - exercice {year}<br />Compte de résultat au {date}", ['farm' => $eFarm['legalName'], 'year' => \account\FinancialYearUi::getYear($eFinancialYear), 'date' => \util\DateUi::numeric($date)]).'</th>';
					$h .= '</tr>';

					$h .= '<tr class="overview_group-title">';
						$h .= '<th colspan="3">'.s("Charges").'</th>';
						$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYear)).'</th>';
						if($hasComparison) {
							$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYearComparison)).'</th>';
						}
						$h .= '<th colspan="3">'.s("Produits").'</th>';
						$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYear)).'</th>';
						if($hasComparison) {
							$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYearComparison)).'</th>';
						}
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					$h .= $this->displaySubCategoryLines($eFarm, $resultData['expenses']['operating'], $resultData['incomes']['operating'], $cAccount, $hasComparison);

					$h .= $this->displaySubTotal($totals, 'operating', $hasComparison);

					$h .= $this->displaySubCategoryLines($eFarm, $resultData['expenses']['financial'], $resultData['incomes']['financial'], $cAccount, $hasComparison);

					$h .= $this->displaySubTotal($totals, 'financial', $hasComparison);

					$h .= $this->displaySubCategoryLines($eFarm, $resultData['expenses']['exceptional'], $resultData['incomes']['exceptional'], $cAccount, $hasComparison);

					$h .= $this->displaySubTotal($totals, 'exceptional', $hasComparison);

					$totalExpensesCurrent = $totals['operatingExpense']['current'] + $totals['financialExpense']['current'] + $totals['exceptionalExpense']['current'];
					$totalIncomesCurrent = $totals['operatingIncome']['current'] + $totals['financialIncome']['current'] + $totals['exceptionalIncome']['current'];
					$differenceCurrent = $totalIncomesCurrent - $totalExpensesCurrent;

					if($hasComparison) {
						$totalExpensesPrevious = $totals['operatingExpense']['comparison'] + $totals['financialExpense']['comparison'] + $totals['exceptionalExpense']['comparison'];
						$totalIncomesPrevious = $totals['operatingIncome']['comparison'] + $totals['financialIncome']['comparison'] + $totals['exceptionalIncome']['comparison'];
						$differencePrevious = $totalIncomesPrevious - $totalExpensesPrevious;
					}

					$h .= '<tr class="overview_group-total">';

						if($differenceCurrent < 0 and ($hasComparison === FALSE or $differencePrevious < 0)) {
							$h .= '<th colspan="'.($hasComparison ? 4 : 3).'"></th>';
							$h .= '<td></td>';
						} else {

							$h .= '<th colspan="3">'.s("Résultat d'exploitation (bénéfice)").'</th>';
							$h .= '<td class="text-end">'.($differenceCurrent > 0 ? \util\TextUi::money($differenceCurrent) : '').'</td>';
							if($hasComparison) {
								$h .= '<td class="text-end">'.($differencePrevious > 0 ? \util\TextUi::money($differencePrevious) : '').'</td>';
							}
						}
						if($differenceCurrent > 0 and ($hasComparison === FALSE or $differencePrevious > 0)) {
							$h .= '<th colspan="'.($hasComparison ? 4 : 3).'"></th>';
							$h .= '<td></td>';
						} else {
							$h .= '<th colspan="3">'.s("Résultat d'exploitation (perte)").'</th>';
							$h .= '<td class="text-end">'.($differenceCurrent < 0 ? \util\TextUi::money(abs($differenceCurrent)) : '').'</td>';
							if($hasComparison) {
								$h .= '<td class="text-end">'.($differencePrevious < 0 ? \util\TextUi::money(abs($differencePrevious)) : '').'</td>';
							}
						}

					$h .= '</tr>';

					$h .= '<tr class="overview_group-total row-bold">';

						$h .= '<th colspan="3">'.s("Total général").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totalExpensesCurrent + ($differenceCurrent > 0 ? $differenceCurrent : 0)).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totalExpensesPrevious + ($differencePrevious > 0 ? $differencePrevious : 0)).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total général").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totalIncomesCurrent + ($differenceCurrent < 0 ? abs($differenceCurrent) : 0)).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totalIncomesPrevious + ($differencePrevious < 0 ? abs($differencePrevious) : 0)).'</td>';
						}

					$h .= '</tr>';

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	private function displaySubTotal(array $totals, string $type, bool $hasComparison): string {

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

		$h = '<tr class="overview_group-total row-bold">';

			$h .= '<th colspan="3">'.$expensesTitle.'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Expense']['current']).'</td>';
			if($hasComparison) {
				$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Expense']['comparison']).'</td>';
			}
			$h .= '<th colspan="3">'.$incomesTitle.'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Income']['current']).'</td>';
			if($hasComparison) {
				$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Income']['comparison']).'</td>';
			}

		$h .= '</tr>';

		return $h;

	}

	private function displaySubCategoryLines(\farm\Farm$eFarm, array $expenses, array $incomes, \Collection $cAccount, bool $hasComparison): string {

		$h = '';

		while(count($expenses) > 0 or count($incomes) > 0) {

			$expense = array_shift($expenses);
			$income = array_shift($incomes);

			$h .= '<tr class="overview_line">';

				if($expense !== null) {

					$style = ($expense['isSummary'] ?? FALSE) ? ' style="font-weight: bold";' : '';

					$h .= '<td class="text-end td-min-content"'.$style.'>'.encode($expense['class']).'</td>';
					$h .= '<td'.$style.'>';
						if($cAccount->offsetExists($expense['class'])) {
							$eAccount = $cAccount->offsetGet($expense['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($expense['class'], 0, 2));
						}
						$h .= encode($eAccount['description']);
					$h .= '</td>';
					$h .= '<td class="td-min-content">'.new CommonUi()->getDropdownClass($eFarm, $expense['class']).'</td>';
					$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($expense['current']).'</td>';
					if($hasComparison) {
						$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($expense['comparison']).'</td>';
					}

				} else {
					$h .= '<td></td>';
					$h .= '<td></td>';
					$h .= '<td></td>';
					$h .= '<td></td>';
					if($hasComparison) {
						$h .= '<td></td>';
					}
				}

				if($income !== null) {

					$style = ($income['isSummary'] ?? FALSE) ? ' style="font-weight: bold";' : '';

					$h .= '<td class="text-end td-min-content"'.$style.'>'.encode($income['class']).'</td>';
					$h .= '<td'.$style.'>';
						if($cAccount->offsetExists($income['class'])) {
							$eAccount = $cAccount->offsetGet($income['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($income['class'], 0, 2));
						}
						$h .= encode($eAccount['description']);
					$h .= '</td>';
					$h .= '<td class="td-min-content">'.new CommonUi()->getDropdownClass($eFarm, $income['class']).'</td>';
					$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($income['current']).'</td>';
					if($hasComparison) {
						$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($income['comparison']).'</td>';
					}

				} else {
					$h .= '<td></td>';
					$h .= '<td></td>';
					$h .= '<td></td>';
					$h .= '<td></td>';
					if($hasComparison) {
						$h .= '<td></td>';
					}
				}

			$h .= '</tr>';

		}

		return $h;
	}

}
?>
