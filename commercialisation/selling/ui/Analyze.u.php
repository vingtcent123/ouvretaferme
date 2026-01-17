<?php
namespace selling;

class AnalyzeUi {

	public function __construct() {

		\Asset::css('analyze', 'chart.css');
		\Asset::js('analyze', 'chart.js');

	}

	public function getTurnover(\farm\Farm $eFarm, \Collection $cSaleTurnover, ?int $year, ?string $month, ?string $week): string {

		$h = '<ul class="util-summarize util-summarize-overflow">';

			foreach($cSaleTurnover as $eSaleTurnover) {
				$h .= '<li '.($eSaleTurnover['year'] === $year ? 'class="selected"' : '').'>';
					$h .= '<a href="'.\farm\FarmUi::urlAnalyzeSelling($eFarm, $eSaleTurnover['year'], \farm\Farmer::ITEM).'?'.http_build_query(['week' => $week, 'month' => $month]).'">';
						$h .= '<h5>'.$eSaleTurnover['year'].'</h5>';
						$h .= '<div>'.\util\TextUi::money($eSaleTurnover['turnover'], precision: 0).'</div>';
					$h .= '</a>';
				$h .= '</li>';
			}

		$h .= '</ul>';

		return $h;
	}

	public function getShopTurnover(\farm\Farm $eFarm, \shop\Shop $eShop, \Collection $cSaleTurnover, ?int $year): string {

		$h = '<ul class="util-summarize">';

			foreach($cSaleTurnover as $eSaleTurnover) {
				$h .= '<li '.($eSaleTurnover['year'] === $year ? 'class="selected"' : '').'>';
					$h .= '<a href="'.\farm\FarmUi::urlAnalyzeSelling($eFarm, $eSaleTurnover['year'], \farm\Farmer::SHOP).'?shop='.$eShop['id'].'">';
						$h .= '<h5>'.$eSaleTurnover['year'].'</h5>';
						$h .= '<div>'.\util\TextUi::money($eSaleTurnover['turnover'], precision: 0).'</div>';
					$h .= '</a>';
				$h .= '</li>';
			}

		$h .= '</ul>';

		return $h;
	}

	public function getPeriod(int $year, \Collection $cItemMonth, \Collection $cItemMonthBefore, \Collection $cItemWeek, \Collection $cItemWeekBefore, \Search $search): string {

		$h = $this->getSearch($search);

		if($cItemMonth->empty()) {

			$h .= '<div class="util-empty">';
				$h .= s("Aucune vente n'a été enregistrée pour cette année.");
			$h .= '</div>';

			return $h;

		}

		$h .= '<h2>'.s("Ventes mensuelles").'</h2>';
		$h .= '<div class="analyze-chart-table">';
			$h .= $this->getPeriodMonthTable($cItemMonth, search: $search);
			if($cItemMonthBefore->notEmpty()) {
				$h .= $this->getDoublePeriodMonthChart('turnover', $cItemMonth, $year, $cItemMonthBefore, $year - 1);
			} else {
				$h .= $this->getPeriodMonthChart('turnover', $cItemMonth);
			}
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<h2>'.s("Ventes hebdomadaires").'</h2>';
		if($cItemMonthBefore->notEmpty()) {
			$h .= $this->getDoublePeriodWeekChart('turnover', $cItemWeek, $year, $cItemWeekBefore, $year - 1);
		} else {
			$h .= $this->getPeriodWeekChart('turnover', $cItemWeek);
		}

		return $h;

	}

	protected function getPeriodMonthTable(\Collection $cItemMonth, Product|\plant\Plant|Customer|null $e = NULL, \Search $search = new \Search()): string {

		$totalTurnover = $cItemMonth->sum('turnover');

		if($e === NULL) {
			$totalTurnoverPrivate = $cItemMonth->sum('turnoverPrivate');
			$totalTurnoverPro = $cItemMonth->sum('turnoverPro');
		}

		$h = '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="tr-even analyze-values">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th>'.s("Mois").'</th>';
						$h .= '<th class="text-end">'.s("Ventes").'</th>';
						$h .= '<th></th>';
						if($e === NULL and $search->isFiltered('type') === FALSE) {
							$h .= '<th class="text-end color-private">'.s("Dont<br/>particuliers").'</th>';
							$h .= '<th></th>';
							$h .= '<th class="color-pro">'.s("Dont<br/>professionnels").'</th>';
						}

						if($e instanceof Product) {
							$h .= '<th class="text-end">'.s("Volume").'</th>';
							$h .= '<th class="text-end">'.s("Prix").'</th>';
						}

						if($e instanceof \plant\Plant) {
							$h .= '<th class="text-center" colspan="2">'.s("Volume").'</th>';
							$h .= '<th class="text-end">'.s("Prix").'</th>';
						}

					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					for($month = 1; $month <= 12; $month++) {

						if($cItemMonth->offsetExists($month)) {

							$eItemMonth = $cItemMonth[$month];
							$turnover = $eItemMonth['turnover'];

							if($e === NULL) {
								$turnoverPrivate = $eItemMonth['turnoverPrivate'];
								$turnoverPro = $eItemMonth['turnoverPro'];
							}

						} else {

							$eItemMonth = new Item();
							$turnover = NULL;

							if($e === NULL) {
								$turnoverPrivate = NULL;
								$turnoverPro = NULL;
							}

						}

						$h .= '<tr>';
							$h .= '<td>';
								$h .= ucfirst(\util\DateUi::getMonthName($month));
							$h .= '</td>';
							$h .= '<td class="text-end">';
								if($turnover !== NULL) {
									$h .= \util\TextUi::money($turnover, precision: 0);
								} else {
									$h .= '-';
								}
							$h .= '</td>';
							$h .= '<td>';
								if($turnover !== NULL and $totalTurnover > 0) {
									$h .= '<div class="util-annotation">';
										$h .= \util\TextUi::pc($turnover / $totalTurnover * 100);
									$h .= '</div>';
								}
							$h .= '</td>';

							if($e === NULL and $search->isFiltered('type') === FALSE) {

								$h .= '<td class="text-end color-private">';
									if($turnoverPrivate !== NULL) {
										$h .= \util\TextUi::money($turnoverPrivate, precision: 0);
									} else {
										$h .= '-';
									}
								$h .= '</td>';
								$h .= '<td class="td-min-content">';
									if((float)$turnover !== 0.0) {

										$partPrivate = round($turnoverPrivate / $turnover * 100);
										$partPro = 100 - $partPrivate;

										$h .= '<iv class="analyze-values-balance" style="width: 10rem">';
											if($partPrivate > 0) {
												$h .= '<div class="analyze-values-private" style="width: '.$partPrivate.'%">'.s("{value}<small> %</small>", $partPrivate).'</div>';
											}
											if($partPro > 0) {
												$h .= '<div class="analyze-values-pro" style="width: '.$partPro.'%">'.s("{value}<small> %</small>", $partPro).'</div>';
											}
										$h .= '</div>';

									}
								$h .= '</td>';
								$h .= '<td class="color-pro">';
									if($turnoverPro !== NULL) {
										$h .= \util\TextUi::money($turnoverPro, precision: 0);
									} else {
										$h .= '-';
									}
								$h .= '</td>';

							} else if($e instanceof Product) {

								if($eItemMonth->empty()) {
									$h .= '<td class="text-end">-</td>';
									$h .= '<td class="text-end">-</td>';
								} else {

									$h .= '<td class="text-end">';
										$h .= \selling\UnitUi::getValue(round($eItemMonth['quantity']), $e['unit'], short: TRUE);
									$h .= '</td>';
									$h .= '<td class="text-end">';
										$h .= ($eItemMonth['quantity'] > 0) ? \util\TextUi::money($turnover / $eItemMonth['quantity']) : '-';
									$h .= '</td>';

								}

							} else if($e instanceof \plant\Plant) {

								if($eItemMonth->empty()) {
									$h .= '<td class="text-end">-</td>';
									$h .= '<td></td>';
									$h .= '<td class="text-end">-</td>';
								} else {

									$monthly = NULL;

									$cItem = $eItemMonth['cItem'];

									$h .= $this->getMonthlyQuantity($monthly, $cItem);
									$h .= $this->getMonthlyAverage($monthly, $cItem);

								}

							}

						$h .= '</tr>';

					}

					$h .= '<tr class="analyze-total">';
						$h .= '<td>';
							$h .= s("Total");
						$h .= '</td>';
						$h .= '<td class="text-end">';
							$h .= \util\TextUi::money($totalTurnover, precision: 0);
						$h .= '</td>';
						$h .= '<td>';
							if($turnover !== NULL and $totalTurnover > 0) {
								$h .= '<div class="util-annotation">'.\util\TextUi::pc($turnover / $totalTurnover * 100, 0).'</div>';
							}
						$h .= '</td>';

						if($e === NULL and $search->isFiltered('type') === FALSE) {

							$h .= '<td class="text-end color-private">';
								if($totalTurnoverPrivate > 0) {
									$h .= \util\TextUi::money($totalTurnoverPrivate, precision: 0);
								} else {
									$h .= '-';
								}
							$h .= '</td>';
							$h .= '<td class="td-min-content">';
								if($totalTurnover > 0) {

									$partPrivate = round($totalTurnoverPrivate / $totalTurnover * 100);
									$partPro = 100 - $partPrivate;

									$h .= '<iv class="analyze-values-balance" style="width: 10rem">';
										if($partPrivate > 0) {
											$h .= '<div class="analyze-values-private" style="width: '.$partPrivate.'%">'.s("{value}<small> %</small>", $partPrivate).'</div>';
										}
										if($partPro > 0) {
											$h .= '<div class="analyze-values-pro" style="width: '.$partPro.'%">'.s("{value}<small> %</small>", $partPro).'</div>';
										}
									$h .= '</div>';

								}
							$h .= '</td>';
							$h .= '<td class="color-pro">';
								if($totalTurnoverPro > 0) {
									$h .= \util\TextUi::money($totalTurnoverPro, precision: 0);
								} else {
									$h .= '-';
								}
							$h .= '</td>';

						} else if($e instanceof Product) {

							$totalQuantity = $cItemMonth->sum('quantity');

							$h .= '<td class="text-end">';
								$h .= \selling\UnitUi::getValue(round($totalQuantity), $e['unit'], short: TRUE);
							$h .= '</td>';
							$h .= '<td class="text-end">';
								$h .= ($totalQuantity > 0) ? \util\TextUi::money($totalTurnover / $totalQuantity) : '-';
							$h .= '</td>';

						} else if($e instanceof \plant\Plant) {

							$h .= $this->getMonthlyQuantity($monthly, $e['cItem']);
							$h .= $this->getMonthlyAverage($monthly, $e['cItem']);

						}

					$h .= '</tr>';

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getPeriodMonthChart(string $chart, \Collection $cItemMonth, \farm\Farm $eFarmChart = new \farm\Farm()): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		[$values, $labels] = $this->extractMonthChartValues($cItemMonth, $chart);

		$title = match($chart) {
			\farm\Farmer::TURNOVER => s("Ventes"),
			\farm\Farmer::QUANTITY => s("Volumes")
		};

		$suffix = match($chart) {
			\farm\Farmer::TURNOVER => '€',
			\farm\Farmer::QUANTITY => ''
		};

		$h = '<div>';
			$h .= '<div class="analyze-bar">';
				$h .= '<canvas '.attr('onrender', 'Analyze.createBar(this, "'.$title.'", '.json_encode($values).', '.json_encode($labels).', "'.$suffix.'")').'</canvas>';
			$h .= '</div>';
			$h .= $this->getChartLink($chart, $eFarmChart);
		$h .= '</div>';

		return $h;

	}

	public function getDoublePeriodMonthChart(?string $chart, \Collection $cItemMonthNow, int $yearNow, \Collection $cItemMonthBefore, int $yearBefore, \farm\Farm $eFarmChart = new \farm\Farm()): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		$chart ??= $eFarmChart->getView('viewAnalyzeChart');

		[$turnoversNow, $labelsNow] = $this->extractMonthChartValues($cItemMonthNow, $chart);
		[$turnoversBefore] = $this->extractMonthChartValues($cItemMonthBefore, $chart);

		$title = fn($year) => match($chart) {
			\farm\Farmer::TURNOVER => s("Ventes {value}", $year),
			\farm\Farmer::QUANTITY => s("Volumes {value}", $year)
		};

		$suffix = match($chart) {
			\farm\Farmer::TURNOVER => '€',
			\farm\Farmer::QUANTITY => ''
		};

		$h = '<div>';
			$h .= '<div class="analyze-bar">';
				$h .= '<canvas '.attr('onrender', 'Analyze.createDoubleBar(this, "'.$title($yearNow).'", '.json_encode($turnoversNow).', "'.$title($yearBefore).'", '.json_encode($turnoversBefore).', '.json_encode($labelsNow).', "'.$suffix.'")').'</canvas>';
			$h .= '</div>';
			$h .= $this->getChartLink($chart, $eFarmChart);
		$h.= '</div>';

		return $h;

	}

	protected function getChartLink(string $chart, \farm\Farm $eFarm): string {

		if($eFarm->empty()) {
			return '';
		}

		$h = '<div class="text-center mt-1">';
			$h .= match($chart) {
				\farm\Farmer::TURNOVER => '<a data-ajax="'.\util\HttpUi::setArgument(LIME_REQUEST, 'chart', \farm\Farmer::QUANTITY).'" data-ajax-method="get" style="text-decoration: underline; color: var(--muted)">'.s("Voir les volumes").'</a>',
				\farm\Farmer::QUANTITY => '<a data-ajax="'.\util\HttpUi::setArgument(LIME_REQUEST, 'chart', \farm\Farmer::TURNOVER).'" data-ajax-method="get" style="text-decoration: underline; color: var(--muted)">'.s("Voir les ventes").'</a>'
			};
		$h.= '</div>';

		return $h;

	}

	protected function extractMonthChartValues(\Collection $cItemMonth, string $chart): array {

		$values = [];
		$labels = [];

		for($month = 1; $month <= 12; $month++) {

			if($cItemMonth->offsetExists($month)) {

				$eItemMonth = $cItemMonth[$month];

				$values[] = round($eItemMonth[$chart]);

			} else {
				$values[] = 0;
			}

			$labels[] = \util\DateUi::getMonthName($month, type: 'short');

		}

		return [$values, $labels];

	}

	public function getPeriodWeekChart(?string $chart, \Collection $cItemWeek): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		[$values, $labels] = $this->extractWeekChartValues($cItemWeek, $chart);

		$title = match($chart) {
			\farm\Farmer::TURNOVER => s("Ventes"),
			\farm\Farmer::QUANTITY => s("Volumes")
		};

		$suffix = match($chart) {
			\farm\Farmer::TURNOVER => '€',
			\farm\Farmer::QUANTITY => ''
		};

		$h = '<div class="analyze-bar">';
			$h .= '<canvas '.attr('onrender', 'Analyze.createBar(this, "'.$title.'", '.json_encode($values).', '.json_encode($labels).', "'.$suffix.'")').'</canvas>';
		$h .= '</div>';

		return $h;

	}

	public function getDoublePeriodWeekChart(?string $chart, \Collection $cItemWeekNow, int $yearNow, \Collection $cItemWeekBefore, int $yearBefore, \farm\Farm $eFarmChart = new \farm\Farm()): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		[$valuesNow, $labelsNow] = $this->extractWeekChartValues($cItemWeekNow, $chart);
		[$valuesBefore] = $this->extractWeekChartValues($cItemWeekBefore, $chart);

		$title = fn($year) => match($chart) {
			\farm\Farmer::TURNOVER => s("Ventes {value}", $year),
			\farm\Farmer::QUANTITY => s("Volumes {value}", $year)
		};

		$suffix = match($chart) {
			\farm\Farmer::TURNOVER => '€',
			\farm\Farmer::QUANTITY => ''
		};

		$h = '<div>';
			$h .= '<div class="analyze-bar">';
				$h .= '<canvas '.attr('onrender', 'Analyze.createDoubleBar(this, "'.$title($yearNow).'", '.json_encode($valuesNow).', "'.$title($yearBefore).'", '.json_encode($valuesBefore).', '.json_encode($labelsNow).', "'.$suffix.'")').'</canvas>';
			$h .= '</div>';
			$h .= $this->getChartLink($chart, $eFarmChart);
		$h .= '</div>';

		return $h;

	}

	protected function extractWeekChartValues(\Collection $cItemWeek, string $chart): array {

		$values = [];
		$labels = [];

		for($week = 1; $week <= 52; $week++) {

			if($cItemWeek->offsetExists($week)) {

				$eItemWeek = $cItemWeek[$week];
				$values[] = round($eItemWeek[$chart]);

			} else {
				$values[] = 0;
			}

			$labels[] = $week;

		}

		return [$values, $labels];

	}

	public function getBestCustomers(\Collection $ccItemCustomer, \Collection $ccItemCustomerMonthly, int $year, ?int $month, ?string $week, ?string $monthly, \Search $search): string {

		if($ccItemCustomer->offsetExists($year) === FALSE) {

			$h = '<div class="util-empty">';
				$h .= s("Aucune vente n'a été enregistrée pour cette année.");
			$h .= '</div>';

			return $h;

		}
		$h = '<div class="util-title">';
			$h .= '<h2>'.s("Meilleurs clients").'</h2>';
			$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#analyze-selling-search")').' class="btn btn-outline-primary">'.\Asset::icon('search').' '.s("Filtrer").'</a> ';
		$h .= '</div>';

		$h .= $this->getSearch($search);

		$h .= '<div class="'.($monthly ? '' : 'analyze-chart-table').'">';
			if($monthly === NULL) {
				$h .= $this->getBestCustomersPie($ccItemCustomer, $year);
			}
			$h .= $this->getBestCustomersTable($ccItemCustomer, $year, ccItemCustomerMonthly: $ccItemCustomerMonthly, monthly: $monthly, search: $search, zoom: ($month === NULL and $week === NULL));
		$h .= '</div>';

		return $h;

	}

	protected function getBestCustomersTable(\Collection $ccItemCustomer, int $year, ?int $limit = NULL, \Collection $ccItemCustomerMonthly = new \Collection(), ?string $monthly = NULL, ?\Search $search = NULL, bool $zoom = TRUE): string {

		$search ??= new \Search()
			->sort(GET('sort'))
			->validateSort(['customer', 'turnover'], 'turnover-');

		$ccItemCustomer[$year]->sort($search->buildSort([
			'customer' => fn($direction) => [
				'customer' => ['name' => $direction]
			]
		]));

		$turnover = $ccItemCustomer[$year]->sum('turnover');
		$turnoverBefore = $ccItemCustomer->offsetExists($year - 1) ? $ccItemCustomer[$year - 1]->sum('turnover') : 0;

		$h = '<div class="'.($monthly ? 'util-overflow-lg' : '').' stick-xs">';

			$monthlyClass = match($monthly) {
				'turnover' => 'analyze-month-table-5',
				NULL => '',
			};

			$h .= '<table class="tr-even analyze-values '.$monthlyClass.'">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th rowspan="2">'.$search->linkSort('customer', s("Client")).'</th>';
						$h .= '<th colspan="'.($monthly === NULL ? 4 : 2 + 12).'" class="text-center">'.s("Ventes").'</th>';
						$h .= '<th rowspan="2"></th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="text-end">';
							$h .= $search->linkSort('turnover', $year, SORT_DESC);
						$h .= '</th>';
						$h .= '<th>';
							$h .= $this->getMonthlyLink($monthly, 'turnover');
						$h .= '</th>';

						switch($monthly) {

							case 'turnover' :

								for($month = 1; $month <= 12; $month++) {
									$h .= '<th class="text-center">'.\util\DateUi::getMonthName($month, type: 'short').'</th>';
								}

								break;

							default :

								$h .= '<th class="text-end">'.($year - 1).'</th>';
								$h .= '<th></th>';

								break;

						}

					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

				$again = $limit;

				foreach($ccItemCustomer[$year] as $eItem) {

					if($again !== NULL and $again-- === 0) {
						break;
					}

					$h .= '<tr>';
						$h .= '<td>';
							$h .= CustomerUi::link($eItem['customer']);
						$h .= '</td>';
						$h .= '<td class="text-end">';
							$h .= \util\TextUi::money($eItem['turnover'], precision: 0);
						$h .= '</td>';
						$h .= '<td class="util-annotation">';
							if($turnover > 0) {
								$h .= \util\TextUi::pc($eItem['turnover'] / $turnover * 100, 0);
							}
						$h .= '</td>';

						switch($monthly) {

							case 'turnover' :

								for($month = 1; $month <= 12; $month++) {

									$eItemMonthly = $ccItemCustomerMonthly[$eItem['customer']['id']][$month] ?? new Item();

									$h .= '<td class="text-end analyze-month-value">';
										if($eItemMonthly->notEmpty()) {
											$h .= \util\TextUi::money($eItemMonthly['turnover'], precision: 0);
										}
									$h .= '</td>';

								}

								break;

							default :

								$eItemBefore = $ccItemCustomer[$year - 1][$eItem['customer']['id']] ?? new Item();

								if($eItemBefore->notEmpty()) {
									$h .= '<td class="text-end">';
										$h .= \util\TextUi::money($eItemBefore['turnover'], precision: 0);
									$h .= '</td>';
									$h .= '<td class="util-annotation">';
										if($turnoverBefore > 0) {
											$h .= \util\TextUi::pc($eItemBefore['turnover'] / $turnoverBefore * 100, 0);
										}
									$h .= '</td>';
								} else {
									$h .= '<td class="text-end">-</td>';
									$h .= '<td></td>';
								}

								break;

						}

						$h .= '<td class="td-min-content">';
							if($zoom) {
								$h .= '<a href="/selling/customer:analyze?id='.$eItem['customer']['id'].'&year='.$year.'" class="btn btn-sm btn-outline-secondary">'.\Asset::icon('search').'</a>';
							}
						$h .= '</td>';

					$h .= '</tr>';

				}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		if($limit !== NULL and $ccItemCustomer->count() > $limit) {
			$h .= '<p class="util-info" style="margin-top: -1rem">';
				$h .= p("+ {value} autre client", "+ {value} autres clients", $ccItemCustomer->count() - $limit);
			$h .= '</p>';
		}

		return $h;

	}

	protected function getBestCustomersByProductTable(Product $eProduct, \Collection $ccItemCustomer, \Collection $ccItemType, int $year, ?int $limit = NULL): string {

		$turnover = $ccItemCustomer[$year]->sum('turnover');
		$turnoverBefore = $ccItemCustomer->offsetExists($year - 1) ? $ccItemCustomer[$year - 1]->sum('turnover') : 0;

		$line = function($eItem, $eItemBefore) use($eProduct, $turnover, $turnoverBefore) {

			$h = '<td class="text-end">';
				$h .= \util\TextUi::money($eItem['turnover'], precision: 0);
			$h .= '</td>';
			$h .= '<td>';
				$h .= '<div class="util-annotation">';
					$h .= ($turnover > 0) ? \util\TextUi::pc($eItem['turnover'] / $turnover * 100) : '-';
				$h .= '</div>';
			$h .= '</td>';

			if($eItemBefore->notEmpty()) {
				$h .= '<td class="text-end">';
					$h .= \util\TextUi::money($eItemBefore['turnover'], precision: 0);
				$h .= '</td>';
				$h .= '<td>';
					$h .= '<div class="util-annotation">';
						$h .= ($turnoverBefore > 0) ? \util\TextUi::pc($eItemBefore['turnover'] / $turnoverBefore * 100, 0) : '-';
					$h .= '</div>';
				$h .= '</td>';
			} else {
				$h .= '<td class="text-end">-</td>';
				$h .= '<td></td>';
			}

			$h .= '<td class="text-end">';
				$h .= \selling\UnitUi::getValue(round($eItem['quantity']), $eProduct['unit'], short: TRUE);
			$h .= '</td>';

			$h .= '<td class="text-end">';
				$h .= ($eItem['quantity'] > 0) ? \util\TextUi::money($eItem['turnover'] / $eItem['quantity']) : '';
			$h .= '</td>';

			return $h;

		};

		$h = '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="tr-even analyze-values">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th rowspan="2">'.s("Client").'</th>';
						$h .= '<th colspan="4" class="text-center">'.s("Ventes").'</th>';
						$h .= '<th class="text-end">'.s("Volume").'</th>';
						$h .= '<th class="text-end">'.s("Prix").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="text-end">'.$year.'</th>';
						$h .= '<th></th>';
						$h .= '<th class="text-end">'.($year - 1).'</th>';
						$h .= '<th></th>';
						$h .= '<th class="text-end">'.$year.'</th>';
						$h .= '<th class="text-end">'.$year.'</th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					$again = $limit;

					foreach($ccItemCustomer[$year] as $eItem) {

						if($again !== NULL and $again-- === 0) {
							break;
						}

						$eItemBefore = $ccItemCustomer[$year - 1][$eItem['customer']['id']] ?? new Item();

						$h .= '<tr>';
							$h .= '<td>';
								$h .= CustomerUi::link($eItem['customer']);
							$h .= '</td>';
							$h .= $line($eItem, $eItemBefore);
						$h .= '</tr>';

					}

					if($ccItemType->notEmpty()) {

						foreach($ccItemType[$year] as $eItem) {

							$eItemBefore = $ccItemType[$year - 1][$eItem['type']] ?? new Item();

							$h .= '<tr class="color-'.$eItem['type'].'" style="'.($eItem['type'] === Customer::PRIVATE ? 'border-top: 0.25rem solid var(--primary)' : '').'">';
								$h .= '<td>';
									$h .= match($eItem['type']) {
										Customer::PRIVATE => s("Clients particuliers"),
										Customer::PRO => s("Clients professionnels")
									};
								$h .= '</td>';
								$h .= $line($eItem, $eItemBefore);
							$h .= '</tr>';

						}

					}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		if($limit !== NULL and $ccItemCustomer->count() > $limit) {
			$h .= '<p class="util-info" style="margin-top: -1rem">';
				$h .= p("+ {value} autre client", "+ {value} autres clients", $ccItemCustomer->count() - $limit);
			$h .= '</p>';
		}

		return $h;

	}

	protected function getBestCustomersByPlantTable(\plant\Plant $ePlant, \Collection $ccItemCustomer, \Collection $ccItemType, int $year, ?int $limit = NULL): string {

		$turnover = $ccItemCustomer[$year]->sum('turnover');
		$turnoverBefore = $ccItemCustomer->offsetExists($year - 1) ? $ccItemCustomer[$year - 1]->sum('turnover') : 0;

		$line = function($eItem, $eItemBefore) use($ePlant, $turnover, $turnoverBefore) {

			$h = '<td class="text-end">';
				$h .= \util\TextUi::money($eItem['turnover'], precision: 0);
			$h .= '</td>';
			$h .= '<td>';
				$h .= '<div class="util-annotation">';
					$h .= ($turnover > 0 ? \util\TextUi::pc($eItem['turnover'] / $turnover * 100) : '-');
				$h .= '</div>';
			$h .= '</td>';

			if($eItemBefore->notEmpty()) {
				$h .= '<td class="text-end">';
					$h .= \util\TextUi::money($eItemBefore['turnover'], precision: 0);
				$h .= '</td>';
				$h .= '<td class="util-annotation">';
					$h .= ($turnoverBefore > 0 ? \util\TextUi::pc($eItemBefore['turnover'] / $turnoverBefore * 100) : '-');
				$h .= '</td>';
			} else {
				$h .= '<td class="text-end">-</td>';
				$h .= '<td></td>';
			}

			return $h;

		};

		$h = '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="tr-even analyze-values">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th rowspan="2">'.s("Client").'</th>';
						$h .= '<th colspan="4" class="text-center">'.s("Ventes").'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="text-end">'.$year.'</th>';
						$h .= '<th></th>';
						$h .= '<th class="text-end">'.($year - 1).'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					$again = $limit;

					foreach($ccItemCustomer[$year] as $eItem) {

						if($again !== NULL and $again-- === 0) {
							break;
						}

						$eItemBefore = $ccItemCustomer[$year - 1][$eItem['customer']['id']] ?? new Item();

						$h .= '<tr>';
							$h .= '<td>';
								$h .= CustomerUi::link($eItem['customer']);
							$h .= '</td>';
							$h .= $line($eItem, $eItemBefore);
						$h .= '</tr>';

					}

					if($ccItemType->notEmpty()) {

						foreach($ccItemType[$year] as $eItem) {

							$eItemBefore = $ccItemType[$year - 1][$eItem['type']] ?? new Item();

							$h .= '<tr class="color-'.$eItem['type'].'" style="'.($eItem['type'] === Customer::PRIVATE ? 'border-top: 0.25rem solid var(--primary)' : '').'">';
								$h .= '<td>';
									$h .= match($eItem['type']) {
										Customer::PRIVATE => s("Clients particuliers"),
										Customer::PRO => s("Clients professionnels")
									};
								$h .= '</td>';
								$h .= $line($eItem, $eItemBefore);
							$h .= '</tr>';

						}

					}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		if($limit !== NULL and $ccItemCustomer->count() > $limit) {
			$h .= '<p class="util-info" style="margin-top: -1rem">';
				$h .= p("+ {value} autre client", "+ {value} autres clients", $ccItemCustomer->count() - $limit);
			$h .= '</p>';
		}

		return $h;

	}

	protected function getBestProductsByPlantTable(\plant\Plant $ePlant, \Collection $cItemTurnover, int $year): string {

		$turnover = $cItemTurnover->sum('turnover');

		$h = '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="tr-even analyze-values">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th rowspan="2">'.s("Produit").'</th>';
						$h .= '<th class="text-end">'.s("Volume").'</th>';
						$h .= '<th class="text-end">'.s("Ventes").'</th>';
						$h .= '<th></th>';
						$h .= '<th class="text-end">'.s("Prix").'</th>';
					$h .= '</tr>';

				$h .= '</thead>';
				$h .= '<tbody>';

					foreach($cItemTurnover as $eItem) {

						$h .= '<tr>';
							$h .= '<td>';
								$h .= ProductUi::getVignette($eItem['product'], '2rem').'  ';
								$h .= ProductUi::link($eItem['product']);
							$h .= '</td>';
							$h .= '<td class="text-end">';
								$h .= \selling\UnitUi::getValue(round($eItem['quantity']), $eItem['product']['unit'], short: TRUE);
							$h .= '</td>';
							$h .= '<td class="text-end">';
								$h .= \util\TextUi::money($eItem['turnover'], precision: 0);
							$h .= '</td>';
							$h .= '<td class="util-annotation">';
								$h .= ($turnover > 0 ? \util\TextUi::pc($eItem['turnover'] / $turnover * 100) : '-');
							$h .= '</td>';
							$h .= '<td class="text-end">';
								$h .= ($eItem['quantity'] > 0) ? \util\TextUi::money($eItem['turnover'] / $eItem['quantity']) : '-';
							$h .= '</td>';
						$h .= '</tr>';

					}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getBestCustomersPie(\Collection $ccItemCustomer, int $year): string {

		$cItemCustomer = $ccItemCustomer[$year];

		return new \analyze\ChartUi()->buildPie(
			s("Répartition des ventes par client en {value}", $year),
			$cItemCustomer,
			'turnover',
			fn($eTimesheet) => $eTimesheet['customer']->getName()
		);

	}

	public function getEmptyShop(): string {

		$h = '<div class="util-empty">';
			$h .= s("Vous n'avez pas ouvert de boutique en ligne sur {siteName}.");
		$h .= '</div>';

		return $h;

	}

	public function getShop(\farm\Farm $eFarm, \Collection $cShop, \shop\Shop $eShopSelected, \Collection $cSaleTurnover, \Collection $cItemProduct, \Collection $cItemProductMonthly, \Collection $cPlant, \Collection $cccItemPlantMonthly, \Collection $ccItemCustomer, int $year, ?string $monthly): string {

		$h = '<div class="util-title">';
			$h .= '<h2>';
				if($cShop->count() === 1) {
					$h .= encode($eShopSelected['name']);
				} else {

					$h .= '<a data-dropdown="bottom-start" class="dropdown-toggle" data-dropdown-hover="true">'.encode($eShopSelected['name']).'</a>';
					$h .=' <div class="dropdown-list bg-secondary">';
						foreach($cShop as $eShop) {
							$h .= '<a href="'.\farm\FarmUi::urlAnalyzeSelling($eFarm, $year, \farm\Farmer::SHOP).'?shop='.$eShop['id'].'" class="dropdown-item '.($eShopSelected['id'] === $eShop['id'] ? 'selected' : '').'">'.encode($eShop['name']).'</a>';
						}
					$h .= '</div>';

				}
			$h .= '</h2>';
			$h .= '<div>';
				if($cItemProduct->contains(fn($eItemProduct) => $eItemProduct['containsComposition'] or $eItemProduct['containsIngredient'])) {
					$h .= SaleUi::getCompositionSwitch($eFarm, 'btn-outline-primary').' ';
				}
			$h .= '</div>';
		$h .= '</div>';

		$h .= $this->getShopTurnover($eFarm, $eShopSelected, $cSaleTurnover, $year);

		if($cItemProduct->empty() and $cPlant->empty()) {

			$h .= '<div class="util-empty">';
				$h .= s("Aucune vente en ligne n'a été enregistrée pour cette année.");
			$h .= '</div>';

			return $h;

		} else {

			$h .= '<div class="tabs-h" id="selling-analyze" onrender="'.encode('Lime.Tab.restore(this, "analyze-product")').'">';

				$h .= '<div class="tabs-item">';
					$h .= '<a class="tab-item selected" data-tab="analyze-product" onclick="Lime.Tab.select(this)">'.s("Par produit").'</a>';
					$h .= '<a class="tab-item" data-tab="analyze-plant" onclick="Lime.Tab.select(this)">'.s("Par espèce").'</a>';
					$h .= '<a class="tab-item" data-tab="analyze-customer" onclick="Lime.Tab.select(this)">'.s("Par client").'</a>';
				$h .= '</div>';

				$h .= '<div class="tab-panel selected '.($monthly ? '' : 'analyze-chart-table').'" data-tab="analyze-product">';
					if($cItemProduct->empty()) {
						$h .= '<div class="util-empty">';
							$h .= s("Aucune vente de produit n'a été enregistrée pour cette année.");
						$h .= '</div>';
					} else {

						if($monthly === NULL) {
							$h .= $this->getBestProductsPie($cItemProduct);
						}

						$h .= $this->getBestProductsTable($cItemProduct, $year, cItemProductMonthly: $cItemProductMonthly, monthly: $monthly, zoom: FALSE);

					}

				$h .= '</div>';

				$h .= '<div class="tab-panel '.($monthly ? '' : 'analyze-chart-table').'" data-tab="analyze-plant">';
					if($cPlant->empty()) {
						$h .= '<div class="util-empty">';
							$h .= s("Aucune vente sur une espèce n'a été enregistrée pour cette année.");
						$h .= '</div>';
					} else {
						if($monthly === NULL) {
							$h .= $this->getBestPlantsPie($cPlant);
						}
						$h .= $this->getBestPlantsTable($cPlant, $year, cccItemPlantMonthly: $cccItemPlantMonthly, monthly: $monthly, zoom: FALSE);
					}
				$h .= '</div>';

				$h .= '<div class="tab-panel analyze-chart-table" data-tab="analyze-customer">';
					if($ccItemCustomer->empty()) {
						$h .= '<div class="util-empty">';
							$h .= s("Aucun client n'a été trouvé pour cette année.");
						$h .= '</div>';
					} else {
						$h .= $this->getBestCustomersPie($ccItemCustomer, $year);
						$h .= $this->getBestCustomersTable($ccItemCustomer, $year, zoom: FALSE);
					}
				$h .= '</div>';

			$h .= '</div>';

		}

		return $h;

	}

	public function getBestSeller(\farm\Farm $eFarm, \Collection $cItemProduct, \Collection $cItemProductMonthly, \Collection $cPlant, \Collection $cccItemPlantMonthly, int $year, \Collection $cItemProductCompare, \Collection $cPlantCompare, ?int $yearCompare, array $years, ?string $monthly, ?string $month, ?string $week, \Search $search): string {

		$h = '<div class="util-title">';
			$h .= '<h2>'.s("Meilleures ventes").'</h2>';
			$h .= '<div>';

				if(
					$cItemProduct->contains(fn($eItemProduct) => $eItemProduct['containsComposition'] or $eItemProduct['containsIngredient']) or
					$cItemProductCompare->contains(fn($eItemProduct) => $eItemProduct['containsComposition'] or $eItemProduct['containsIngredient'])
				) {
					$h .= SaleUi::getCompositionSwitch($eFarm, 'btn-outline-primary').' ';
				}

				if(count($years) > 1) {
					$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#analyze-selling-search")').' class="btn btn-outline-primary">'.\Asset::icon('search').' '.s("Filtrer").'</a> ';
					$h .= '<a data-dropdown="bottom-end" class="btn btn-outline-primary dropdown-toggle">'.s("Comparer").'</a>';
					$h .=' <div class="dropdown-list">';
						foreach($years as $selectedYear => $sales) {
							if($year !== $selectedYear) {
								$h .= '<a href="'.\farm\FarmUi::urlAnalyzeSelling($eFarm, $year, \farm\Farmer::ITEM).'/compare/'.$selectedYear.LIME_REQUEST_ARGS.'" class="dropdown-item">'.s("avec {value}", $selectedYear).'</a>';
							}
						}
						if($cItemProductCompare->notEmpty()) {
							$h .= '<a href="'.\farm\FarmUi::urlAnalyzeSelling($eFarm, $year, \farm\Farmer::ITEM).LIME_REQUEST_ARGS.'" class="dropdown-item">'.s("ne plus comparer").'</a>';
						}
					$h .= '</div>';
				}
			$h .= '</div>';
		$h .= '</div>';

		if($cItemProduct->empty() and $cPlant->empty()) {

			$h = '<div class="util-empty">';
				$h .= s("Aucune vente sur des cultures en production n'a été enregistrée pour cette année.");
			$h .= '</div>';

			return $h;

		} else {

			$h .= $this->getSearch($search);

			$h .= '<div class="tabs-h" id="selling-analyze" onrender="'.encode('Lime.Tab.restore(this, "analyze-product")').'">';

				$h .= '<div class="tabs-item">';
					$h .= '<a class="tab-item selected" data-tab="analyze-product" onclick="Lime.Tab.select(this)">'.s("Par produit").'</a>';
					$h .= '<a class="tab-item" data-tab="analyze-plant" onclick="Lime.Tab.select(this)">'.s("Par espèce").'</a>';
				$h .= '</div>';

				if($yearCompare === NULL) {

					$h .= '<div class="tab-panel selected '.($monthly ? '' : 'analyze-chart-table').'" data-tab="analyze-product">';
						if($cItemProduct->empty()) {
							$h .= '<div class="util-empty">';
								$h .= s("Aucune vente de produit n'a été enregistrée pour cette année.");
							$h .= '</div>';
						} else {
							if($monthly === NULL) {
								$h .= $this->getBestProductsPie($cItemProduct);
							}
							$h .= $this->getBestProductsTable($cItemProduct, $year, cItemProductMonthly: $cItemProductMonthly, monthly: $monthly, search: $search, zoom: ($month === NULL and $week === NULL));
						}
					$h .= '</div>';

					$h .= '<div class="tab-panel '.($monthly ? '' : 'analyze-chart-table').'" data-tab="analyze-plant">';
						if($cPlant->empty()) {
							$h .= '<div class="util-empty">';
								$h .= s("Aucune vente sur une espèce n'a été enregistrée pour cette année.");
							$h .= '</div>';
						} else {
							if($monthly === NULL) {
								$h .= $this->getBestPlantsPie($cPlant);
							}
							$h .= $this->getBestPlantsTable($cPlant, $year, cccItemPlantMonthly: $cccItemPlantMonthly, monthly: $monthly, search: $search, zoom: ($month === NULL and $week === NULL));
						}
					$h .= '</div>';

				} else {

					$h .= '<div class="tab-panel selected" data-tab="analyze-product">';
						if($cItemProduct->empty()) {
							$h .= '<div class="util-empty">';
								$h .= s("Aucune vente de produit n'a été enregistrée pour cette année.");
							$h .= '</div>';
						} else {
							$h .= $this->getBestProductsTable($cItemProduct, $year, cItemProductCompare: $cItemProductCompare, yearCompare: $yearCompare);
						}
					$h .= '</div>';

					$h .= '<div class="tab-panel selected" data-tab="analyze-plant">';
						if($cPlant->empty()) {
							$h .= '<div class="util-empty">';
								$h .= s("Aucune vente sur une espèce n'a été enregistrée pour cette année.");
							$h .= '</div>';
						} else {
							$h .= $this->getBestPlantsTable($cPlant, $year, cPlantCompare: $cPlantCompare, yearCompare: $yearCompare);
						}
					$h .= '</div>';

				}

			$h .= '</div>';

		}

		return $h;

	}

	public function getBestProductsTable(\Collection $cItemProduct, ?int $year = NULL, \Collection $cItemProductMonthly = new \Collection(), ?string $monthly = NULL, \Collection $cItemProductCompare = new \Collection(), ?int $yearCompare = NULL, ?int $limit = NULL, ?\Search $search = NULL, bool $zoom = TRUE, bool $expand = TRUE, array $hide = [], ?string $moreTh = NULL, ?\Closure $moreTd = NULL): string {

		$search ??= new \Search()->sort(GET('sort'));
		$search->validateSort(['product', 'average', 'turnover', 'quantity'], 'turnover-');

		$cItemProduct->sort($search->buildSort([
			'product' => fn($direction) => [
				'product' => ['name' => $direction]
			]
		]));

		$compare = $cItemProductCompare->notEmpty();
		$totalTurnover = $cItemProduct->sum('turnover');

		$h = '<div class="'.($monthly ? 'util-overflow-lg' : 'util-overflow-xs').' stick-xs">';

			$monthlyClass = match($monthly) {
				'turnover' => 'analyze-month-table-5',
				'quantity' => 'analyze-month-table-5',
				'average' => 'analyze-month-table-4',
				NULL => '',
			};

			$h .= '<table class="tr-even analyze-values '.$monthlyClass.'">';

				$h.= '<thead>';

					if($compare === FALSE) {

						$h .= '<tr>';
							$h .= '<th>'.$search->linkSort('product', s("Produit")).'</th>';

							if($monthly === NULL or $monthly === 'turnover') {
								$h .= '<th class="text-center" colspan="2">';
									$h .= '<div class="analyze-month-th">';
										$h .= $search->linkSort('turnover', s("Ventes"), SORT_DESC);
										if($expand) {
											$h .= $this->getMonthlyLink($monthly, 'turnover');
										}
									$h .= '</div>';
								$h .= '</th>';
							}

							if($monthly === NULL or $monthly === 'quantity') {
								$h .= '<th class="text-center" colspan="2">';
									$h .= '<div class="analyze-month-th">';
										$h .= $search->linkSort('quantity', s("Volume"), SORT_DESC);
										if($expand) {
											$h .= $this->getMonthlyLink($monthly, 'quantity');
										}
									$h .= '</div>';
								$h .= '</th>';
							}

							if(
								in_array('average', $hide) === FALSE and
								($monthly === NULL or $monthly === 'average')
							) {
								$h .= '<th class="text-end">';
									$h .= '<div class="analyze-month-th">';
										$h .= $search->linkSort('average', s("Prix"), SORT_DESC);
										if($expand) {
											$h .= $this->getMonthlyLink($monthly, 'average');
										}
									$h .= '</div>';
								$h .= '</th>';
							}

							if($monthly) {
								for($month = 1; $month <= 12; $month++) {
									$h .= '<th class="text-center">'.\util\DateUi::getMonthName($month, type: 'short').'</th>';
								}
							}

							if($moreTh !== NULL) {
								$h .= $moreTh;
							}

							if($compare or $zoom) {
								$h .= '<th></th>';
							}

						$h .= '</tr>';

					} else {

						$h .= '<tr>';
							$h .= '<th rowspan="2">'.$search->linkSort('product', s("Produit")).'</th>';
							$h .= '<th class="text-center" colspan="4">'.$year.'</th>';
							$h .= '<th class="text-center color-muted" colspan="4">'.$yearCompare.'</th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<th class="text-end">'.$search->linkSort('turnover', s("Ventes"), SORT_DESC).'</th>';
							$h .= '<th class="text-center" colspan="2">'.$search->linkSort('quantity', s("Volume"), SORT_DESC).'</th>';
							$h .= '<th class="text-end">'.$search->linkSort('average', s("Prix"), SORT_DESC).'</th>';
							$h .= '<th class="text-end color-muted">'.s("Ventes").'</th>';
							$h .= '<th class="text-center color-muted" colspan="2">'.s("Volume").'</th>';
							$h .= '<th class="text-end color-muted">'.s("Prix").'</th>';
						$h .= '</tr>';

					}

				$h .= '</thead>';

				$again = $limit;

				$displayItem = function(Item $eItem, string $class = '') use($compare, $totalTurnover, $cItemProductMonthly, $monthly, $hide) {

					if($eItem->empty()) {
						$h = '<td class="text-end '.$class.'">/</td>';
						$h .= '<td class="text-end '.$class.'">/</td>';
						$h .= '<td style="padding-left: 0"></td>';
						$h .= '<td class="text-end '.$class.'">/</td>';
						return $h;
					}

					$turnover = fn($eItem) => \util\TextUi::money($eItem['turnover'], precision: 0);
					$quantity = fn($eItem) => ($eItem['quantity'] !== NULL) ? \util\TextUi::number(round($eItem['quantity']), 0) : '';
					$average = fn($eItem) => ($eItem['average'] !== NULL) ? \util\TextUi::money($eItem['average']) : '';

					$h = '';

					if($monthly === NULL or $monthly === 'turnover') {
						$h .= '<td class="text-end '.$class.'">';
							$h .= $turnover($eItem);
						$h .= '</td>';
						if($compare === FALSE) {
							$h .= '<td class="util-annotation '.$class.'">';
								if($totalTurnover > 0) {
									$h .= \util\TextUi::pc($eItem['turnover'] / $totalTurnover * 100);
								}
							$h .= '</td>';
						}
					}

					if($monthly === NULL or $monthly === 'quantity') {
						$h .= '<td class="text-end '.$class.'">';
							$h .= $quantity($eItem);
						$h .= '</td>';
						$h .= '<td class="'.$class.'" style="padding-left: 0">';
							if($eItem['unit']->notEmpty()) {
								$h .= \selling\UnitUi::getSingular($eItem['unit'], short: TRUE);
							}
						$h .= '</td>';
					}

					if(
						in_array('average', $hide) === FALSE and
						($monthly === NULL or $monthly === 'average')
					) {
						$h .= '<td class="text-end '.$class.'">';
							$h .= $average($eItem);
							if($monthly) {
								$h .= \selling\UnitUi::getBy($eItem['unit'], short: TRUE);
							}
						$h .= '</td>';
					}

					if($monthly) {

						if($eItem['product']['id'] !== NULL) {

							for($month = 1; $month <= 12; $month++) {

								$eItemMonthly = $cItemProductMonthly[$eItem['product']['id']][$month] ?? new Item();

								$h .= '<td class="text-end analyze-month-value">';
									if($eItemMonthly->notEmpty()) {
										$h .= match($monthly) {
											'turnover' => $turnover($eItemMonthly),
											'quantity' => $quantity($eItemMonthly),
											'average' => $average($eItemMonthly),
										};
									}
								$h .= '</td>';

							}

						} else {
							$h .= '<td class="text-end analyze-month-value analyze-empty" colspan="12"></td>';

						}

					}

					return $h;

				};

				$h .= '<tbody>';

					foreach($cItemProduct as $key => $eItem) {

						if($again !== NULL and $again-- === 0) {
							break;
						}

						$h .= '<tr>';
							$h .= '<td>';
								if($eItem['product']['id'] !== NULL) {
									$h .= ProductUi::getVignette($eItem['product'], '2rem').'&nbsp;&nbsp;'.ProductUi::link($eItem['product']);
								} else {
									$h .= encode($eItem['product']['name']);
								}
							$h .= '</td>';

							$h .= $displayItem($eItem);

							if($moreTd !== NULL) {
								$h .= $moreTd($eItem);
							}

							if($compare) {
								$h .= $displayItem($cItemProductCompare[$key] ?? new Item(), 'color-muted');
							} else if($zoom) {
								$h .= '<td class="td-min-content">';
									if($eItem['product']['id'] !== NULL) {
										$h .= '<a href="/selling/product:analyze?id='.$eItem['product']['id'].'&year='.$year.'&type='.$search->get('type').'" class="btn btn-outline-secondary">';
											$h .= \Asset::icon('search');
										$h .= '</a>';
									}
								$h .= '</td>';
							}

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		if($limit !== NULL and $cItemProduct->count() > $limit) {
			$h .= '<p class="util-info" style="margin-top: -1rem">';
				$h .= p("+ {value} autre produit moins vendu", "+ {value} autres produits moins vendus", $cItemProduct->count() - $limit);
			$h .= '</p>';
		}

		return $h;

	}

	protected function getMonthlyQuantity(?string $monthly, \Collection $cItem, string $class = ''): string {

		if($monthly !== NULL and $monthly !== 'quantity') {
			return '';
		}

		$quantity = fn($eItem) => \util\TextUi::number(round($eItem['quantity']), 0);

		$h = '<td class="text-end '.$class.'">';
			$position = 0;
			foreach($cItem as $eItem) {
				$h .= '<div style="'.(($position++ % 2) ? 'opacity: 0.66' : '').'">';
					$h .= $quantity($eItem);
				$h .= '</div>';
			}
		$h .= '</td>';
		$h .= '<td class="'.$class.'" style="padding-left: 0">';
			$position = 0;
			foreach($cItem as $eItem) {
				$h .= '<div style="'.(($position++ % 2) ? 'opacity: 0.66' : '').'">';
					$h .= \selling\UnitUi::getSingular($eItem['unit'], short: TRUE);
				$h .= '</div>';
			}
		$h .= '</td>';

		return $h;

	}

	protected function getMonthlyAverage(?string $monthly, \Collection $cItem, string $class = ''): string {

		if($monthly !== NULL and $monthly !== 'average') {
			return '';
		}

		$average = fn($eItem) => ($eItem['average'] !== NULL) ? \util\TextUi::money($eItem['average']) : '';

		$h = '<td class="text-end '.$class.'">';
			$position = 0;
			foreach($cItem as $eItem) {
				$h .= '<div style="'.(($position++ % 2) ? 'opacity: 0.66' : '').'">';
					$h .= $average($eItem);
					if($monthly) {
						$h .= \selling\UnitUi::getBy($eItem['unit'], short: TRUE);
					}
				$h .= '</div>';
			}
		$h .= '</td>';

		return $h;

	}

	public function getMonthlyLink(?string $monthly, string $value) {

		// C'est pas beau mais ça marche
		if(
			get_exists('week') or
			get_exists('month')
		) {
			return '';
		}

		if($monthly === $value) {
			$request = \util\HttpUi::removeArgument(LIME_REQUEST, 'monthly');
			return '<a href="'.$request.'" class="btn btn-xs btn-secondary" title="'.s("Revenir sur la vue annuelle").'">'.\Asset::icon('arrows-angle-contract').'</a>';
		} else {
			$request = \util\HttpUi::setArgument(LIME_REQUEST, 'monthly', $value);
			return '<a href="'.$request.'" class="btn btn-xs btn-secondary" title="'.s("Voir le détail mois par mois").'">'.\Asset::icon('arrows-angle-expand').'</a>';
		}

	}

	public function getBestProductsPie(\Collection $cItemProduct): string {

		return new \analyze\ChartUi()->buildPie(
			s("Répartition des ventes par produit"),
			$cItemProduct,
			'turnover',
			fn($eItemProduct) => $eItemProduct['product']->getName()
		);

	}

	protected function getBestPlantsTable(\Collection $cPlant, int $year, \Collection $cccItemPlantMonthly = new \Collection(), ?string $monthly = NULL, \Collection $cPlantCompare = new \Collection(), ?int $yearCompare = NULL, ?\Search $search = NULL, bool $zoom = TRUE): string {

		$search ??= new \Search()
			->sort(GET('sort'))
			->validateSort(['plant', 'turnover'], 'turnover-');

		$cPlant->sort($search->buildSort([
			'plant' => fn($direction) => [
				'name' => $direction
			]
		]));

		$compare = $cPlantCompare->notEmpty();
		$globalTurnover = $cPlant->sum('turnover');

		$h = '<div class="'.($monthly ? 'util-overflow-lg' : 'util-overflow-xs').' stick-xs">';

			$monthlyClass = match($monthly) {
				'turnover' => 'analyze-month-table-5',
				'quantity' => 'analyze-month-table-5',
				'average' => 'analyze-month-table-4',
				NULL => '',
			};

			$h .= '<table class="tr-even analyze-values '.$monthlyClass.'">';

				$h .= '<thead>';

					if($compare === FALSE) {

						$h .= '<tr>';
							$h .= '<th>'.$search->linkSort('plant', s("Espèce")).'</th>';

							if($monthly === NULL or $monthly === 'turnover') {
								$h .= '<th class="text-center" colspan="2">';
									$h .= '<div class="analyze-month-th">';
										$h .= $search->linkSort('turnover', s("Ventes"), SORT_DESC);
										$h .= $this->getMonthlyLink($monthly, 'turnover');
									$h .= '</div>';
								$h .= '</th>';
							}

							if($monthly === NULL or $monthly === 'quantity') {
								$h .= '<th class="text-center" colspan="2">';
									$h .= '<div class="analyze-month-th">';
										$h .= s("Volume");
										$h .= $this->getMonthlyLink($monthly, 'quantity');
									$h .= '</div>';
								$h .= '</th>';
							}

							if($monthly === NULL or $monthly === 'average') {
								$h .= '<th class="text-end">';
									$h .= '<div class="analyze-month-th">';
										$h .= s("Prix");
										$h .= $this->getMonthlyLink($monthly, 'average');
									$h .= '</div>';
								$h .= '</th>';
							}

							if($monthly) {
								for($month = 1; $month <= 12; $month++) {
									$h .= '<th class="text-center">'.\util\DateUi::getMonthName($month, type: 'short').'</th>';
								}
							}

							$h .= '<th></th>';
						$h .= '</tr>';

					} else {

						$h .= '<tr>';
							$h .= '<th rowspan="2">'.$search->linkSort('plant', s("Espèce")).'</th>';
							$h .= '<th class="text-center" colspan="4">'.$year.'</th>';
							$h .= '<th class="text-center color-muted" colspan="4">'.$yearCompare.'</th>';
						$h .= '</tr>';

						$h .= '<tr>';
							$h .= '<th class="text-end">'.$search->linkSort('turnover', s("Ventes"), SORT_DESC).'</th>';
							$h .= '<th class="text-center" colspan="2">'.s("Volume").'</th>';
							$h .= '<th class="text-end">'.s("Prix").'</th>';
							$h .= '<th class="text-end color-muted">'.s("Ventes").'</th>';
							$h .= '<th class="text-center color-muted" colspan="2">'.s("Volume").'</th>';
							$h .= '<th class="text-end color-muted">'.s("Prix").'</th>';
						$h .= '</tr>';

					}

				$h .= '</thead>';

				$displayItem = function(\plant\Plant $ePlant, \Collection $cItem, string $class = '') use($compare, $globalTurnover, $cccItemPlantMonthly, $monthly) {

					if($cItem->empty()) {
						$h = '<td class="text-end '.$class.'">/</td>';
						$h .= '<td class="text-end '.$class.'">/</td>';
						$h .= '<td style="padding-left: 0"></td>';
						$h .= '<td class="text-end '.$class.'">/</td>';
						return $h;
					}

					$itemTurnover = $cItem->sum('turnover');

					$turnover = fn(float $value) => \util\TextUi::money($value, precision: 0);
					$quantity = fn($eItem) => \util\TextUi::number(round($eItem['quantity']), 0);
					$average = fn($eItem) => \util\TextUi::money($eItem['average']);

					$h = '';

					if($monthly === NULL or $monthly === 'turnover') {
						$h .= '<td class="text-end '.$class.'">';
							$h .= $turnover($itemTurnover);
						$h .= '</td>';
						if($compare === FALSE) {
							$h .= '<td class="util-annotation '.$class.'">';
								if($globalTurnover > 0) {
									$h .= \util\TextUi::pc($itemTurnover / $globalTurnover * 100, 0);
								}
							$h .= '</td>';
						}
					}

					$h .= $this->getMonthlyQuantity($monthly, $cItem, $class);
					$h .= $this->getMonthlyAverage($monthly, $cItem, $class);

					if($monthly) {

						for($month = 1; $month <= 12; $month++) {

							$cItemMonthly = $cccItemPlantMonthly[$ePlant['id']][$month] ?? new \Collection();

							$h .= '<td class="text-end analyze-month-value">';

								if($cItemMonthly->notEmpty()) {

									switch($monthly) {

										case 'turnover' :
											$h .= $turnover($cItemMonthly->sum('turnover'));
											break;

										case 'quantity' :
										case 'average' :

											$position = 0;

											foreach($cItem as $eItem) {

												$eItemMonthly = $cItemMonthly[$eItem['unit']->empty() ? NULL : $eItem['unit']['id']] ?? new Item();

												$h .= '<div style="'.(($position++ % 2) ? 'opacity: 0.66' : '').'">';
													if($eItemMonthly->notEmpty()) {
														$h .= match($monthly) {
															'quantity' => $quantity($eItemMonthly),
															'average' => $average($eItemMonthly)
														};
													} else {
														$h .= '&nbsp;';
													}
												$h .= '</div>';

											}

											break;

									}

								}

							$h .= '</td>';

						}

					}

					return $h;

				};

				$h .= '<tbody>';

					foreach($cPlant as $key => $ePlant) {

						$h .= '<tr>';
							$h .= '<td>';
								$h .= \plant\PlantUi::getVignette($ePlant, '2rem').'&nbsp;&nbsp;'.\plant\PlantUi::link($ePlant);
							$h .= '</td>';

							$h .= $displayItem($ePlant, $ePlant['cItem']);

							if($compare) {
								$h .= $displayItem($ePlant, $cPlantCompare[$key]['cItem'] ?? new \Collection(), 'color-muted');
							} else {
								$h .= '<td class="td-min-content">';
									if($zoom) {
										$h .= '<a href="/plant/plant:analyzeSales?id='.$ePlant['id'].'&year='.$year.'&type='.$search->get('type').'" class="btn btn-outline-secondary">';
											$h .= \Asset::icon('search');
										$h .= '</a>';
									}
								$h .= '</td>';
							}

						$h .= '</tr>';

					}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getBestPlantsPie(\Collection $cPlant): string {

		return new \analyze\ChartUi()->buildPie(
			s("Répartition des ventes"),
			$cPlant,
			'turnover',
			fn($ePlant) => $ePlant['name']
		);

	}

	public function getCustomer(Customer $e, int $year, \Collection $cSaleTurnover, \Collection $cItemProduct, \Collection $cItemMonth, \Collection $cItemMonthBefore, \Collection $cItemWeek, \Collection $cItemWeekBefore): \Panel {

		$h = $this->getCustomerTurnover($cSaleTurnover, $year, $e, inPanel: TRUE);

		$h .= '<div class="util-title">';
			$h .= '<h3>'.s("Produits les plus vendus").'</h3>';
			$h .= '<div>';
				if($cItemProduct->contains(fn($eItemProduct) => $eItemProduct['containsComposition'] or $eItemProduct['containsIngredient'])) {
					$h .= SaleUi::getCompositionSwitch($e['farm'], 'btn-outline-primary').' ';
				}
			$h .= '</div>';
		$h .= '</div>';
		$h .= '<div class="analyze-chart-table">';
			$h .= $this->getBestProductsTable($cItemProduct, $year, limit: 30, expand: FALSE);
			$h .= $this->getBestProductsPie($cItemProduct);
		$h .= '</div>';

		$h .= '<h3>'.s("Ventes mensuelles").'</h3>';
		$h .= '<div class="analyze-chart-table">';
			$h .= $this->getPeriodMonthTable($cItemMonth, $e);
			if($cItemMonthBefore->notEmpty()) {
				$h .= $this->getDoublePeriodMonthChart('turnover', $cItemMonth, $year, $cItemMonthBefore, $year - 1);
			} else {
				$h .= $this->getPeriodMonthChart('turnover', $cItemMonth);
			}
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<h3>'.s("Ventes hebdomadaires").'</h3>';;
		if($cItemMonthBefore->notEmpty()) {
			$h .= $this->getDoublePeriodWeekChart('turnover', $cItemWeek, $year, $cItemWeekBefore, $year - 1);
		} else {
			$h .= $this->getPeriodWeekChart('turnover', $cItemWeek);
		}

		return new \Panel(
			id: 'panel-customer-analyze',
			title: s("{value} en {year}", ['value' => encode($e['name']), 'year' => $year]),
			body: $h,
		);

	}

	public function getCustomerTurnover(\Collection $cSaleTurnover, ?int $year, Customer $eCustomer, bool $inPanel = FALSE): string {

		$h = '<ul class="util-summarize mb-2">';

			foreach($cSaleTurnover as $eSaleTurnover) {
				$h .= '<li '.($eSaleTurnover['year'] === $year ? 'class="selected"' : '').'>';
					if($inPanel) {
						$h .= '<a data-ajax="/selling/customer:analyze?id='.$eCustomer['id'].'&year='.$eSaleTurnover['year'].'" data-ajax-method="get">';
					} else {
						$h .= '<a href="/selling/customer:analyze?id='.$eCustomer['id'].'&year='.$eSaleTurnover['year'].'">';
					}
						$h .= '<h5>'.$eSaleTurnover['year'].'</h5>';
						$h .= '<div>'.\util\TextUi::money($eSaleTurnover['turnover'], precision: 0).'</div>';
						if($eSaleTurnover['turnoverGlobal'] > 0) {
							$h .= '<div class="util-summarize-muted">('.\util\TextUi::pc($eSaleTurnover['turnover'] / $eSaleTurnover['turnoverGlobal'] * 100, 0).')</div>';
						}
					$h .= '</a>';
				$h .= '</li>';
			}

		$h .= '</ul>';

		return $h;

	}

	public function getPlantSales(\plant\Plant $e, bool $switchComposition, int $year, \Collection $cItemTurnover, \Collection $cItemYear, \Collection $cItemCustomer, \Collection $cItemType, \Collection $cItemMonth, \Collection $cItemMonthBefore, \Collection $cItemWeek, $cItemWeekBefore, \Search $search): \Panel {

		$h = '';

		if($cItemYear->notEmpty()) {

			$h .= $this->getCustomerTitle($search);

			$h .= $this->getPlantTurnover($cItemYear, $year, $e, $search);

			if($cItemCustomer->offsetExists($year)) {

				if($e['farm']->canPersonalData()) {

					$h .= '<h3>'.s("Ventes par client").'</h3>';
					$h .= '<div class="analyze-chart-table">';
						$h .= $this->getBestCustomersPie($cItemCustomer, $year);
						$h .= $this->getBestCustomersByPlantTable($e, $cItemCustomer, $search->isFiltered('type') ? new \Collection() : $cItemType, $year, 10);
					$h .= '</div>';

					$h .= '<br/>';

				}

				if($cItemTurnover->count() > 1) {

					$h .= '<h3>'.s("Ventes par produit").'</h3>';
					$h .= '<div class="analyze-chart-table">';
						$h .= $this->getBestProductsPie($cItemTurnover);
						$h .= $this->getBestProductsByPlantTable($e, $cItemTurnover, $year);
					$h .= '</div>';

					$h .= '<br/>';

				}

				$h .= '<h3>'.s("Ventes mensuelles").'</h3>';
				$h .= '<div class="analyze-chart-table">';
					$h .= $this->getPeriodMonthTable($cItemMonth, $e);
					if($cItemMonthBefore->notEmpty()) {
						$h .= $this->getDoublePeriodMonthChart('turnover', $cItemMonth, $year, $cItemMonthBefore, $year - 1);
					} else {
						$h .= $this->getPeriodMonthChart('turnover', $cItemMonth);
					}
				$h .= '</div>';

				$h .= '<br/>';

				$h .= '<h3>'.s("Ventes hebdomadaires").'</h3>';
				if($cItemMonthBefore->notEmpty()) {
					$h .= $this->getDoublePeriodWeekChart('turnover', $cItemWeek, $year, $cItemWeekBefore, $year - 1);
				} else {
					$h .= $this->getPeriodWeekChart('turnover', $cItemWeek);
				}

			} else {
				$h .= '<p class="util-empty">';
					$h .= s("Il n'y a aucune vente pour ce produit en {value}.", $year);
				$h .= '</p>';
			}

		} else {

			$h .= '<p class="util-empty">';
				$h .= s("Vous n'avez encore jamais vendu la production de cette plante.");
			$h .= '</p>';

		}

		$title = s("{value} en {year}", ['value' => encode($e['name']), 'year' => $year]);
		$product = '<h2 class="panel-title">'.\plant\PlantUi::getVignette($e, '3rem').' '.$title.'</h2>';

		if($switchComposition) {
			$header = '<div style="display: flex; justify-content: space-between; align-items: center">';
				$header .= $product;
				$header .= SaleUi::getCompositionSwitch($e['farm'], 'btn-outline-primary');
			$header .= '</div>';
		} else {
			$header = $product;
		}

		return new \Panel(
			id: 'panel-plant-analyze',
			documentTitle: $title,
			body: $h,
			header: '<h4>'.s("ESPÈCE CULTIVÉE").'</h4>'.$header,
		);

	}

	public function getCustomerTitle(\Search $search): string {

			$types = [
				NULL => s("Clients particuliers et professionnels"),
				Customer::PRIVATE => s("Clients particuliers"),
				Customer::PRO => s("Clients professionnels")
			];

			$h = '<h3 class="mb-1">';
				$h .= '<a data-dropdown="bottom-start" style="color: var(--text)" class="dropdown-toggle">'.$types[$search->get('type')].'</a>';
				$h .= '<div class="dropdown-list bg-primary">';
					foreach($types as $type => $label) {
						$h .= '<a href="'.\util\HttpUi::setArgument(LIME_REQUEST, 'type', $type, FALSE).'" class="dropdown-item">'.$label.'</a>';
					}
				$h .= '</div>';
			$h .= '</h3>';

			return $h;

	}

	public function getPlantTurnover(\Collection $cItemYear, ?int $year, ?\plant\Plant $ePlantLink, \Search $search = new \Search()): string {

		$h = '<ul class="util-summarize mb-3">';

			foreach($cItemYear as $eItemYear) {
				$h .= '<li '.($eItemYear['year'] === $year ? 'class="selected"' : '').'>';
					$h .= '<a href="/plant/plant:analyzeSales?id='.$ePlantLink['id'].'&year='.$eItemYear['year'].''.($search->get('type') ? '&type='.encode($search->get('type')) : '').'" data-ajax-method="get">';
						$h .= '<h5>'.$eItemYear['year'].'</h5>';
						$h .= '<div>'.\util\TextUi::money($eItemYear['turnover'], precision: 0).'</div>';
						$h .= '<div class="util-summarize-muted">('.\util\TextUi::pc($eItemYear['turnover'] / $eItemYear['turnoverGlobal'] * 100, 0).')</div>';
					$h .= '</a>';
				$h .= '</li>';
			}

		$h .= '</ul>';

		return $h;

	}

	public function getProduct(Product $e, bool $switchComposition, int $year, \Collection $cItemYear, \Collection $cItemCustomer, \Collection $cItemType, \Collection $cItemMonth, \Collection $cItemMonthBefore, \Collection $cItemWeek, \Collection $cItemWeekBefore, \Search $search = new \Search()): \Panel {

		$h = '';

		if($e['status'] === Product::DELETED) {
			$h .= '<div class="util-danger mb-1">'.s("Ce produit a été supprimé et les statistiques ne sont disponible qu'à titre historique.").'</div>';
		}

		if($cItemYear->notEmpty()) {

			$h .= $this->getCustomerTitle($search);

			$h .= $this->getProductYear($cItemYear, $year, $e, $search);

			if($cItemCustomer->offsetExists($year)) {

				if($e['farm']->canPersonalData()) {

					$h .= '<h3>'.s("Ventes par client").'</h3>';
					$h .= '<div class="analyze-chart-table">';
						$h .= $this->getBestCustomersPie($cItemCustomer, $year);
						$h .= $this->getBestCustomersByProductTable($e, $cItemCustomer, $search->isFiltered('type') ? new \Collection() : $cItemType, $year, 10);
					$h .= '</div>';

				}

				$h .= '<h3>'.s("Ventes mensuelles").'</h3>';
				$h .= '<div class="analyze-chart-table">';
					$h .= $this->getPeriodMonthTable($cItemMonth, $e);
					if($cItemMonthBefore->notEmpty()) {
						$h .= $this->getDoublePeriodMonthChart($e['farm']->getView('viewAnalyzeChart'), $cItemMonth, $year, $cItemMonthBefore, $year - 1, $e['farm']);
					} else {
						$h .= $this->getPeriodMonthChart($e['farm']->getView('viewAnalyzeChart'), $cItemMonth, $e['farm']);
					}
				$h .= '</div>';

				$h .= '<br/>';

				$h .= '<h3>'.s("Ventes hebdomadaires").'</h3>';
				if($cItemMonthBefore->notEmpty()) {
					$h .= $this->getDoublePeriodWeekChart($e['farm']->getView('viewAnalyzeChart'), $cItemWeek, $year, $cItemWeekBefore, $year - 1, $e['farm']);
				} else {
					$h .= $this->getPeriodWeekChart($e['farm']->getView('viewAnalyzeChart'), $cItemWeek);
				}

			} else {
				$h .= '<p class="util-empty">';
					$h .= s("Il n'y a aucune vente pour ce produit en {value}.", $year);
				$h .= '</p>';
			}

		} else {

			$h .= '<p class="util-empty">';
				$h .= s("Vous n'avez encore jamais vendu ce produit.");
			$h .= '</p>';

		}

		$title = s("{value} en {year}", ['value' => encode($e['name']), 'year' => $year]);
		$product = '<h2 class="panel-title">'.ProductUi::getVignette($e, '3rem').' '.$title.'</h2>';

		if($switchComposition) {
			$header = '<div style="display: flex; justify-content: space-between; align-items: center">';
				$header .= $product;
				$header .= SaleUi::getCompositionSwitch($e['farm'], 'btn-outline-primary');
			$header .= '</div>';
		} else {
			$header = $product;
		}

		return new \Panel(
			id: 'panel-product-analyze',
			documentTitle: $title,
			body: $h,
			header: '<h4>'.s("PRODUIT").'</h4>'.$header,
		);

	}

	public function getProductYear(\Collection $cItemYear, ?int $year, ?Product $eProductLink, \Search $search = new \Search()): string {

		$h = '<ul class="util-summarize mb-3">';

			foreach($cItemYear as $eItemYear) {
				$h .= '<li '.($eItemYear['year'] === $year ? 'class="selected"' : '').'>';
					$h .= '<a href="/selling/product:analyze?id='.$eProductLink['id'].'&year='.$eItemYear['year'].''.($search->get('type') ? '&type='.encode($search->get('type')) : '').'">';
						$h .= '<h5>'.$eItemYear['year'].'</h5>';
						$h .= '<div>'.\util\TextUi::money($eItemYear['turnover'], precision: 0).'</div>';
						if($eItemYear['turnoverGlobal'] > 0) {
							$h .= '<div class="util-summarize-muted">('.\util\TextUi::pc($eItemYear['turnover'] / $eItemYear['turnoverGlobal'] * 100, 0).')</div>';
						}
					$h .= '</a>';
				$h .= '</li>';
			}

		$h .= '</ul>';

		return $h;

	}

	public function getSearch(\Search $search): string {

		$h = '<div id="analyze-selling-search" class="util-block-search '.($search->empty() ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'class' => 'util-search']);

				if(get_exists('month')) {
					$h .= $form->hidden('month', GET('month'));
				}

				if(get_exists('week')) {
					$h .= $form->hidden('week', GET('week'));
				}

				$h .= '<fieldset>';
					$h .= '<legend>'.s("Type de clients").'</legend>';
					$h .= $form->select('type', [
						Customer::PRIVATE => s("Clients particuliers"),
						Customer::PRO => s("Clients professionnels"),
					], $search->get('type'), ['placeholder' => s("Tous")]);
				$h .= '</fieldset>';


				$h .= '<div class="util-search-submit">';
					$h .= $form->submit(s("Chercher"));
					$h .= '<a href="'.$url.'" class="btn">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getExportInvoicesHeader(\farm\Farm $eFarm, array $vatRates): array {

		$header = [
			'invoice_number',
			'customer_name',
			'customer_siret',
			'customer_vat',
			'date',
			'due_date',
			'payment_method',
			'payment_status'
		];

		if($eFarm->getConf('hasVat')) {
			$header[] = 'amount_excluding_vat';
			foreach($vatRates as $vatRate) {
				$header[] = 'vat_'.$vatRate;
			}
			$header[] = 'amount_including_vat';
		} else {
			$header[] = 'amount';
		}

		$header[] = 'url';

		return $header;

	}

	public function getExportSalesHeader(\farm\Farm $eFarm): array {

		$header = [
			'sale_id',
			'customer_name',
			'type',
			'delivery',
			'items',
			'shop',
			'payment_method'
		];

		if($eFarm->getConf('hasVat')) {
			$header[] = 'amount_excluding_vat';
			$header[] = 'vat';
			$header[] = 'amount_including_vat';
		} else {
			$header[] = 'amount';
		}

		return $header;

	}

	public function getExportItemsHeader(\farm\Farm $eFarm): array {

		$header = [
			'sale_id',
			'item_id',
			'label',
			'product_id',
			'product_type',
		];

		if($eFarm->hasAccounting()) {
			$header[] = 'product_account';
		}

		$header = array_merge($header, [
			'customer_name',
			'type',
			'delivery',
			'quantity',
			'unit'
		]);

		if($eFarm->getConf('hasVat')) {
			$header[] = 'amount_excluding_vat';
			$header[] = 'vat';
			$header[] = 'amount_including_vat';
		} else {
			$header[] = 'amount';
		}

		return $header;

	}

	public function getExportProductsHeader(\farm\Farm $eFarm): array {

		return [
			'type',
			'name',
			'reference',
			'unit',
			'price_private',
			'price_pro',
			'vat_rate',
			'additional',
			'origin',
			'quality',
			'species',
			'variety',
			'frozen',
			'packaging',
			'composition',
			'allergen'
		];

	}

	public function getExportCustomersHeader(\farm\Farm $eFarm): array {

		return [
			'type',
			'private_first_name',
			'private_last_name',
			'pro_commercial_name',
			'pro_legal_name',
			'account',
			'email',
			'phone',
			'groups',
			'pro_contact_name',
			'pro_siret',
			'pro_vat',
			'invoice_street_1',
			'invoice_street_2',
			'invoice_postcode',
			'invoice_city',
			'invoice_country',
			'delivery_street_1',
			'delivery_street_2',
			'delivery_postcode',
			'delivery_city',
			'delivery_country',
			'discount',
			'opt_in',
		];

	}

}
?>
