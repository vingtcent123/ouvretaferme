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

	public function getPeriod(int $year, \Collection $cItemMonth, \Collection $cItemMonthBefore, \Collection $cItemWeek, \Collection $cItemWeekBefore, string $tag = 'h2'): string {

		if($cItemMonth->empty()) {

			$h = '<div class="util-info">';
				$h .= s("Aucune vente n'a été enregistrée pour cette année.");
			$h .= '</div>';

			return $h;

		}

		$h = '<h2>'.s("Ventes mensuelles").'</h2>';
		$h .= '<div class="analyze-chart-table">';
			$h .= $this->getPeriodMonthTable($cItemMonth);
			if($cItemMonthBefore->notEmpty()) {
				$h .= $this->getDoublePeriodMonthChart($cItemMonth, $year, $cItemMonthBefore, $year - 1);
			} else {
				$h .= $this->getPeriodMonthChart($cItemMonth);
			}
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<'.$tag.'>'.s("Ventes hebdomadaires").'</'.$tag.'>';
		if($cItemMonthBefore->notEmpty()) {
			$h .= $this->getDoublePeriodWeekChart($cItemWeek, $year, $cItemWeekBefore, $year - 1);
		} else {
			$h .= $this->getPeriodWeekChart($cItemWeek);
		}

		return $h;

	}

	protected function getPeriodMonthTable(\Collection $cItemMonth, Product|\plant\Plant|Customer|null $e = NULL): string {

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
						if($e === NULL) {
							$h .= '<th class="text-end color-private">'.s("Dont<br/>particuliers").'</th>';
							$h .= '<th></th>';
							$h .= '<th class="color-pro">'.s("Dont<br/>pros").'</th>';
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
							$h .= '<td class="util-annotation">';
								if($turnover !== NULL) {
									$h .= ($totalTurnover > 0 ? \util\TextUi::pc($turnover / $totalTurnover * 100) : '-');
								}
							$h .= '</td>';

							if($e === NULL) {

								$h .= '<td class="text-end color-private">';
									if($turnoverPrivate !== NULL) {
										$h .= \util\TextUi::money($turnoverPrivate, precision: 0);
									} else {
										$h .= '-';
									}
								$h .= '</td>';
								$h .= '<td class="td-min-content">';
									if($turnover !== NULL) {

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
										$h .= \main\UnitUi::getValue(round($eItemMonth['quantity']), $e['unit'], short: TRUE);
									$h .= '</td>';
									$h .= '<td class="text-end">';
										$h .= \util\TextUi::money($turnover / $eItemMonth['quantity']);
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
						$h .= '<td class="util-annotation">';
							if($turnover !== NULL) {
								$h .= \util\TextUi::pc($turnover / $totalTurnover * 100, 0);
							}
						$h .= '</td>';

						if($e === NULL) {

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
								$h .= \main\UnitUi::getValue(round($totalQuantity), $e['unit'], short: TRUE);
							$h .= '</td>';
							$h .= '<td class="text-end">';
								$h .= \util\TextUi::money($totalTurnover / $totalQuantity);
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

	public function getPeriodMonthChart(\Collection $cItemMonth): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		[$turnovers, $labels] = $this->extractMonthChartValues($cItemMonth);

		$h = '<div class="analyze-bar">';
			$h .= '<canvas '.attr('onrender', 'Analyze.createBar(this, "'.s("Ventes").'", '.json_encode($turnovers).', '.json_encode($labels).')').'</canvas>';
		$h .= '</div>';

		return $h;

	}

	public function getDoublePeriodMonthChart(\Collection $cItemMonthNow, int $yearNow, \Collection $cItemMonthBefore, int $yearBefore): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		[$turnoversNow, $labelsNow] = $this->extractMonthChartValues($cItemMonthNow);
		[$turnoversBefore] = $this->extractMonthChartValues($cItemMonthBefore);

		$h = '<div class="analyze-bar">';
			$h .= '<canvas '.attr('onrender', 'Analyze.createDoubleBar(this, "'.s("Ventes {value}", $yearNow).'", '.json_encode($turnoversNow).', "'.s("Ventes {value}", $yearBefore).'", '.json_encode($turnoversBefore).', '.json_encode($labelsNow).')').'</canvas>';
		$h .= '</div>';

		return $h;

	}

	protected function extractMonthChartValues(\Collection $cItemMonth): array {

		$turnovers = [];
		$labels = [];

		for($month = 1; $month <= 12; $month++) {

			if($cItemMonth->offsetExists($month)) {

				$eItemMonth = $cItemMonth[$month];

				$turnovers[] = round($eItemMonth['turnover']);

			} else {
				$turnovers[] = 0;
			}

			$labels[] = \util\DateUi::getMonthName($month, type: 'short');

		}

		return [$turnovers, $labels];

	}

	public function getPeriodWeekChart(\Collection $cItemWeek, Product|\plant\Plant|null $e = NULL): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		[$turnovers, $labels, $quantities] = $this->extractWeekChartValues($cItemWeek, $e);

		$h = '<div class="analyze-bar">';
			if($e instanceof Product) {
				$h .= '<canvas '.attr('onrender', 'Analyze.createBarLine(this, "'.s("Ventes").'", '.json_encode($turnovers).', "'.s("Volume").'", '.json_encode($quantities).', '.json_encode($labels).')').'</canvas>';
			} else {
				$h .= '<canvas '.attr('onrender', 'Analyze.createBar(this, "'.s("Ventes").'", '.json_encode($turnovers).', '.json_encode($labels).')').'</canvas>';
			}
		$h .= '</div>';

		return $h;

	}

	public function getDoublePeriodWeekChart(\Collection $cItemWeekNow, int $yearNow, \Collection $cItemWeekBefore, int $yearBefore): string {

		\Asset::jsUrl('https://cdn.jsdelivr.net/npm/chart.js');

		[$turnoversNow, $labelsNow] = $this->extractWeekChartValues($cItemWeekNow);
		[$turnoversBefore] = $this->extractWeekChartValues($cItemWeekBefore);

		$h = '<div class="analyze-bar">';
			$h .= '<canvas '.attr('onrender', 'Analyze.createDoubleBar(this, "'.s("Ventes {value}", $yearNow).'", '.json_encode($turnoversNow).', "'.s("Ventes {value}", $yearBefore).'", '.json_encode($turnoversBefore).', '.json_encode($labelsNow).')').'</canvas>';
		$h .= '</div>';

		return $h;

	}

	protected function extractWeekChartValues(\Collection $cItemWeek, Product|\plant\Plant|null $e = NULL): array {

		$turnovers = [];
		$quantities = [];
		$labels = [];

		for($week = 1; $week <= 52; $week++) {

			if($cItemWeek->offsetExists($week)) {

				$eItemWeek = $cItemWeek[$week];

				$turnovers[] = round($eItemWeek['turnover']);
				$quantities[] = ($e instanceof Product) ? $eItemWeek['quantity'] : NULL;

			} else {
				$turnovers[] = 0;
				$quantities[] = NULL;
			}

			$labels[] = $week;

		}

		return [$turnovers, $labels, $quantities];

	}

	public function getBestCustomers(\Collection $ccItemCustomer, \Collection $ccItemCustomerMonthly, int $year, ?int $month, ?string $week, ?string $monthly, \Search $search): string {

		if($ccItemCustomer->offsetExists($year) === FALSE) {

			$h = '<div class="util-info">';
				$h .= s("Aucune vente n'a été enregistrée pour cette année.");
			$h .= '</div>';

			return $h;

		}
		$h = '<div class="util-action">';
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

		$search ??= (new \Search())
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
							$h .= \util\TextUi::pc($eItem['turnover'] / $turnover * 100, 0);
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
										$h .= \util\TextUi::pc($eItemBefore['turnover'] / $turnoverBefore * 100, 0);
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

		$line = function($eItem, $eItemBefore) use ($eProduct, $turnover, $turnoverBefore) {

			$h = '<td class="text-end">';
				$h .= \util\TextUi::money($eItem['turnover'], precision: 0);
			$h .= '</td>';
			$h .= '<td class="util-annotation">';
				$h .= \util\TextUi::pc($eItem['turnover'] / $turnover * 100, 0);
			$h .= '</td>';

			if($eItemBefore->notEmpty()) {
				$h .= '<td class="text-end">';
					$h .= \util\TextUi::money($eItemBefore['turnover'], precision: 0);
				$h .= '</td>';
				$h .= '<td class="util-annotation">';
					$h .= \util\TextUi::pc($eItemBefore['turnover'] / $turnoverBefore * 100, 0);
				$h .= '</td>';
			} else {
				$h .= '<td class="text-end">-</td>';
				$h .= '<td></td>';
			}

			$h .= '<td class="text-end">';
				$h .= \main\UnitUi::getValue(round($eItem['quantity']), $eProduct['unit'], short: TRUE);
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
										Customer::PRO => s("Clients pros")
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

		$line = function($eItem, $eItemBefore) use ($ePlant, $turnover, $turnoverBefore) {

			$h = '<td class="text-end">';
				$h .= \util\TextUi::money($eItem['turnover'], precision: 0);
			$h .= '</td>';
			$h .= '<td class="util-annotation">';
				$h .= ($turnover > 0 ? \util\TextUi::pc($eItem['turnover'] / $turnover * 100) : '-');
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
										Customer::PRO => s("Clients pros")
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

	public function getBestCustomersPie(\Collection $ccItemCustomer, int $year): string {

		$cItemCustomer = $ccItemCustomer[$year];

		return (new \analyze\ChartUi())->buildPie(
			s("Répartition des ventes en {value}", $year),
			$cItemCustomer,
			'turnover',
			fn($eTimesheet) => $eTimesheet['customer']['name']
		);

	}

	public function getShop(\farm\Farm $eFarm, \Collection $cShop, \shop\Shop $eShopSelected, \Collection $cSaleTurnover, \Collection $cItemProduct, \Collection $cItemProductMonthly, \Collection $cPlant, \Collection $cccItemPlantMonthly, \Collection $ccItemCustomer, int $year, ?string $monthly): string {

		$h = '<h2>';
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

		$h .= $this->getShopTurnover($eFarm, $eShopSelected, $cSaleTurnover, $year);

		if($cItemProduct->empty() and $cPlant->empty()) {

			$h .= '<div class="util-info">';
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
						$h = '<div class="util-info">';
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
						$h = '<div class="util-info">';
							$h .= s("Aucune vente sur une espèce cultivée n'a été enregistrée pour cette année.");
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
						$h = '<div class="util-info">';
							$h .= s("Aucune client n'a été enregistré pour cette année.");
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

		$h = '<div class="util-action">';
			$h .= '<h2>'.s("Meilleures ventes").'</h2>';
			$h .= '<div>';
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

			$h = '<div class="util-info">';
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
							$h = '<div class="util-info">';
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
							$h = '<div class="util-info">';
								$h .= s("Aucune vente sur une espèce cultivée n'a été enregistrée pour cette année.");
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
							$h = '<div class="util-info">';
								$h .= s("Aucune vente de produit n'a été enregistrée pour cette année.");
							$h .= '</div>';
						} else {
							$h .= $this->getBestProductsTable($cItemProduct, $year, cItemProductCompare: $cItemProductCompare, yearCompare: $yearCompare);
						}
					$h .= '</div>';

					$h .= '<div class="tab-panel selected" data-tab="analyze-plant">';
						if($cPlant->empty()) {
							$h = '<div class="util-info">';
								$h .= s("Aucune vente sur une espèce cultivée n'a été enregistrée pour cette année.");
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

	protected function getBestProductsTable(\Collection $cItemProduct, int $year, \Collection $cItemProductMonthly = new \Collection(), ?string $monthly = NULL, \Collection $cItemProductCompare = new \Collection(), ?int $yearCompare = NULL, ?int $limit = NULL, ?\Search $search = NULL, bool $zoom = TRUE): string {

		$search ??= (new \Search())->sort(GET('sort'));
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
										$h .= $this->getMonthlyLink($monthly, 'turnover');
									$h .= '</div>';
								$h .= '</th>';
							}

							if($monthly === NULL or $monthly === 'quantity') {
								$h .= '<th class="text-center" colspan="2">';
									$h .= '<div class="analyze-month-th">';
										$h .= $search->linkSort('quantity', s("Volume"), SORT_DESC);
										$h .= $this->getMonthlyLink($monthly, 'quantity');
									$h .= '</div>';
								$h .= '</th>';
							}

							if($monthly === NULL or $monthly === 'average') {
								$h .= '<th class="text-end">';
									$h .= '<div class="analyze-month-th">';
										$h .= $search->linkSort('average', s("Prix"), SORT_DESC);
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

				$displayItem = function(Item $eItem, string $class = '') use ($compare, $totalTurnover, $cItemProductMonthly, $monthly) {

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
								$h .= \util\TextUi::pc($eItem['turnover'] / $totalTurnover * 100);
							$h .= '</td>';
						}
					}

					if($monthly === NULL or $monthly === 'quantity') {
						$h .= '<td class="text-end '.$class.'">';
							$h .= $quantity($eItem);
						$h .= '</td>';
						$h .= '<td class="'.$class.'" style="padding-left: 0">';
							if($eItem['unit'] !== NULL) {
								$h .= \main\UnitUi::getSingular($eItem['unit'], short: TRUE);
							}
						$h .= '</td>';
					}

					if($monthly === NULL or $monthly === 'average') {
						$h .= '<td class="text-end '.$class.'">';
							$h .= $average($eItem);
							if($monthly and $eItem['unit'] !== NULL) {
								$h .= ' / '.\main\UnitUi::getSingular($eItem['unit'], short: TRUE, by: TRUE);
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

							if($compare) {
								$h .= $displayItem($cItemProductCompare[$key] ?? new Item(), 'color-muted');
							} else {
								$h .= '<td class="td-min-content">';
									if($zoom and $eItem['product']['id'] !== NULL) {
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
					$h .= \main\UnitUi::getSingular($eItem['unit'], short: TRUE);
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
						$h .= ' / '.\main\UnitUi::getSingular($eItem['unit'], short: TRUE, by: TRUE);
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

		return (new \analyze\ChartUi())->buildPie(
			s("Répartition des ventes en valeur"),
			$cItemProduct,
			'turnover',
			fn($eTimesheet) => $eTimesheet['product']->getName()
		);

	}

	protected function getBestPlantsTable(\Collection $cPlant, int $year, \Collection $cccItemPlantMonthly = new \Collection(), ?string $monthly = NULL, \Collection $cPlantCompare = new \Collection(), ?int $yearCompare = NULL, ?\Search $search = NULL, bool $zoom = TRUE): string {

		$search ??= (new \Search())
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

				$displayItem = function(\plant\Plant $ePlant, \Collection $cItem, string $class = '') use ($compare, $globalTurnover, $cccItemPlantMonthly, $monthly) {

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
								$h .= \util\TextUi::pc($itemTurnover / $globalTurnover * 100, 0);
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

												$eItemMonthly = $cItemMonthly[$eItem['unit']] ?? new Item();

												$h .= '<div style="'.(($position++ % 2) ? 'opacity: 0.66' : '').'">';
													if($eItemMonthly->notEmpty()) {
														$h .= match($monthly) {
															'quantity' => $quantity($eItemMonthly),//.' / '.\main\UnitUi::getSingular($eItem['unit'], short: TRUE),
															'average' => $average($eItemMonthly)//.'<small class="color-muted"> / '.\main\UnitUi::getSingular($eItem['unit'], short: TRUE).'</small>',
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

		return (new \analyze\ChartUi())->buildPie(
			s("Répartition des ventes en valeur"),
			$cPlant,
			'turnover',
			fn($ePlant) => $ePlant['name']
		);

	}

	public function getCustomer(Customer $e, int $year, \Collection $cSaleTurnover, \Collection $cItemProduct, \Collection $cItemMonth, \Collection $cItemMonthBefore, \Collection $cItemWeek, \Collection $cItemWeekBefore): \Panel {

		$h = $this->getCustomerTurnover($cSaleTurnover, $year, $e, inPanel: TRUE);

		$h .= '<h3>'.s("Produits les plus vendus").'</h3>';
		$h .= '<div class="analyze-chart-table">';
			$h .= $this->getBestProductsTable($cItemProduct, $year, limit: 30);
			$h .= $this->getBestProductsPie($cItemProduct);
		$h .= '</div>';

		$h .= '<h3>'.s("Ventes mensuelles").'</h3>';
		$h .= '<div class="analyze-chart-table">';
			$h .= $this->getPeriodMonthTable($cItemMonth, $e);
			if($cItemMonthBefore->notEmpty()) {
				$h .= $this->getDoublePeriodMonthChart($cItemMonth, $year, $cItemMonthBefore, $year - 1);
			} else {
				$h .= $this->getPeriodMonthChart($cItemMonth);
			}
		$h .= '</div>';

		$h .= '<br/>';

		$h .= '<h3>'.s("Ventes hebdomadaires").'</h3>';;
		if($cItemMonthBefore->notEmpty()) {
			$h .= $this->getDoublePeriodWeekChart($cItemWeek, $year, $cItemWeekBefore, $year - 1);
		} else {
			$h .= $this->getPeriodWeekChart($cItemWeek);
		}

		return new \Panel(
			id: 'panel-customer-analyze',
			title: s("{value} en {year}", ['value' => encode($e['name']), 'year' => $year]),
			body: $h,
		);

	}

	public function getCustomerTurnover(\Collection $cSaleTurnover, ?int $year, Customer $eCustomer, bool $inPanel = FALSE): string {

		$h = '<ul class="util-summarize">';

			foreach($cSaleTurnover as $eSaleTurnover) {
				$h .= '<li '.($eSaleTurnover['year'] === $year ? 'class="selected"' : '').'>';
					if($inPanel) {
						$h .= '<a data-ajax="/selling/customer:analyze?id='.$eCustomer['id'].'&year='.$eSaleTurnover['year'].'" data-ajax-method="get">';
					} else {
						$h .= '<a href="/selling/customer:analyze?id='.$eCustomer['id'].'&year='.$eSaleTurnover['year'].'">';
					}
						$h .= '<h5>'.$eSaleTurnover['year'].'</h5>';
						$h .= '<div>'.\util\TextUi::money($eSaleTurnover['turnover'], precision: 0).'</div>';
						$h .= '<div class="util-summarize-muted">('.\util\TextUi::pc($eSaleTurnover['turnover'] / $eSaleTurnover['turnoverGlobal'] * 100, 0).')</div>';
					$h .= '</a>';
				$h .= '</li>';
			}

		$h .= '</ul>';

		return $h;

	}

	public function getPlantSales(\plant\Plant $e, int $year, \Collection $cItemTurnover, \Collection $cItemCustomer, \Collection $cItemType, \Collection $cItemMonth, \Collection $cItemMonthBefore, \Collection $cItemWeek, $cItemWeekBefore, \Search $search): \Panel {

		$h = '';

		if($cItemTurnover->notEmpty()) {

			if($search->isFiltered('type')) {
				$h .= match($search->get('type')) {
					Customer::PRIVATE => '<h3>'.s("Clients particuliers").'</h3>',
					Customer::PRO => '<h3>'.s("Clients pros").'</h3>'
				};
			}

			$h .= $this->getPlantTurnover($cItemTurnover, $year, $e);

			if($cItemCustomer->offsetExists($year)) {

				if($e['farm']->canPersonalData()) {

					$h .= '<h3>'.s("Clients principaux").'</h3>';
					$h .= '<div class="analyze-chart-table">';
						$h .= $this->getBestCustomersPie($cItemCustomer, $year);
						$h .= $this->getBestCustomersByPlantTable($e, $cItemCustomer, $search->isFiltered('type') ? new \Collection() : $cItemType, $year, 10);
					$h .= '</div>';

					$h .= '<br/>';

				}

				$h .= '<h3>'.s("Ventes mensuelles").'</h3>';
				$h .= '<div class="analyze-chart-table">';
					$h .= $this->getPeriodMonthTable($cItemMonth, $e);
					if($cItemMonthBefore->notEmpty()) {
						$h .= $this->getDoublePeriodMonthChart($cItemMonth, $year, $cItemMonthBefore, $year - 1);
					} else {
						$h .= $this->getPeriodMonthChart($cItemMonth);
					}
				$h .= '</div>';

				$h .= '<br/>';

				$h .= '<h3>'.s("Ventes hebdomadaires").'</h3>';
				if($cItemMonthBefore->notEmpty()) {
					$h .= $this->getDoublePeriodWeekChart($cItemWeek, $year, $cItemWeekBefore, $year - 1);
				} else {
					$h .= $this->getPeriodWeekChart($cItemWeek, $e);
				}

			} else {
				$h .= '<p class="util-info">';
					$h .= s("Il n'y a aucune vente pour ce produit en {value}.", $year);
				$h .= '</p>';
			}

		} else {

			$h .= '<p class="util-info">';
				$h .= s("Vous n'avez encore jamais vendu la production de cette plante.");
			$h .= '</p>';

		}

		$title = s("{value} en {year}", ['value' => encode($e['name']), 'year' => $year]);

		return new \Panel(
			id: 'panel-plant-analyze',
			documentTitle: $title,
			body: $h,
			header: '<h4>'.s("ESPÈCE CULTIVÉE").'</h4><h2 class="panel-title">'.\plant\PlantUi::getVignette($e, '3rem').' '.$title.'</h2>',
		);

	}

	public function getPlantTurnover(\Collection $cItemTurnover, ?int $year, ?\plant\Plant $ePlantLink): string {

		$h = '<ul class="util-summarize">';

			foreach($cItemTurnover as $eItemTurnover) {
				$h .= '<li '.($eItemTurnover['year'] === $year ? 'class="selected"' : '').'>';
					$h .= '<a data-ajax="/plant/plant:analyzeSales?id='.$ePlantLink['id'].'&year='.$eItemTurnover['year'].'" data-ajax-method="get">';
						$h .= '<h5>'.$eItemTurnover['year'].'</h5>';
						$h .= '<div>'.\util\TextUi::money($eItemTurnover['turnover'], precision: 0).'</div>';
						$h .= '<div class="util-summarize-muted">('.\util\TextUi::pc($eItemTurnover['turnover'] / $eItemTurnover['turnoverGlobal'] * 100, 0).')</div>';
					$h .= '</a>';
				$h .= '</li>';
			}

		$h .= '</ul>';

		return $h;

	}

	public function getProduct(Product $e, int $year, \Collection $cItemTurnover, \Collection $cItemCustomer, \Collection $cItemType, \Collection $cItemMonth, \Collection $cItemMonthBefore, \Collection $cItemWeek, \Collection $cItemWeekBefore, \Search $search = new \Search()): \Panel {

		$h = '';

		if($cItemTurnover->notEmpty()) {

			if($search->isFiltered('type')) {
				$h .= match($search->get('type')) {
					Customer::PRIVATE => '<h3>'.s("Clients particuliers").'</h3>',
					Customer::PRO => '<h3>'.s("Clients pros").'</h3>'
				};
			}

			$h .= $this->getProductTurnover($cItemTurnover, $year, $e);

			if($cItemCustomer->offsetExists($year)) {

				if($e['farm']->canPersonalData()) {

					$h .= '<h3>'.s("Clients principaux").'</h3>';
					$h .= '<div class="analyze-chart-table">';
						$h .= $this->getBestCustomersPie($cItemCustomer, $year);
						$h .= $this->getBestCustomersByProductTable($e, $cItemCustomer, $search->isFiltered('type') ? new \Collection() : $cItemType, $year, 10);
					$h .= '</div>';

				}

				$h .= '<h3>'.s("Ventes mensuelles").'</h3>';
				$h .= '<div class="analyze-chart-table">';
					$h .= $this->getPeriodMonthTable($cItemMonth, $e);
					if($cItemMonthBefore->notEmpty()) {
						$h .= $this->getDoublePeriodMonthChart($cItemMonth, $year, $cItemMonthBefore, $year - 1);
					} else {
						$h .= $this->getPeriodMonthChart($cItemMonth);
					}
				$h .= '</div>';

				$h .= '<br/>';

				$h .= '<h3>'.s("Ventes hebdomadaires").'</h3>';
				if($cItemMonthBefore->notEmpty()) {
					$h .= $this->getDoublePeriodWeekChart($cItemWeek, $year, $cItemWeekBefore, $year - 1);
				} else {
					$h .= $this->getPeriodWeekChart($cItemWeek, $e);
				}

			} else {
				$h .= '<p class="util-info">';
					$h .= s("Il n'y a aucune vente pour ce produit en {value}.", $year);
				$h .= '</p>';
			}

		} else {

			$h .= '<p class="util-info">';
				$h .= s("Vous n'avez encore jamais vendu ce produit.");
			$h .= '</p>';

		}

		$title = s("{value} en {year}", ['value' => encode($e['name']), 'year' => $year]);

		return new \Panel(
			id: 'panel-product-analyze',
			documentTitle: $title,
			body: $h,
			header: '<h4>'.s("PRODUIT").'</h4><h2 class="panel-title">'.ProductUi::getVignette($e, '3rem').' '.$title.'</h2>',
		);

	}

	public function getProductTurnover(\Collection $cItemTurnover, ?int $year, ?Product $eProductLink): string {

		$h = '<ul class="util-summarize">';

			foreach($cItemTurnover as $eItemTurnover) {
				$h .= '<li '.($eItemTurnover['year'] === $year ? 'class="selected"' : '').'>';
					$h .= '<a data-ajax="/selling/product:analyze?id='.$eProductLink['id'].'&year='.$eItemTurnover['year'].'" data-ajax-method="get">';
						$h .= '<h5>'.$eItemTurnover['year'].'</h5>';
						$h .= '<div>'.\util\TextUi::money($eItemTurnover['turnover'], precision: 0).'</div>';
						$h .= '<div class="util-summarize-muted">('.\util\TextUi::pc($eItemTurnover['turnover'] / $eItemTurnover['turnoverGlobal'] * 100, 0).')</div>';
					$h .= '</a>';
				$h .= '</li>';
			}

		$h .= '</ul>';

		return $h;

	}

	public function getSearch(\Search $search): string {

		$h = '<div id="analyze-selling-search" class="util-block-search '.($search->empty() ? 'hide' : '').' mt-1">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';

					if(get_exists('month')) {
						$h .= $form->hidden('month', GET('month'));
					}

					if(get_exists('week')) {
						$h .= $form->hidden('week', GET('week'));
					}

					$h .= $form->select('type', [
							Customer::PRIVATE => s("Clients particuliers"),
							Customer::PRO => s("Clients pros"),
						], $search->get('type'), ['placeholder' => s("Clients particuliers et pros")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';

				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getYears(\farm\Farm $eFarm, array $years, int $selectedYear, ?int $selectedMonth, ?string $selectedWeek, string $selectedView): string {

		$h = ' '.\Asset::icon('chevron-right').' ';

		if(count($years) === 1) {
			$h .= $selectedYear;
			return $h;
		}

		$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">'.$selectedYear.' '.\farm\FarmUi::getNavigation().'</a>';

		$h .= '<div class="dropdown-list dropdown-list-3 bg-secondary">';

			$h .= '<div class="dropdown-title">'.s("Changer l'année").'</div>';

			foreach($years as $year => $sales) {

				$url = \farm\FarmUi::urlAnalyzeSelling($eFarm, $year, $selectedView);

				$h .= '<a href="'.$url.'" class="dropdown-item dropdown-item-full '.(($selectedYear === $year and $selectedMonth === NULL) ? 'selected' : '').'">'.s("Année {year}", ['year' => $year]).'</a>';

			}

		$h .= '</div>';

		if($selectedMonth !== NULL) {
			$h .= ' '.\Asset::icon('chevron-right').' ';
			$h .= mb_ucfirst(\util\DateUi::getMonthName($selectedMonth));
			$h .= ' <a href="'.\farm\FarmUi::urlAnalyzeSelling($eFarm, $selectedYear, $selectedView).'" class="btn btn-sm btn-outline-secondary">'.\Asset::icon('x-circle').'</a>';
		}

		if($selectedWeek !== NULL) {
			$h .= ' '.\Asset::icon('chevron-right').' ';
			$h .= s("Semaine {value}", week_number($selectedWeek));
			$h .= ' <a href="'.\farm\FarmUi::urlAnalyzeSelling($eFarm, $selectedYear, $selectedView).'" class="btn btn-sm btn-outline-secondary">'.\Asset::icon('x-circle').'</a>';
		}

		return $h;

	}

	public function getExportHeader(\farm\Farm $eFarm): array {

		return [
			s("Désignation"),
			s("Produit"),
			s("Vente"),
			s("Client"),
			s("Type"),
			s("Livraison"),
			s("Quantité"),
			s("Unité"),
			$eFarm['selling']['hasVat'] ? s("Montant (HT)") : s("Montant")
		];

	}

}
?>
