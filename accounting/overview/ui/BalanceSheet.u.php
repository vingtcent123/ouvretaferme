<?php
namespace overview;

class BalanceSheetUi {

	public function __construct() {
		\Asset::css('overview', 'balance.css');
	}

	public function getSearch(\Search $search, \Collection $cFinancialYear, \account\FinancialYear $eFinancialYear): string {

		$excludedProperties = ['ids'];
		if($search->get('type') === BalanceSheetLib::VIEW_BASIC) {
			$excludedProperties[] = 'type';
		}

		$h = '<div id="balance-sheet-search" class="util-block-search '.($search->empty($excludedProperties) === TRUE ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';
					$h .= $form->select('type', [
						BalanceSheetLib::VIEW_BASIC => s("Vue synthétique"),
						BalanceSheetLib::VIEW_DETAILED => s("Vue détaillée"),
					], $search->get('type'), ['mandatory' => TRUE]);

					if($cFinancialYear->count() > 1) {
						$h .= $form->select('financialYearComparison', $cFinancialYear
							->filter(fn($e) => !$e->is($eFinancialYear))
							->makeArray(function($e, &$key) {
								$key = $e['id'];
								return s("Exercice {value}", $e->getLabel());
							}), $search->get('financialYearComparison'), ['placeholder' => s("Comparer avec un autre exercice")]);
					}
				$h .= '</div>';
				$h .= '<div class="mb-1">';
					$h .= $form->checkbox('netOnly', 1, ['checked' => GET('netOnly'), 'callbackLabel' => fn($input) => $input.' '.s("Afficher uniquement le net")]);
				$h .= '</div>';
				$h .= '<div class="mb-1">';
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
		$netOnly = GET('netOnly', 'bool', FALSE);

		$h = '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="balance-table tr-hover tr-even'.($hasComparison ? ' overview_has_previous' : '').'">';

				$h .= '<thead class="thead-sticky">';

					$h .= '<tr class="overview_row_sizing">';
						$h .= '<td colspan="'.($hasComparison ? 9 - ($netOnly ? 4 : 0) : 6 - ($netOnly ? 2 : 0)).'"></td>';
						$h .= '<td colspan="'.($hasComparison ? 9 - ($netOnly ? 4 : 0) : 6 - ($netOnly ? 2 : 0)).'"></td>';
					$h .= '</tr>';

					$h .= '<tr class="overview_row-title">';
						$h .= '<th class="text-center" colspan="'.($hasComparison ? 18 - ($netOnly ? 4 : 0) : 12 - ($netOnly ? 2 : 0)).'">'.s("{farm} - exercice {year}<br />Bilan au {date}", ['farm' => $eFarm['legalName'], 'year' => $eFinancialYear->getLabel(), 'date' => \util\DateUi::numeric($date)]).'</th>';
					$h .= '</tr>';

					$h .= '<tr class="overview_group-title">';
						$h .= '<th colspan="3" rowspan="2">'.s("Actif").'</th>';
						$h .= '<th class="td-min-content text-center" '.($netOnly ? '' : 'colspan="3"').'>'.s("Exercice {value}", $eFinancialYear->getLabel()).'</th>';
						if($hasComparison) {
							$h .= '<th class="td-min-content text-center" '.($netOnly ? '' : 'colspan="3"').'>'.s("Exercice {value}", $eFinancialYearComparison->getLabel()).'</th>';
						}
						$h .= '<th colspan="3" rowspan="2">'.s("Passif").'</th>';
						$h .= '<th class="td-min-content text-center" '.($netOnly ? '' : 'colspan="3"').'>'.s("Exercice {value}", $eFinancialYear->getLabel()).'</th>';
						if($hasComparison) {
							$h .= '<th class="td-min-content text-center" '.($netOnly ? '' : 'colspan="3"').'>'.s("Exercice {value}", $eFinancialYearComparison->getLabel()).'</th>';
						}
					$h .= '</tr>';

					if($netOnly === FALSE) {
						$h .= '<tr class="overview_group-title">';
							$h .= '<th class="td-min-content">'.s("Brut").'</th>';
							$h .= '<th class="td-min-content">'.s("Amort. Prov.").'</th>';
							$h .= '<th class="td-min-content">'.s("Net").'</th>';
							if($hasComparison) {
								$h .= '<th class="td-min-content">'.s("Brut").'</th>';
								$h .= '<th class="td-min-content">'.s("Amort. Prov.").'</th>';
								$h .= '<th class="td-min-content">'.s("Net").'</th>';
							}
							$h .= '<th class="td-min-content">'.s("Brut").'</th>';
							$h .= '<th class="td-min-content">'.s("Amort. Prov.").'</th>';
							$h .= '<th class="td-min-content">'.s("Net").'</th>';
							if($hasComparison) {
								$h .= '<th class="td-min-content">'.s("Brut").'</th>';
								$h .= '<th class="td-min-content">'.s("Amort. Prov.").'</th>';
								$h .= '<th class="td-min-content">'.s("Net").'</th>';
							}
						$h .= '</tr>';
					}

				$h .= '</thead>';

				$h .= '<tbody>';

					$h .= $this->displaySubCategoryLines(eFarm: $eFarm, assets: $balanceSheetData['fixedAssets'], liabilities: $balanceSheetData['equity'],cAccount: $cAccount, hasDetail: $hasDetail, hasComparison: $hasComparison);

					$h .= '<tr class="overview_group-total row-bold">';

						$h .= '<th colspan="3">'.s("Total actif immobilisé").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['fixedAssets']['currentBrut']).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['fixedAssets']['currentDepreciation'], 1) !== 0.0) {
								$h .= \util\TextUi::money($totals['fixedAssets']['currentDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['fixedAssets']['currentNet']).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['fixedAssets']['comparisonBrut']).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['fixedAssets']['comparisonDepreciation'], 1) !== 0.0) {
									$h .= \util\TextUi::money($totals['fixedAssets']['comparisonDepreciation']);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['fixedAssets']['comparisonNet']).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total capitaux propres").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['equity']['currentBrut']).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['equity']['currentDepreciation'], 1) !== 0.0) {
								$h .= \util\TextUi::money($totals['equity']['currentDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['equity']['currentNet']).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['equity']['comparisonBrut']).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['equity']['comparisonDepreciation'], 1) !== 0.0) {
									$h .= \util\TextUi::money($totals['equity']['comparisonDepreciation']);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['equity']['comparisonNet']).'</td>';
						}

					$h .= '</tr>';

					$h .= $this->displaySubCategoryLines(eFarm: $eFarm, assets: $balanceSheetData['currentAssets'],liabilities: $balanceSheetData['debts'], cAccount: $cAccount, hasDetail: $hasDetail, hasComparison: $hasComparison);

					$h .= '<tr class="overview_group-total row-bold">';

						$h .= '<th colspan="3">'.s("Total actif circulant").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['currentAssets']['currentBrut']).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['currentAssets']['currentDepreciation'], 1) !== 0.0) {
								$h .= \util\TextUi::money($totals['currentAssets']['currentDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['currentAssets']['currentNet']).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['currentAssets']['comparisonBrut']).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['currentAssets']['comparisonDepreciation'], 1) !== 0.0) {
									$h .= \util\TextUi::money($totals['currentAssets']['comparisonDepreciation']);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['currentAssets']['comparisonNet']).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total dettes").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['debts']['currentBrut']).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['debts']['currentDepreciation'], 1) !== 0.0) {
								$h .= \util\TextUi::money($totals['debts']['currentDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['debts']['currentNet']).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['debts']['comparisonBrut']).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['debts']['comparisonDepreciation'], 1) !== 0.0) {
									$h .= \util\TextUi::money($totals['debts']['comparisonDepreciation']);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['debts']['comparisonNet']).'</td>';
						}

					$h .= '</tr>';

					$h .= '<tr class="overview_group-total row-bold">';

						$h .= '<th colspan="3">'.s("Total Actif").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['fixedAssets']['currentBrut'] + $totals['currentAssets']['currentBrut']).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['fixedAssets']['currentDepreciation'] + $totals['currentAssets']['currentDepreciation'], 1) !== 0.0) {
								$h .= \util\TextUi::money($totals['fixedAssets']['currentDepreciation'] + $totals['currentAssets']['currentDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['fixedAssets']['currentNet'] + $totals['currentAssets']['currentNet']).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['fixedAssets']['comparisonBrut'] + $totals['currentAssets']['comparisonBrut']).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['fixedAssets']['comparisonDepreciation'] + $totals['currentAssets']['comparisonDepreciation'], 1) !== 0.0) {
									$h .= \util\TextUi::money($totals['fixedAssets']['comparisonDepreciation'] + $totals['currentAssets']['comparisonDepreciation']);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['fixedAssets']['comparisonNet'] + $totals['currentAssets']['comparisonNet']).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total passif").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['equity']['currentBrut'] + $totals['debts']['currentBrut']).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['equity']['currentDepreciation'] + $totals['debts']['currentDepreciation'], 1) !== 0.0) {
								$h .= \util\TextUi::money($totals['equity']['currentDepreciation'] + $totals['debts']['currentDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['equity']['currentNet'] + $totals['debts']['currentNet']).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['equity']['comparisonBrut'] + $totals['debts']['comparisonBrut']).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['equity']['comparisonDepreciation'] + $totals['debts']['comparisonDepreciation'], 1) !== 0.0) {
									$h .= \util\TextUi::money($totals['equity']['comparisonDepreciation'] + $totals['debts']['comparisonDepreciation']);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['equity']['comparisonNet'] + $totals['debts']['comparisonNet']).'</td>';
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

		$netOnly = GET('netOnly', 'bool', FALSE);

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
					if($netOnly === FALSE) {
						$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($asset['currentBrut']).'</td>';
						$h .= '<td class="text-end balance-td-amortization">';
						if(round($asset['currentDepreciation']) !== 0.0) {
							$h .= \util\TextUi::money($asset['currentDepreciation']);
						}
						$h .= '</td>';
					}
					$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($asset['currentNet']).'</td>';
					if($hasComparison) {
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($asset['comparisonBrut']).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($asset['comparisonDepreciation']) !== 0.0) {
								$h .= \util\TextUi::money($asset['comparisonDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($asset['comparisonNet']).'</td>';
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
					if($netOnly === FALSE) {
						$h .= '<td class="text-end'.$class.' balance-td-brut">'.\util\TextUi::money($asset['currentBrut']).'</td>';
						$h .= '<td class="text-end'.$class.' balance-td-amortization">';
						if(round($asset['currentDepreciation']) !== 0.0) {
							$h .= \util\TextUi::money($asset['currentDepreciation']);
						}
						$h .= '</td>';
					}
					$h .= '<td class="text-end'.$class.' balance-td-net">'.\util\TextUi::money($asset['currentNet']).'</td>';
					if($hasComparison) {

						if($netOnly === FALSE) {
							$h .= '<td class="text-end'.$class.' balance-td-brut">'.\util\TextUi::money($asset['comparisonBrut']).'</td>';
							$h .= '<td class="text-end'.$class.' balance-td-amortization">';
							if(round($asset['comparisonDepreciation']) !== 0.0) {
								$h .= \util\TextUi::money($asset['comparisonDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end'.$class.' balance-td-net">'.\util\TextUi::money($asset['comparisonNet']).'</td>';
					}

				}

			} else {

				$h .= '<td></td>';
				$h .= '<td></td>';
				$h .= '<td></td>';
				if($netOnly === FALSE) {
					$h .= '<td class="balance-td-brut"></td>';
					$h .= '<td class="balance-td-amortization"></td>';
				}
				$h .= '<td class="balance-td-net"></td>';
				if($hasComparison) {
					if($netOnly === FALSE) {
						$h .= '<td class="balance-td-brut"></td>';
						$h .= '<td class="balance-td-amortization"></td>';
					}
					$h .= '<td class="balance-td-net"></td>';
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
					if($netOnly === FALSE) {
						$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($liability['currentBrut']).'</td>';
						$h .= '<td class="text-end balance-td-amortization">';
						if(round($liability['currentDepreciation']) !== 0.0) {
							$h .= \util\TextUi::money($liability['currentDepreciation']);
						}
						$h .= '</td>';
					}
					$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($liability['currentNet']).'</td>';
					if($hasComparison) {
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($liability['comparisonBrut']).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($liability['comparisonDepreciation']) !== 0.0) {
								$h .= \util\TextUi::money($liability['comparisonDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($liability['comparisonNet']).'</td>';
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
					if($netOnly === FALSE) {
						$h .= '<td class="text-end'.$class.' balance-td-brut">'.\util\TextUi::money($liability['currentBrut']).'</td>';
						$h .= '<td class="text-end'.$class.' balance-td-amortization">';
						if(round($liability['currentDepreciation']) !== 0.0) {
							$h .= \util\TextUi::money($liability['currentDepreciation']);
						}
						$h .= '</td>';
					}
					$h .= '<td class="text-end'.$class.' balance-td-net">'.\util\TextUi::money($liability['currentNet']).'</td>';
					if($hasComparison) {
						if($netOnly === FALSE) {
							$h .= '<td class="text-end'.$class.' balance-td-brut">'.\util\TextUi::money($liability['comparisonBrut']).'</td>';
							$h .= '<td class="text-end'.$class.' balance-td-amortization">';
							if(round($liability['comparisonDepreciation']) !== 0.0) {
								$h .= \util\TextUi::money($liability['comparisonDepreciation']);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end'.$class.' balance-td-net">'.\util\TextUi::money($liability['comparisonNet']).'</td>';
					}

				}

			} else {
				$h .= '<td></td>';
				$h .= '<td></td>';
				$h .= '<td></td>';
				if($netOnly === FALSE) {
					$h .= '<td class="balance-td-brut"></td>';
					$h .= '<td class="balance-td-amortization"></td>';
				}
				$h .= '<td class="balance-td-net"></td>';
				if($hasComparison) {
					if($netOnly === FALSE) {
						$h .= '<td class="balance-td-brut"></td>';
						$h .= '<td class="balance-td-amortization"></td>';
					}
					$h .= '<td class="balance-td-net"></td>';
				}
			}

			$h .= '</tr>';

		}

		return $h;
	}

}
?>
