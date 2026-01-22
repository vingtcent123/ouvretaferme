<?php
namespace overview;

class IncomeStatementUi {

	public function __construct() {
		\Asset::css('overview', 'incomeStatement.css');
	}

	public function getSearch(\Search $search, \Collection $cFinancialYear, \account\FinancialYear $eFinancialYear): string {

		$excludedProperties = ['ids'];
		if($search->get('type') === IncomeStatementLib::VIEW_BASIC) {
			$excludedProperties[] = 'type';
		}

		$h = '<div id="income-statement-search" class="util-block-search '.($search->empty($excludedProperties) === TRUE ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH;

		$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);
			$h .= '<fieldset>';
				$h .= '<legend>'.s("Vue").'</legend>';
				$h .= $form->select('type', [
					IncomeStatementLib::VIEW_BASIC => s("Vue synthétique"),
					IncomeStatementLib::VIEW_DETAILED => s("Vue détaillée"),
				], $search->get('type'), ['mandatory' => TRUE]);
			$h .= '</fieldset>';

			if($cFinancialYear->count() > 1) {
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Comparer avec un autre exercice").'</legend>';
					$values = [];
					foreach($cFinancialYear as $eFinancialYearCurrent) {
						if($eFinancialYearCurrent->is($eFinancialYear)) {
							continue;
						}
						$values[$eFinancialYearCurrent['id']] = s("Exercice {value}", $eFinancialYearCurrent->getLabel());
					}
					$h .= $form->select('financialYearComparison', $values, $search->get('financialYearComparison'));
				$h .= '</fieldset>';
			}

			$h .= '<div class="util-search-submit">';
				$h .= $form->submit(s("Valider"));
				$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
			$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getTHead(\account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearComparison): string  {

		$hasComparison = $eFinancialYearComparison->notEmpty();

		$h = '';


		$h .= '<tr class="overview_group-title">';
			$h .= '<th colspan="3">'.s("Charges").'</th>';
			$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", $eFinancialYear->getLabel()).'</th>';
			if($hasComparison) {
				$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", $eFinancialYearComparison->getLabel()).'</th>';
			}
			$h .= '<th colspan="3">'.s("Produits").'</th>';
			$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", $eFinancialYear->getLabel()).'</th>';
			if($hasComparison) {
				$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", $eFinancialYearComparison->getLabel()).'</th>';
			}
		$h .= '</tr>';

		return $h;
	}

	public function getPdfTHead(string $type, string $header, \account\FinancialYear $eFinancialYear, \account\FinancialYear $eFinancialYearComparison): string  {

		$hasComparison = $eFinancialYearComparison->notEmpty();

		$h = $header;
		$h .= '<tr class="overview_group-title">';
			$h .= '<th colspan="2">'.($type === 'expenses' ? s("Charges") : s("Produits")).'</th>';
			$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", $eFinancialYear->getLabel()).'</th>';
			if($hasComparison) {
				$h .= '<th class="text-center td-min-content">'.s("Exercice {value}", $eFinancialYearComparison->getLabel()).'</th>';
			}
		$h .= '</tr>';

		return $h;
	}

	public function getPdfTBody(string $type, \farm\Farm $eFarm, \Collection $cAccount, array $resultData, \account\FinancialYear $eFinancialYearComparison): string  {

		$hasComparison = $eFinancialYearComparison->notEmpty();

		$totals = [
			'operating' => [
				'current' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['current'], $resultData['operating'])),
				'comparison' => $hasComparison === FALSE ? 0 : array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['comparison'], $resultData['operating'])),
			],
			'financial' => [
				'current' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['current'], $resultData['financial'])),
				'comparison' => $hasComparison === FALSE ? 0 : array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['comparison'], $resultData['financial'])),
			],
			'exceptional' => [
				'current' => array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['current'], $resultData['exceptional'])),
				'comparison' => $hasComparison === FALSE ? 0 : array_sum(array_map(fn($data) => ($data['isSummary'] ?? FALSE) ? 0 : $data['comparison'], $resultData['exceptional'])),
			],
		];

		$h = $this->displayPdfSubCategoryLines($eFarm, $resultData['operating'], $cAccount, $hasComparison);

		$h .= $this->displayPdfSubTotal($type, $totals['operating'], 'operating', $hasComparison);

		$h .= $this->displayPdfSubCategoryLines($eFarm, $resultData['financial'], $cAccount, $hasComparison);

		$h .= $this->displayPdfSubTotal($type, $totals['financial'], 'financial', $hasComparison);

		$h .= $this->displayPdfSubCategoryLines($eFarm, $resultData['exceptional'], $cAccount, $hasComparison);

		$h .= $this->displayPdfSubTotal($type, $totals['exceptional'], 'exceptional', $hasComparison);

		return $h;

	}

	public function generateTotals(array $resultData): array {

		return [
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

	}
	public function getTable(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYearComparison, \account\FinancialYear $eFinancialYear, array $resultData, \Collection $cAccount, bool $displaySummary): string {

		$hasComparison = $eFinancialYearComparison->notEmpty();

		if($eFinancialYear['endDate'] > date('Y-m-d')) {
			$date = date('Y-m-d');
		} else {
			$date = $eFinancialYear['endDate'];
		}

		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="income-statement-table tr-even overview tr-hover'.($hasComparison ? ' overview_has_previous' : '').'">';

				$h .= '<thead class="thead-sticky">';

					$h .= '<tr class="overview_row_sizing">';
						$h .= '<td colspan="'.($hasComparison ? 5 : 4).'"></td>';
						$h .= '<td colspan="'.($hasComparison ? 5 : 4).'"></td>';
					$h .= '</tr>';

					$h .= '<tr class="overview_row-title">';
						$h .= '<th class="text-center" colspan="'.($hasComparison ? 10 : 8).'">'.s("{farm} - exercice {year}<br />Compte de résultat au {date}", ['farm' => $eFarm['legalName'], 'year' => $eFinancialYear->getLabel(), 'date' => \util\DateUi::numeric($date)]).'</th>';
					$h .= '</tr>';

					$h .= $this->getTHead($eFinancialYear, $eFinancialYearComparison);

				$h .= '</thead>';

				$h .= '<tbody>';

					$totals = $this->generateTotals($resultData);

					$hasComparison = $eFinancialYearComparison->notEmpty();

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
							$h .= '<td class="text-end">'.($differenceCurrent > 0 ? \util\TextUi::money($differenceCurrent, precision: 0) : '').'</td>';
							if($hasComparison) {
								$h .= '<td class="text-end">'.($differencePrevious > 0 ? \util\TextUi::money($differencePrevious) : '').'</td>';
							}
						}
						if($differenceCurrent > 0 and ($hasComparison === FALSE or $differencePrevious > 0)) {
							$h .= '<th colspan="'.($hasComparison ? 4 : 3).'"></th>';
							$h .= '<td></td>';
						} else {
							$h .= '<th colspan="3">'.s("Résultat d'exploitation (perte)").'</th>';
							$h .= '<td class="text-end">'.($differenceCurrent < 0 ? \util\TextUi::money(abs($differenceCurrent), precision: 0) : '').'</td>';
							if($hasComparison) {
								$h .= '<td class="text-end">'.($differencePrevious < 0 ? \util\TextUi::money(abs($differencePrevious), precision: 0) : '').'</td>';
							}
						}

					$h .= '</tr>';

					$h .= '<tr class="overview_group-total tr-bold">';

						$h .= '<th colspan="3">'.s("Total général").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totalExpensesCurrent + ($differenceCurrent > 0 ? $differenceCurrent : 0), precision: 0).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totalExpensesPrevious + ($differencePrevious > 0 ? $differencePrevious : 0), precision: 0).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total général").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totalIncomesCurrent + ($differenceCurrent < 0 ? abs($differenceCurrent) : 0), precision: 0).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totalIncomesPrevious + ($differencePrevious < 0 ? abs($differencePrevious) : 0), precision: 0).'</td>';
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

		$h = '<tr class="overview_group-total tr-bold">';

			$h .= '<th colspan="3">'.$expensesTitle.'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Expense']['current'], precision: 0).'</td>';
			if($hasComparison) {
				$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Expense']['comparison'], precision: 0).'</td>';
			}
			$h .= '<th colspan="3">'.$incomesTitle.'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Income']['current'], precision: 0).'</td>';
			if($hasComparison) {
				$h .= '<td class="text-end">'.\util\TextUi::money($totals[$type.'Income']['comparison'], precision: 0).'</td>';
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
					$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($expense['current'], precision: 0).'</td>';
					if($hasComparison) {
						$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($expense['comparison'], precision: 0).'</td>';
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
					$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($income['current'], precision: 0).'</td>';
					if($hasComparison) {
						$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($income['comparison'], precision: 0).'</td>';
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

	private function displayPdfSubTotal(string $category, array $totals, string $type, bool $hasComparison): string {

		$title = match($category) {
			'expenses' => match($type) {
				'exceptional' => s("Total charges exceptionnelles"),
				'financial' => s("Total charges financières"),
				'operating' => s("Total charges d'exploitation"),
			},
			'incomes' => match($type) {
				'exceptional' => s("Total produits exceptionnels"),
				'financial' => s("Total produits financiers"),
				'operating' => s("Total produits d'exploitation"),
			},
		};

		$h = '<tr class="overview_group-total tr-bold">';

			$h .= '<th colspan="2">'.$title.'</th>';
			$h .= '<td class="text-end">'.\util\TextUi::money($totals['current'], precision: 0).'</td>';
			if($hasComparison) {
				$h .= '<td class="text-end">'.\util\TextUi::money($totals['comparison'], precision: 0).'</td>';
			}

		$h .= '</tr>';

		return $h;

	}
	private function displayPdfSubCategoryLines(\farm\Farm$eFarm, array $data, \Collection $cAccount, bool $hasComparison): string {

		$h = '';

		foreach($data as $line) {

			$h .= '<tr class="overview_line">';

				$style = ($line['isSummary'] ?? FALSE) ? ' style="font-weight: bold";' : '';

				$h .= '<td class="text-end td-min-content"'.$style.'>'.encode($line['class']).'</td>';
				$h .= '<td'.$style.'>';
					if($cAccount->offsetExists($line['class'])) {
						$eAccount = $cAccount->offsetGet($line['class']);
					} else {
						$eAccount = $cAccount->offsetGet(substr($line['class'], 0, 2));
					}
					$h .= encode($eAccount['description']);
				$h .= '</td>';
				$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($line['current'], precision: 0).'</td>';
				if($hasComparison) {
					$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($line['comparison'], precision: 0).'</td>';
				}

			$h .= '</tr>';

		}

		return $h;
	}

}
?>
