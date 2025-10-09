<?php
namespace overview;

class BalanceSheetUi {

	public function __construct() {
		\Asset::css('overview', 'overview.css');
	}

	public function getTitle(): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= s("Bilan");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#balance-sheet-search")').' class="btn btn-primary">'.\Asset::icon('filter').' '.s("Configurer la synthèse").'</a> ';
			$h .= '</div>';

		$h .= '</div>';

		return $h;
	}

	public function getSearch(\Search $search, \Collection $cFinancialYear, \account\FinancialYear $eFinancialYear): string {

		$excludedProperties = ['ids'];
		if($search->get('view') === BalanceSheetLib::VIEW_BASIC) {
			$excludedProperties[] = 'view';
		}

		$h = '<div id="balance-sheet-search" class="util-block-search '.($search->empty($excludedProperties) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->select('view', [
						BalanceSheetLib::VIEW_BASIC => s("Vue synthétique"),
						BalanceSheetLib::VIEW_DETAILED => s("Vue détaillée"),
					], $search->get('view'), ['mandatory' => TRUE]);
					$h .= $form->select('financialYearComparison', $cFinancialYear
						->filter(fn($e) => !$e->is($eFinancialYear))
						->makeArray(function($e, &$key) {
							$key = $e['id'];
							return s("Exercice {value}", \account\FinancialYearUi::getYear($e));
						}), $search->get('financialYearComparison'), ['placeholder' => s("Comparer avec un autre exercice")]);
					$h .= $form->submit(s("Valider"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}


	public function getTable(
		\farm\Farm $eFarm,
		\account\FinancialYear $eFinancialYear,
		\account\FinancialYear $eFinancialYearComparison,
		array $balanceSheetData,
		array $totals,
		\Collection $cAccount,
		bool $hasDetail,
	): string {

		if($eFinancialYear['endDate'] > date('Y-m-d')) {
			$date = date('Y-m-d');
		} else {
			$date = $eFinancialYear['endDate'];
		}

		$hasComparison = $eFinancialYearComparison->notEmpty();

		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="overview tr-hover tr-even'.($hasComparison ? ' overview_has_previous' : '').'">';

				$h .= '<thead class="thead-sticky">';

					$h .= '<tr class="overview_row_sizing">';
						$h .= '<td colspan="'.($hasComparison ? 5 : 4).'"></td>';
						$h .= '<td colspan="'.($hasComparison ? 5 : 4).'"></td>';
					$h .= '</tr>';

					$h .= '<tr class="overview_row-title">';
						$h .= '<th class="text-center" colspan="'.($hasComparison ? 10 : 8).'">'.s("{farm} - exercice {year}<br />Balance au {date}", ['farm' => $eFarm['legalName'], 'year' => \account\FinancialYearUi::getYear($eFinancialYear), 'date' => \util\DateUi::numeric($date)]).'</th>';
					$h .= '</tr>';

					$h .= '<tr class="overview_group-title">';
						$h .= '<th colspan="3">'.s("Actif").'</th>';
						$h .= '<th class="td-min-content">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYear)).'</th>';
						if($hasComparison) {
							$h .= '<th class="td-min-content">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYearComparison)).'</th>';
						}
						$h .= '<th colspan="3">'.s("Passif").'</th>';
						$h .= '<th class="td-min-content">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYear)).'</th>';
						if($hasComparison) {
							$h .= '<th class="td-min-content">'.s("Exercice {value}", \account\FinancialYearUi::getYear($eFinancialYearComparison)).'</th>';
						}
					$h .= '</tr>';

				$h .= '</thead>';

				$h .= '<tbody>';

					$h .= $this->displaySubCategoryLines(eFarm: $eFarm, assets: $balanceSheetData['fixedAssets'], liabilities: $balanceSheetData['equity'],cAccount: $cAccount, hasDetail: $hasDetail, hasComparison: $hasComparison);

					$h .= '<tr class="overview_group-total row-bold">';

						$h .= '<th colspan="3">'.s("Total actif immobilisé").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totals['fixedAssets']['current']).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totals['fixedAssets']['comparison']).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total capitaux propres").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totals['equity']['current']).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totals['equity']['comparison']).'</td>';
						}

					$h .= '</tr>';

					$h .= $this->displaySubCategoryLines(eFarm: $eFarm, assets: $balanceSheetData['currentAssets'],liabilities: $balanceSheetData['debts'], cAccount: $cAccount, hasDetail: $hasDetail, hasComparison: $hasComparison);

					$h .= '<tr class="overview_group-total row-bold">';

						$h .= '<th colspan="3">'.s("Total actif circulant").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totals['currentAssets']['current']).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totals['currentAssets']['comparison']).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total dettes").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totals['debts']['current']).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totals['debts']['comparison']).'</td>';
						}

					$h .= '</tr>';

					$h .= '<tr class="overview_group-total row-bold">';

						$h .= '<th colspan="3">'.s("Total Actif").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totals['fixedAssets']['current'] + $totals['currentAssets']['current']).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totals['fixedAssets']['comparison'] + $totals['currentAssets']['comparison']).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total passif").'</th>';
						$h .= '<td class="text-end">'.\util\TextUi::money($totals['equity']['current'] + $totals['debts']['current']).'</td>';
						if($hasComparison) {
							$h .= '<td class="text-end">'.\util\TextUi::money($totals['equity']['comparison'] + $totals['debts']['comparison']).'</td>';
						}

					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	private function displaySubCategoryLines(\farm\Farm $eFarm, array $assets, array $liabilities, \Collection $cAccount, bool $hasDetail, bool $hasComparison): string {

		$h = '';
		$class = ($hasDetail ? ' overview_cell-summary' : '');

		while(count($assets) > 0 or count($liabilities) > 0) {

			$asset = array_shift($assets);
			$liability = array_shift($liabilities);

			$h .= '<tr class="overview_line">';

			if($asset !== null) {

				if($asset['description'] ?? NULL) {

					$h .= '<td></td>';
					$h .= '<td><i>';
						$h .= '<span class="overview_class-detail">'.encode($asset['class']).'</span> ';
						if($cAccount->offsetExists((int)trim($asset['class'], '0'))) {
							$h .= encode($cAccount[(int)trim($asset['class'], '0')]['description']);
						} else {
							$h .= encode($asset['description']);
						}
					$h .= '</i></td>';
					$h .= '<td>'.new CommonUi()->getDropdownClass($eFarm, $asset['class']).'</td>';
					$h .= '<td class="text-end">'.\util\TextUi::money($asset['current']).'</td>';
					if($hasComparison) {
						$h .= '<td class="text-end">'.\util\TextUi::money($asset['comparison']).'</td>';
					}

				} else {

					$h .= '<td class="text-end td-min-content'.$class.'">'.encode($asset['class']).'</td>';
					$h .= '<td class="'.$class.'">';
						if($cAccount->offsetExists($asset['class'])) {
							$eAccount = $cAccount->offsetGet($asset['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($asset['class'], 0, 2));
						}
						$h .= encode($eAccount['description']);
					$h .= '</td>';
					$h .= '<td>'.new CommonUi()->getDropdownClass($eFarm, $asset['class']).'</td>';
					$h .= '<td class="text-end'.$class.'">'.\util\TextUi::money($asset['current']).'</td>';
					if($hasComparison) {
						$h .= '<td class="text-end'.$class.'">'.\util\TextUi::money($asset['comparison']).'</td>';
					}

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

			if($liability !== null) {

				if($liability['description'] ?? NULL) {
					$h .= '<td></td>';
					$h .= '<td><i>';
						$h .= '<span class="overview_class-detail">'.encode($liability['class']).'</span> ';
						if($cAccount->offsetExists((int)trim($liability['class'], '0'))) {
							$h .= encode($cAccount[(int)trim($liability['class'], '0')]['description']);
						} else {
							$h .= encode($liability['description']);
						}
					$h .= '</i></td>';
					$h .= '<td>'.new CommonUi()->getDropdownClass($eFarm, $liability['class']).'</td>';
					$h .= '<td class="text-end">'.\util\TextUi::money($liability['current']).'</td>';
					if($hasComparison) {
						$h .= '<td class="text-end">'.\util\TextUi::money($liability['comparison']).'</td>';
					}

				} else {

					$h .= '<td class="text-end td-min-content'.$class.'">'.encode($liability['class']).'</td>';
					$h .= '<td class="'.$class.'">';

						if($cAccount->offsetExists($liability['class'])) {
							$eAccount = $cAccount->offsetGet($liability['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($liability['class'], 0, 2));
						}
						$h .= encode($eAccount['description']);

					$h .= '</td>';
					$h .= '<td>'.new CommonUi()->getDropdownClass($eFarm, $liability['class']).'</td>';
					$h .= '<td class="text-end'.$class.'">'.\util\TextUi::money($liability['current']).'</td>';
					if($hasComparison) {
						$h .= '<td class="text-end'.$class.'">'.\util\TextUi::money($liability['comparison']).'</td>';
					}

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
