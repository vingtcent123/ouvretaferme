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
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#income-statement-search")').' class="btn btn-primary">'.\Asset::icon('filter').' '.s("Configurer la syntèse").'</a> ';
			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

	public function getSearch(\Search $search, \account\FinancialYear $eFinancialYear): string {

		$h = '<div id="income-statement-search" class="util-block-search '.($search->empty(['ids']) === TRUE ? 'hide' : '').'">';

		$form = new \util\FormUi();
		$url = LIME_REQUEST_PATH;

		$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

		$h .= '<div>';
			$h .= $form->checkbox('summary', 1, ['checked' => $search->get('summary'), 'callbackLabel' => fn($input) => $input.' '.s("Afficher la synthèse par classe de compte (sur 2 chiffres)")]);
			$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
			$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
		$h .= '</div>';

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}


	public function getTable(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYearPrevious, \account\FinancialYear $eFinancialYear, array $resultData, \Collection $cAccount, bool $displaySummary): string {

		$hasPrevious = $eFinancialYearPrevious->notEmpty();

		$totals = [
			'operatingExpense' => [
				'current' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['current']), $resultData['expenses']['operating'])),
				'previous' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['previous']), $resultData['expenses']['operating'])),
			],
			'financialExpense' => [
				'current' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['current']), $resultData['expenses']['financial'])),
				'previous' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['previous']), $resultData['expenses']['financial'])),
			],
			'exceptionalExpense' => [
				'current' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['current']), $resultData['expenses']['exceptional'])),
				'previous' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['previous']), $resultData['expenses']['exceptional'])),
			],
			'operatingIncome' => [
				'current' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['current']), $resultData['incomes']['operating'])),
				'previous' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['previous']), $resultData['incomes']['operating'])),
			],
			'financialIncome' => [
				'current' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['current']), $resultData['incomes']['financial'])),
				'previous' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['previous']), $resultData['incomes']['financial'])),
			],
			'exceptionalIncome' => [
				'current' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['current']), $resultData['incomes']['exceptional'])),
				'previous' => array_sum(array_map(fn($data) => (($displaySummary and ($data['isSummary'] ?? FALSE)) ? 0 : $data['previous']), $resultData['incomes']['exceptional'])),
			],
		];

		$h = '<div class="util-overflow-md stick-xs">';

		if($eFinancialYear['endDate'] > date('Y-m-d')) {
			$date = date('Y-m-d');
		} else {
			$date = $eFinancialYear['endDate'];
		}

		$h .= '<table class="tr-even overview_income-statement tr-hover'.($hasPrevious ? ' overview_income-statement_has_previous' : '').'">';

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

			$h .= $this->displaySubCategoryLines($resultData['expenses']['operating'], $resultData['incomes']['operating'], $cAccount, $hasPrevious);

			$h .= $this->displaySubTotal($totals, 'operating', $hasPrevious);

			$h .= $this->displaySubCategoryLines($resultData['expenses']['financial'], $resultData['incomes']['financial'], $cAccount, $hasPrevious);

			$h .= $this->displaySubTotal($totals, 'financial', $hasPrevious);

			$h .= $this->displaySubCategoryLines($resultData['expenses']['exceptional'], $resultData['incomes']['exceptional'], $cAccount, $hasPrevious);

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

				if($differenceCurrent < 0 and ($hasPrevious === FALSE or $differencePrevious < 0)) {
					$h .= '<th colspan="'.($hasPrevious ? 3 : 2).'"></th>';
					$h .= '<td></td>';
				} else {

					$h .= '<th colspan="2">'.s("Résultat d'exploitation (bénéfice)").'</th>';
					$h .= '<td class="text-end">'.($differenceCurrent > 0 ? \util\TextUi::money($differenceCurrent) : '').'</td>';
					if($hasPrevious) {
						$h .= '<td class="text-end">'.($differencePrevious > 0 ? \util\TextUi::money($differencePrevious) : '').'</td>';
					}
				}
				if($differenceCurrent > 0 and ($hasPrevious === FALSE or $differencePrevious > 0)) {
					$h .= '<th colspan="'.($hasPrevious ? 3 : 2).'"></th>';
					$h .= '<td></td>';
				} else {
					$h .= '<th colspan="2">'.s("Résultat d'exploitation (perte)").'</th>';
					$h .= '<td class="text-end">'.($differenceCurrent < 0 ? \util\TextUi::money(abs($differenceCurrent)) : '').'</td>';
					if($hasPrevious) {
						$h .= '<td class="text-end">'.($differencePrevious < 0 ? \util\TextUi::money(abs($differencePrevious)) : '').'</td>';
					}
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
					$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($expense['current']).'</td>';
					if($hasPrevious) {
						$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($expense['previous']).'</td>';
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
					$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($income['current']).'</td>';
					if($hasPrevious) {
						$h .= '<td class="text-end"'.$style.'>'.\util\TextUi::money($income['previous']).'</td>';
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
