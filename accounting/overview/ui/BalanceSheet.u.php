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

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Vue").'</legend>';
					$h .= $form->select('type', [
						BalanceSheetLib::VIEW_BASIC => s("Vue synthétique"),
						BalanceSheetLib::VIEW_DETAILED => s("Vue détaillée"),
					], $search->get('type'), ['mandatory' => TRUE]);
				$h .= '</fieldset>';
				if($cFinancialYear->count() > 1) {
					$h .= '<fieldset>';
						$h .= '<legend>'.s("Comparer avec un autre exercice").'</legend>';
						$h .= $form->select('financialYearComparison', $cFinancialYear
							->filter(fn($e) => !$e->is($eFinancialYear))
							->makeArray(function($e, &$key) {
								$key = $e['id'];
								return s("Exercice {value}", $e->getLabel());
							}), $search->get('financialYearComparison'), ['placeholder' => s("Comparer avec un autre exercice")]);
					$h .= '</fieldset>';
				}
				$h .= '<fieldset>';
					$h .= '<legend>'.s("Afficher uniquement le net").'</legend>';
					$h .= $form->select('netOnly', [
						0 => s("Non"),
						1 => s("Oui"),
					], (int)$search->get('netOnly'), ['mandatory' => TRUE]);
				$h .= '</fieldset>';

				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Valider"));
					$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
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

					$h .= '<tr class="overview_group-total tr-bold">';

						$h .= '<th colspan="3">'.s("Total actif immobilisé").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['fixedAssets']['currentBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['fixedAssets']['currentDepreciation'], 1)!== 0) {
								$h .= \util\TextUi::money($totals['fixedAssets']['currentDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['fixedAssets']['currentNet'], precision: 0).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['fixedAssets']['comparisonBrut'], precision: 0).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['fixedAssets']['comparisonDepreciation'], 1)!== 0) {
									$h .= \util\TextUi::money($totals['fixedAssets']['comparisonDepreciation'], precision: 0);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['fixedAssets']['comparisonNet'], precision: 0).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total capitaux propres").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['equity']['currentBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['equity']['currentDepreciation'], 1)!== 0) {
								$h .= \util\TextUi::money($totals['equity']['currentDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['equity']['currentNet'], precision: 0).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['equity']['comparisonBrut'], precision: 0).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['equity']['comparisonDepreciation'], 1)!== 0) {
									$h .= \util\TextUi::money($totals['equity']['comparisonDepreciation'], precision: 0);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['equity']['comparisonNet'], precision: 0).'</td>';
						}

					$h .= '</tr>';

					$h .= $this->displaySubCategoryLines(eFarm: $eFarm, assets: $balanceSheetData['currentAssets'],liabilities: $balanceSheetData['debts'], cAccount: $cAccount, hasDetail: $hasDetail, hasComparison: $hasComparison);

					$h .= '<tr class="overview_group-total tr-bold">';

						$h .= '<th colspan="3">'.s("Total actif circulant").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['currentAssets']['currentBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['currentAssets']['currentDepreciation'], 1)!== 0) {
								$h .= \util\TextUi::money($totals['currentAssets']['currentDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['currentAssets']['currentNet'], precision: 0).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['currentAssets']['comparisonBrut'], precision: 0).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['currentAssets']['comparisonDepreciation'], 1)!== 0) {
									$h .= \util\TextUi::money($totals['currentAssets']['comparisonDepreciation'], precision: 0);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['currentAssets']['comparisonNet'], precision: 0).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total dettes").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['debts']['currentBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['debts']['currentDepreciation'], 1)!== 0) {
								$h .= \util\TextUi::money($totals['debts']['currentDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['debts']['currentNet'], precision: 0).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['debts']['comparisonBrut'], precision: 0).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['debts']['comparisonDepreciation'], 1)!== 0) {
									$h .= \util\TextUi::money($totals['debts']['comparisonDepreciation'], precision: 0);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['debts']['comparisonNet'], precision: 0).'</td>';
						}

					$h .= '</tr>';

					$h .= '<tr class="overview_group-total tr-bold">';

						$h .= '<th colspan="3">'.s("Total Actif").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['fixedAssets']['currentBrut'] + $totals['currentAssets']['currentBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['fixedAssets']['currentDepreciation'] + $totals['currentAssets']['currentDepreciation'], 1)!== 0) {
								$h .= \util\TextUi::money($totals['fixedAssets']['currentDepreciation'] + $totals['currentAssets']['currentDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['fixedAssets']['currentNet'] + $totals['currentAssets']['currentNet'], precision: 0).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['fixedAssets']['comparisonBrut'] + $totals['currentAssets']['comparisonBrut'], precision: 0).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['fixedAssets']['comparisonDepreciation'] + $totals['currentAssets']['comparisonDepreciation'], 1)!== 0) {
									$h .= \util\TextUi::money($totals['fixedAssets']['comparisonDepreciation'] + $totals['currentAssets']['comparisonDepreciation'], precision: 0);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['fixedAssets']['comparisonNet'] + $totals['currentAssets']['comparisonNet'], precision: 0).'</td>';
						}
						$h .= '<th colspan="3">'.s("Total passif").'</th>';
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['equity']['currentBrut'] + $totals['debts']['currentBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($totals['equity']['currentDepreciation'] + $totals['debts']['currentDepreciation'], 1)!== 0) {
								$h .= \util\TextUi::money($totals['equity']['currentDepreciation'] + $totals['debts']['currentDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['equity']['currentNet'] + $totals['debts']['currentNet'], precision: 0).'</td>';
						if($hasComparison) {
							if($netOnly === FALSE) {
								$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['equity']['comparisonBrut'] + $totals['debts']['comparisonBrut'], precision: 0).'</td>';
								$h .= '<td class="text-end balance-td-amortization">';
								if(round($totals['equity']['comparisonDepreciation'] + $totals['debts']['comparisonDepreciation'], 1)!== 0) {
									$h .= \util\TextUi::money($totals['equity']['comparisonDepreciation'] + $totals['debts']['comparisonDepreciation'], precision: 0);
								}
								$h .= '</td>';
							}
							$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['equity']['comparisonNet'] + $totals['debts']['comparisonNet'], precision: 0).'</td>';
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
							$h .= encode(ucfirst(mb_strtolower($asset['description'], 'UTF-8')));
						}
					$h .= '</i></td>';
					$h .= '<td>'.new CommonUi()->getDropdownClass($eFarm, $asset['class']).'</td>';
					if($netOnly === FALSE) {
						$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($asset['currentBrut'], precision: 0).'</td>';
						$h .= '<td class="text-end balance-td-amortization">';
						if(round($asset['currentDepreciation'])!== 0) {
							$h .= \util\TextUi::money($asset['currentDepreciation'], precision: 0);
						}
						$h .= '</td>';
					}
					$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($asset['currentNet'], precision: 0).'</td>';
					if($hasComparison) {
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($asset['comparisonBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($asset['comparisonDepreciation'])!== 0) {
								$h .= \util\TextUi::money($asset['comparisonDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($asset['comparisonNet'], precision: 0).'</td>';
					}

				} else {

					$h .= '<td class="text-end td-min-content'.$class.'">'.encode($asset['class']).'</td>';
					$h .= '<td class="'.$class.'">';
						if($cAccount->offsetExists($asset['class'])) {
							$eAccount = $cAccount->offsetGet($asset['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($asset['class'], 0, 2));
						}
						$h .= encode(ucfirst(mb_strtolower($eAccount['description'], 'UTF-8')));
					$h .= '</td>';
					$h .= '<td>'.new CommonUi()->getDropdownClass($eFarm, $asset['class']).'</td>';
					if($netOnly === FALSE) {
						$h .= '<td class="text-end'.$class.' balance-td-brut">'.\util\TextUi::money($asset['currentBrut'], precision: 0).'</td>';
						$h .= '<td class="text-end'.$class.' balance-td-amortization">';
						if(round($asset['currentDepreciation'])!== 0) {
							$h .= \util\TextUi::money($asset['currentDepreciation'], precision: 0);
						}
						$h .= '</td>';
					}
					$h .= '<td class="text-end'.$class.' balance-td-net">'.\util\TextUi::money($asset['currentNet'], precision: 0).'</td>';
					if($hasComparison) {

						if($netOnly === FALSE) {
							$h .= '<td class="text-end'.$class.' balance-td-brut">'.\util\TextUi::money($asset['comparisonBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end'.$class.' balance-td-amortization">';
							if(round($asset['comparisonDepreciation'])!== 0) {
								$h .= \util\TextUi::money($asset['comparisonDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end'.$class.' balance-td-net">'.\util\TextUi::money($asset['comparisonNet'], precision: 0).'</td>';
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
							$h .= encode(ucfirst(mb_strtolower($cAccount[(int)trim($liability['class'], '0')]['description'], 'UTF-8')));
						} else {
							$h .= encode(ucfirst(mb_strtolower($liability['description'], 'UTF-8')));
						}
					$h .= '</i></td>';
					$h .= '<td>'.new CommonUi()->getDropdownClass($eFarm, $liability['class']).'</td>';
					if($netOnly === FALSE) {
						$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($liability['currentBrut'], precision: 0).'</td>';
						$h .= '<td class="text-end balance-td-amortization">';
						if(round($liability['currentDepreciation'])!== 0) {
							$h .= \util\TextUi::money($liability['currentDepreciation'], precision: 0);
						}
						$h .= '</td>';
					}
					$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($liability['currentNet'], precision: 0).'</td>';
					if($hasComparison) {
						if($netOnly === FALSE) {
							$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($liability['comparisonBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end balance-td-amortization">';
							if(round($liability['comparisonDepreciation'])!== 0) {
								$h .= \util\TextUi::money($liability['comparisonDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($liability['comparisonNet'], precision: 0).'</td>';
					}

				} else {

					$h .= '<td class="text-end td-min-content'.$class.'">'.encode($liability['class']).'</td>';
					$h .= '<td class="'.$class.'">';

						if($cAccount->offsetExists($liability['class'])) {
							$eAccount = $cAccount->offsetGet($liability['class']);
						} else {
							$eAccount = $cAccount->offsetGet(substr($liability['class'], 0, 2));
						}
						$h .= encode(ucfirst(mb_strtolower($eAccount['description'], 'UTF-8')));

					$h .= '</td>';
					$h .= '<td>'.new CommonUi()->getDropdownClass($eFarm, $liability['class']).'</td>';
					if($netOnly === FALSE) {
						$h .= '<td class="text-end'.$class.' balance-td-brut">'.\util\TextUi::money($liability['currentBrut'], precision: 0).'</td>';
						$h .= '<td class="text-end'.$class.' balance-td-amortization">';
						if(round($liability['currentDepreciation'])!== 0) {
							$h .= \util\TextUi::money($liability['currentDepreciation'], precision: 0);
						}
						$h .= '</td>';
					}
					$h .= '<td class="text-end'.$class.' balance-td-net">'.\util\TextUi::money($liability['currentNet'], precision: 0).'</td>';
					if($hasComparison) {
						if($netOnly === FALSE) {
							$h .= '<td class="text-end'.$class.' balance-td-brut">'.\util\TextUi::money($liability['comparisonBrut'], precision: 0).'</td>';
							$h .= '<td class="text-end'.$class.' balance-td-amortization">';
							if(round($liability['comparisonDepreciation'])!== 0) {
								$h .= \util\TextUi::money($liability['comparisonDepreciation'], precision: 0);
							}
							$h .= '</td>';
						}
						$h .= '<td class="text-end'.$class.' balance-td-net">'.\util\TextUi::money($liability['comparisonNet'], precision: 0).'</td>';
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

	public function getPdfTHead(string $type): string {

		if($type === 'assets') {

			$h = '<tr class="pdf-tr-title">';
				$h .= '<th>'.s("Actif").'</th>';
				$h .= '<th class="text-center">'.s("Brut").'</th>';
				$h .= '<th class="text-center">'.s("Amort. prov.").'</th>';
				$h .= '<th class="text-center">'.s("Net").'</th>';
				$h .= '<th class="text-center">'.s("% actif").'</th>';
				$h .= '<th class="text-center">'.s("N-1").'</th>';
			$h .= '</tr>';

			return $h;

		}

		$h = '<tr class="pdf-tr-title">';
			$h .= '<th>'.s("Passif").'</th>';
			$h .= '<th class="text-center">'.s("N").'</th>';
			$h .= '<th class="text-center">'.s("% passif").'</th>';
			$h .= '<th class="text-center">'.s("N-1").'</th>';
		$h .= '</tr>';

		return $h;
	}

	public function getPdfTBodyAssets(string $type, array $balanceSheetData, array $totals, \Collection $cAccount, bool $isDetailed): string {

		$h = '';

		if($type === 'assets') {
			$totalAsset = 0;
			$totalAssetComparison = 0;
			foreach($balanceSheetData['fixedAssets'] + $balanceSheetData['currentAssets'] as $class => $data) {
				if(mb_strlen($class) === 3) {
					$totalAsset += $data['currentNet'];
					$totalAssetComparison += $data['comparisonNet'];
				}
			}

			$h .= $this->displaySubCategoryPdfLinesAssets('fixedAssets', $balanceSheetData['fixedAssets'], $cAccount, $totalAsset);
			$h .= $this->displaySubCategoryPdfLinesAssets('currentAssets', $balanceSheetData['currentAssets'], $cAccount, $totalAsset);

			$h .= '<tr class="overview_line tr-bold pdf-tr-title pdf-tr-total-general">';

				$h .= '<td>';
					$h .= s("Total de l'actif");
				$h .= '</td>';

				$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($totals['fixedAssets']['currentBrut'] + $totals['currentAssets']['currentBrut'], precision: 0).'</td>';
				$h .= '<td class="text-end balance-td-amortization">';
				if(round($totals['fixedAssets']['currentDepreciation'] + $totals['currentAssets']['currentDepreciation'])!== 0) {
					$h .= \util\TextUi::money($totals['fixedAssets']['currentDepreciation'] + $totals['currentAssets']['currentDepreciation'], precision: 0);
				}
				$h .= '</td>';
				$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['fixedAssets']['currentNet'] + $totals['currentAssets']['currentNet'], precision: 0).'</td>';
				$h .= '<td class="text-end balance-td-net">';
					$percentValue = (($totals['fixedAssets']['currentNet'] + $totals['currentAssets']['currentNet']) / $totalAsset);
					if((int)$percentValue === 0) {
						$h .= '';
					} else {
						$h .= $this->getPercent(($totals['fixedAssets']['currentNet'] + $totals['currentAssets']['currentNet']) / $totalAsset);
					}
				$h .= '</td>';
				$h .= '<td class="text-end balance-td-net">';
				 $h .= (($totals['fixedAssets']['comparisonNet'] + $totals['currentAssets']['comparisonNet']) === 0 ? '' : \util\TextUi::money($totals['fixedAssets']['comparisonNet'] + $totals['currentAssets']['comparisonNet'], precision: 0));
				$h .= '</td>';

			$h .= '</tr>';

		} else {

			$totalAsset = array_sum(array_column($balanceSheetData['equity'], 'currentNet')) +
				array_sum(array_column($balanceSheetData['debts'], 'currentNet'));

			$h .= $this->displaySubCategoryPdfLinesLiabilities('equity', $balanceSheetData['equity'], $cAccount, $totalAsset);
			$h .= $this->displaySubCategoryPdfLinesLiabilities('debts', $balanceSheetData['debts'], $cAccount, $totalAsset);

			$h .= '<tr class="overview_line tr-bold pdf-tr-title pdf-tr-total-general">';

				$h .= '<td>';
					$h .= s("Total du passif");
				$h .= '</td>';

				$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($totals['equity']['currentNet'] + $totals['debts']['currentNet'], precision: 0).'</td>';
				$h .= '<td class="text-end balance-td-net">';
					$percentValue = (($totals['equity']['currentNet'] + $totals['debts']['currentNet']) / $totalAsset);
					if((int)$percentValue === 0) {
						$h .= '';
					} else {
						$h .= $this->getPercent(($totals['equity']['currentNet'] + $totals['debts']['currentNet']) / $totalAsset);
					}
				$h .= '</td>';
				$h .= '<td class="text-end balance-td-net">'.(($totals['equity']['comparisonNet'] + $totals['debts']['comparisonNet']) === 0 ? '' : \util\TextUi::money($totals['equity']['comparisonNet'] + $totals['debts']['comparisonNet'], precision: 0)).'</td>';

			$h .= '</tr>';


		}

		return $h;

	}

	private function getPercent(float $value): string {

		$percent = round($value * 100);

		if($percent < 1) {
			return '';
		}

		return s("{rate}%", ['rate' => $percent]);

	}

	private function displaySubCategoryPdfLinesAssets(string $type, array $category, \Collection $cAccount, float $total): string {

		$h = '';
		$lastClass = NULL;

		$cumulation = ['currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0, 'comparisonNet' => 0];
		$categoryCumulation = ['currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0, 'comparisonNet' => 0];

		foreach($category as $class => $line) {

			// Le jeu des arrondis fait qu'on aura une ligne vide
			if(
				(int)$line['comparisonBrut'] === 0 and (int)$line['comparisonDepreciation'] === 0 and (int)$line['comparisonNet'] === 0 and
				(int)$line['currentBrut'] === 0 and (int)$line['currentDepreciation'] === 0 and (int)$line['currentNet'] === 0
			) {
				continue;
			}

			if($lastClass !== NULL and mb_substr($line['class'], 0, 2) !== mb_substr($lastClass, 0, 2)) {

				$eAccount = $cAccount->offsetGet(mb_substr($lastClass, 0, 2));

				$h .= '<tr class="overview_line tr-bold">';

					$h .= '<td>';
						$h .= encode($eAccount['class']).' ';
						$h .= encode(ucfirst(mb_strtolower($eAccount['description'], 'UTF-8')));
					$h .= '</td>';

					$h .= '<td class="text-end balance-td-brut">';
						if($cumulation['currentBrut'] !== 0) {
							$h.= \util\TextUi::money($cumulation['currentBrut'], precision: 0);
						}
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-amortization">';
					if((int)$cumulation['currentDepreciation'] !== 0) {
						$h .= \util\TextUi::money($cumulation['currentDepreciation'], precision: 0);
					}
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net">';
						if($cumulation['currentNet'] !== 0) {
							$h.= \util\TextUi::money($cumulation['currentNet'], precision: 0);
						}
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net">';
						$h .= $this->getPercent($cumulation['currentNet'] / $total);
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net">'.($cumulation['comparisonNet'] === 0 ? '' : \util\TextUi::money($cumulation['comparisonNet'], precision: 0)).'</td>';

				$h .= '</tr>';

				$categoryCumulation['currentBrut'] += $cumulation['currentBrut'];
				$categoryCumulation['currentDepreciation'] += $cumulation['currentDepreciation'];
				$categoryCumulation['currentNet'] += $cumulation['currentNet'];
				$categoryCumulation['comparisonNet'] += $cumulation['comparisonNet'];

				$cumulation = ['currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0, 'comparisonNet' => 0];

			}

			$isLineDetail = (mb_strlen($class) > 3);

			$h .= '<tr class="overview_line'.($isLineDetail ? ' pdf-tr-muted pdf-tr-ml-1' : '').'">';

					$h .= '<td>';

						// 2 premiers cas : détail de ligne
						if($cAccount->offsetExists((int)trim($line['class'], '0'))) {

							$eAccount = $cAccount[(int)trim($line['class'], '0')];
							$classDisplay = $line['class'];

						} else if($isLineDetail) { // Numéro de compte personnalisé mais non créé dans les comptes

							$eAccount = new \account\Account(['description' => $line['description']]);
							$classDisplay = $line['class'];

						} else if($cAccount->offsetExists($line['class'])) { // Ligne "normale"

							$eAccount = $cAccount->offsetGet($line['class']);
							$classDisplay = $eAccount['class'];

						} else { // Ligne "synthèse" sur 2 chiffres

							$eAccount = $cAccount->offsetGet(substr($line['class'], 0, 2));
							$classDisplay = $eAccount['class'];

						}

						$h .= encode($classDisplay).' ';
						$h .= encode(ucfirst(mb_strtolower($eAccount['description'], 'UTF-8')));

					$h .= '</td>';
					$h .= '<td class="text-end balance-td-brut">';
						if((int)$line['currentBrut'] !== 0) {
							$h.= \util\TextUi::money($line['currentBrut'], precision: 0);
						}
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-amortization">';
					if((int)$line['currentDepreciation'] !== 0) {
						$h .= \util\TextUi::money($line['currentDepreciation'], precision: 0);
					}
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net">';
						if((int)$line['currentNet'] !== 0) {
							$h.= \util\TextUi::money($line['currentNet'], precision: 0);
						}
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net"></td>';
					$h .= '<td class="text-end balance-td-net">'.($line['comparisonNet'] === 0 ? '' : \util\TextUi::money($line['comparisonNet'], precision: 0)).'</td>';
				$h .= '</tr>';

				$lastClass = $line['class'];

				if($isLineDetail === FALSE) {

					$cumulation['currentBrut'] += $line['currentBrut'];
					$cumulation['currentDepreciation'] += $line['currentDepreciation'];
					$cumulation['currentNet'] += $line['currentNet'];
					$cumulation['comparisonNet'] += $line['comparisonNet'];

				}

		}

		if($lastClass !== NULL) {

			$eAccount = $cAccount->offsetGet(mb_substr($lastClass, 0, 2));

			$h .= '<tr class="overview_line tr-bold">';

				$h .= '<td>';
					$h .= encode($eAccount['class']).' ';
					$h .= encode(ucfirst(mb_strtolower($eAccount['description'], 'UTF-8')));
				$h .= '</td>';

				$h .= '<td class="text-end balance-td-brut">';
					if($cumulation['currentBrut'] !== 0) {
						$h .= \util\TextUi::money($cumulation['currentBrut'], precision: 0);
					}
				$h .= '</td>';
				$h .= '<td class="text-end balance-td-amortization">';
				if((int)$cumulation['currentDepreciation'] !== 0) {
					$h .= \util\TextUi::money($cumulation['currentDepreciation'], precision: 0);
				}
				$h .= '</td>';
				$h .= '<td class="text-end balance-td-net">';
					if($cumulation['currentNet'] !== 0) {
						$h .= \util\TextUi::money($cumulation['currentNet'], precision: 0);
					}
				$h .= '</td>';
				$h .= '<td class="text-end balance-td-net">';
					$h .= $this->getPercent($cumulation['currentNet'] / $total);
				$h .= '</td>';
				$h .= '<td class="text-end balance-td-net">'.($cumulation['comparisonNet'] === 0 ? '' : \util\TextUi::money($cumulation['comparisonNet'], precision: 0)).'</td>';

			$h .= '</tr>';

		}

		$categoryCumulation['currentBrut'] += $cumulation['currentBrut'];
		$categoryCumulation['currentDepreciation'] += $cumulation['currentDepreciation'];
		$categoryCumulation['currentNet'] += $cumulation['currentNet'];
		$categoryCumulation['comparisonNet'] += $cumulation['comparisonNet'];

		$h .= '<tr class="overview_line tr-bold pdf-tr-title">';

			$h .= '<td>';
				$h .= $type === 'fixedAssets' ? s("Total actif immobilisé") : s("Total actif circulant");
			$h .= '</td>';

			$h .= '<td class="text-end balance-td-brut">'.\util\TextUi::money($categoryCumulation['currentBrut'], precision: 0).'</td>';
			$h .= '<td class="text-end balance-td-amortization">';
			if((int)$categoryCumulation['currentDepreciation'] !== 0) {
				$h .= \util\TextUi::money($categoryCumulation['currentDepreciation'], precision: 0);
			}
			$h .= '</td>';
			$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($categoryCumulation['currentNet'], precision: 0).'</td>';
					$h .= '<td class="text-end balance-td-net">';
						$h .= $this->getPercent($categoryCumulation['currentNet'] / $total);
					$h .= '</td>';
			$h .= '<td class="text-end balance-td-net">'.($categoryCumulation['comparisonNet'] === 0 ? '' : \util\TextUi::money($categoryCumulation['comparisonNet'], precision: 0)).'</td>';

		$h .= '</tr>';

		return $h;
	}

	private function displaySubCategoryPdfLinesLiabilities(string $type, array $category, \Collection $cAccount, float $total): string {

		$h = '';
		$lastClass = NULL;

		$cumulation = ['currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0, 'comparisonNet' => 0];
		$categoryCumulation = ['currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0, 'comparisonNet' => 0];

		foreach($category as $class => $line) {

			// Le jeu des arrondis fait qu'on aura une ligne vide
			if(
				(int)$line['comparisonBrut'] === 0 and (int)$line['comparisonDepreciation'] === 0 and (int)$line['comparisonNet'] === 0 and
				(int)$line['currentBrut'] === 0 and (int)$line['currentDepreciation'] === 0 and (int)$line['currentNet'] === 0
			) {
				continue;
			}

			if($lastClass !== NULL and mb_substr($line['class'], 0, 2) !== mb_substr($lastClass, 0, 2)) {

				$eAccount = $cAccount->offsetGet(mb_substr($lastClass, 0, 2));

				$h .= '<tr class="overview_line tr-bold">';

					$h .= '<td>';
						$h .= encode($eAccount['class']).' ';
						$h .= encode(ucfirst(mb_strtolower($eAccount['description'], 'UTF-8')));
					$h .= '</td>';

					$h .= '<td class="text-end balance-td-net">';
						if((int)$cumulation['currentNet'] !== 0) {
							$h .= \util\TextUi::money($cumulation['currentNet'], precision: 0);
						}
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net">';
						$h .= $this->getPercent($cumulation['currentNet'] / $total);
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net">'.($cumulation['comparisonNet'] === 0 ? '' : \util\TextUi::money($cumulation['comparisonNet'], precision: 0)).'</td>';

				$h .= '</tr>';

				$categoryCumulation['currentBrut'] += $cumulation['currentBrut'];
				$categoryCumulation['currentDepreciation'] += $cumulation['currentDepreciation'];
				$categoryCumulation['currentNet'] += $cumulation['currentNet'];
				$categoryCumulation['comparisonNet'] += $cumulation['comparisonNet'];

				$cumulation = ['currentBrut' => 0, 'currentDepreciation' => 0, 'currentNet' => 0, 'comparisonNet' => 0];

			}

			$isLineDetail = (mb_strlen($class) > 3);

			$h .= '<tr class="overview_line'.($isLineDetail ? ' pdf-tr-muted pdf-tr-ml-1' : '').'">';

					$h .= '<td>';
						// 2 premiers cas : détail de ligne
						if($cAccount->offsetExists((int)trim($line['class'], '0'))) {

							$eAccount = $cAccount[(int)trim($line['class'], '0')];
							$classDisplay = $line['class'];

						} else if($isLineDetail) { // Numéro de compte personnalisé mais non créé dans les comptes

							$eAccount = new \account\Account(['description' => $line['description']]);
							$classDisplay = $line['class'];

						} else if($cAccount->offsetExists($line['class'])) { // Ligne "normale"

							$eAccount = $cAccount->offsetGet($line['class']);
							$classDisplay = $eAccount['class'];

						} else { // Ligne "synthèse" sur 2 chiffres

							$eAccount = $cAccount->offsetGet(substr($line['class'], 0, 2));
							$classDisplay = $eAccount['class'];

						}

						$h .= encode($classDisplay).' ';
						$h .= encode(ucfirst(mb_strtolower($eAccount['description'], 'UTF-8')));
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net">';
						if((int)$line['currentNet'] !== 0) {
							$h .= \util\TextUi::money($line['currentNet'], precision: 0);
						}
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net"></td>';
					$h .= '<td class="text-end balance-td-net">'.($line['comparisonNet'] === 0 ? '' : \util\TextUi::money($line['comparisonNet'], precision: 0)).'</td>';
				$h .= '</tr>';

				$lastClass = $line['class'];

				if($isLineDetail === FALSE) {

					$cumulation['currentBrut'] += $line['currentBrut'];
					$cumulation['currentDepreciation'] += $line['currentDepreciation'];
					$cumulation['currentNet'] += $line['currentNet'];
					$cumulation['comparisonNet'] += $line['comparisonNet'];

			}

		}

		if($lastClass !== NULL) {

			$eAccount = $cAccount->offsetGet(mb_substr($lastClass, 0, 2));

			$h .= '<tr class="overview_line tr-bold">';

				$h .= '<td>';
					$h .= encode($eAccount['class']).' ';
					$h .= encode(ucfirst(mb_strtolower($eAccount['description'], 'UTF-8')));
				$h .= '</td>';

				$h .= '<td class="text-end balance-td-net">';
					if($cumulation['currentNet'] !== 0) {
						$h .= \util\TextUi::money($cumulation['currentNet'], precision: 0);
					}
					$h .= '</td>';
					$h .= '<td class="text-end balance-td-net">';
						$h .= $this->getPercent($cumulation['currentNet'] / $total);
					$h .= '</td>';
				$h .= '<td class="text-end balance-td-net">'.($cumulation['comparisonNet'] === 0 ? '' : \util\TextUi::money($cumulation['comparisonNet'], precision: 0)).'</td>';

			$h .= '</tr>';
		}

		$categoryCumulation['currentBrut'] += $cumulation['currentBrut'];
		$categoryCumulation['currentDepreciation'] += $cumulation['currentDepreciation'];
		$categoryCumulation['currentNet'] += $cumulation['currentNet'];
		$categoryCumulation['comparisonNet'] += $cumulation['comparisonNet'];

		$h .= '<tr class="overview_line tr-bold pdf-tr-title">';

			$h .= '<td>';
				$h .= $type === 'equity' ? s("Total capitaux propres") : s("Total dettes");
			$h .= '</td>';

			$h .= '<td class="text-end balance-td-net">'.\util\TextUi::money($categoryCumulation['currentNet'], precision: 0).'</td>';
					$h .= '<td class="text-end balance-td-net">';
						$h .= $this->getPercent($categoryCumulation['currentNet'] / $total);
					$h .= '</td>';
			$h .= '<td class="text-end balance-td-net">'.($categoryCumulation['comparisonNet'] === 0 ? '' : \util\TextUi::money($categoryCumulation['comparisonNet'], precision: 0)).'</td>';

		$h .= '</tr>';

		return $h;
	}
}
?>
