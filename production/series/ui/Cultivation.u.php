<?php
namespace series;

class CultivationUi {

	public function __construct() {

		\Asset::css('series', 'series.css');
		\Asset::js('series', 'series.js');

		\Asset::js('series', 'cultivation.js');
		\Asset::css('series', 'cultivation.css');

		\Asset::js('sequence', 'crop.js');
		\Asset::css('sequence', 'crop.css');

	}

	public function getListSeason(\farm\Farm $eFarm, int $season, bool $hasWeeks = FALSE): string {

		$eFarm->expects(['calendarMonths', 'calendarMonthStart', 'calendarMonthStop']);

		$daysList = $this->getListDays($eFarm, $season);
		$days = array_sum($daysList);

		$widths = [];
		foreach($daysList as $dayByMonth) {
			$widths[] = ($dayByMonth / $days * 100).'%';
		}

		$h = '<div class="series-timeline-season series-season" style="grid-template-columns: '.implode(' ', $widths).';">';

			if($eFarm['calendarMonthStart'] !== NULL) {
				$h .= '<div class="series-season-year series-season-year-external" style="grid-column: span '.(12 - $eFarm['calendarMonthStart'] + 1).';">';
					$h .= $season - 1;
				$h .= '</div>';
			}

			$h .= '<div class="series-season-year series-season-year-current">';
				$h .= $season;
			$h .= '</div>';

			if($eFarm['calendarMonthStop'] !== NULL) {
				$h .= '<div class="series-season-year series-season-year-external" style="grid-column: span '.$eFarm['calendarMonthStop'].';">';
					$h .= $season + 1;
				$h .= '</div>';
			}

			$months = ['J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D'];

			if($eFarm['calendarMonthStart'] !== NULL) {
				for($month = $eFarm['calendarMonthStart'] - 1; $month < 12; $month++) {
					$h .= '<div class="series-season-month '.($hasWeeks ? 'series-season-month-with-weeks' : '').' series-season-month-external">'.$months[$month].'</div>';
				}
			}

			foreach($months as $month) {
				$h .= '<div class="series-season-month '.($hasWeeks ? 'series-season-month-with-weeks' : '').'">'.$month.'</div>';
			}

			if($eFarm['calendarMonthStop'] !== NULL) {
				for($month = 0; $month < $eFarm['calendarMonthStop']; $month++) {
					$h .= '<div class="series-season-month '.($hasWeeks ? 'series-season-month-with-weeks' : '').' series-season-month-external">'.$months[$month].'</div>';
				}
			}

			if($hasWeeks) {

				$weeks = new PlaceUi()->getWeeksInBounds($eFarm, $season);
				[$startTs, $stopTs] = new PlaceUi()->getBounds($eFarm, $season);
				$startWeekTs = strtotime(week_date_starts($weeks[0]));

				$left = ($startWeekTs - $startTs) / ($stopTs - $startTs) * 100;
				$width = 7 / $days * 100;

				$h .= '<div class="series-season-weeks" style="grid-column: span '.$eFarm['calendarMonths'].'">';

					foreach($weeks as $week) {

						$h .= '<div class="series-season-week" style="left: '.$left.'%; width: '.$width.'%">'.week_number($week).'</div>';
						$left += $width;

					}

					$h .= $this->getNowGrid($startTs, $stopTs);

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getListGrid(\farm\Farm $eFarm, int $season, bool $hasWeeks = FALSE): string {

		$eFarm->expects(['calendarMonths', 'calendarMonthStart', 'calendarMonthStop']);

		$days = array_sum($this->getListDays($eFarm, $season));

		$h = '';
		if($hasWeeks) {
			$h .= $this->getWeeklyListGrid($eFarm, $season, $days);
		}
		$h .= $this->getMonthlyListGrid($eFarm, $season, $days);

		return $h;

	}

	protected function getListDays(\farm\Farm $eFarm, int $season): array {

		$days = [];

		if($eFarm['calendarMonthStart'] !== NULL) {
			for($month = $eFarm['calendarMonthStart']; $month <= 12; $month++) {
				$days[] = date('t', strtotime(($season - 1).'-'.sprintf('%02d', $month).'-01'));
			}
		}

		for($month = 1; $month <= 12; $month++) {
			$days[] = date('t', strtotime($season.'-'.sprintf('%02d', $month).'-01'));
		}

		if($eFarm['calendarMonthStop'] !== NULL) {
			for($month = 1; $month <= $eFarm['calendarMonthStop']; $month++) {
				$days[] = date('t', strtotime(($season + 1).'-'.sprintf('%02d', $month).'-01'));
			}
		}

		return $days;

	}

	public function getWeeklyListGrid(\farm\Farm $eFarm, int $season, int $days): string {

		$eFarm->expects(['calendarMonths', 'calendarMonthStart', 'calendarMonthStop']);

		$weeks = new PlaceUi()->getWeeksInBounds($eFarm, $season);
		[$startTs, $stopTs] = new PlaceUi()->getBounds($eFarm, $season);
		$startWeekTs = strtotime(week_date_starts($weeks[0]));

		$left = ($startWeekTs - $startTs) / ($stopTs - $startTs) * 100;
		$width = 7 / $days * 100;

		$h = '<div class="series-grid series-grid-weeks">';

			foreach($weeks as $week) {

				$h .= '<div class="series-grid-line" style="left: calc('.$left.'% - 1px)"></div>';
				$left += $width;

			}

		$h .= '</div>';


		return $h;

	}

	public function getMonthlyListGrid(\farm\Farm $eFarm, int $season, int $days): string {

		$eFarm->expects(['calendarMonths', 'calendarMonthStart', 'calendarMonthStop']);

		$h = '<div class="series-grid series-grid-months">';

			$left = 0;

			if($eFarm['calendarMonthStart'] !== NULL) {
				for($month = $eFarm['calendarMonthStart']; $month <= 12; $month++) {
					$width = date('t', strtotime(($season - 1).'-'.sprintf('%02d', $month).'-01')) / $days * 100;
					$h .= '<div class="series-grid-line" style="left: '.$left.'%; width: '.$width.'%"></div>';
					$left += $width;
				}
			}

			for($month = 1; $month < 12; $month++) {
				$width = date('t', strtotime($season.'-'.sprintf('%02d', $month).'-01')) / $days * 100;
				$h .= '<div class="series-grid-line" style="left: '.$left.'%; width: '.$width.'%"></div>';
				$left += $width;
			}

			if($eFarm['calendarMonthStop'] !== NULL) {
				for($month = 1; $month <= $eFarm['calendarMonthStop']; $month++) {
					$width = date('t', strtotime(($season + 1).'-'.sprintf('%02d', $month).'-01')) / $days * 100;
					$h .= '<div class="series-grid-line" style="left: '.$left.'%; width: '.$width.'%"></div>';
					$left += $width;
				}
			}

			[$startTs, $stopTs] = new PlaceUi()->getBounds($eFarm, $season);
			$h .= $this->getNowGrid($startTs, $stopTs);

		$h .= '</div>';


		return $h;

	}

	protected function getNowGrid(int $startTs, int $stopTs): string {

		$currentTs = time();

		if($currentTs < $stopTs) {

			$left = ($currentTs - $startTs) / ($stopTs - $startTs) * 100;
			return '<div class="series-grid-now" style="right: '.(100 - $left).'%"></div>';

		} else {
			return '';
		}

	}

	public function displayByArea(int $season, \farm\Farm $eFarm, \Collection $ccCultivation) {

		$viewHarvestExpected = ($ccCultivation->reduce(fn($cCultivation, $n) => $cCultivation->reduce(fn($eCultivation, $n) => $n + (int)($eCultivation['harvestExpectedTarget'] !== NULL or $eCultivation['harvestExpected'] !== NULL), $n), 0) > 0) ? $eFarm->getView('viewPlanningHarvestExpected') : NULL;

		$viewField = $eFarm->getView('viewPlanningField');
		$viewArea = $eFarm->getView('viewPlanningArea');

		$h = '<div id="series-wrapper" class="series-item-wrapper series-item-planning-wrapper '.($viewHarvestExpected ? 'series-item-planning-harvest' : '').' util-overflow-md stick-md">';

			$h .= '<div class="series-item-header series-item-planning">';

				$h .= '<div class="util-grid-header util-checkbox">';
					if($ccCultivation->count() > 1) {
						$h .= '<label>';
							$h .= '<input type="checkbox" class="series-item-planning-checkbox" oninput="Series.changeAllSelection(this)"/>';
						$h .= '</label>';
					}
				$h .= '</div>';
				$h .= '<div class="util-grid-header">';
					$h .= s("Série");
				$h .= '</div>';
				$h .= '<div class="util-grid-header series-item-planning-place">';

					$label = match($viewField) {
						\farm\Farmer::VARIETY => s("Variétés"),
						\farm\Farmer::SOIL => s("Assolement")
					};

					$h .= '<a class="dropdown-toggle" data-dropdown="bottom-end">'.$label.'</a>';
					$h .= '<div class="dropdown-list bg-secondary">';
						$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::AREA, $season).'&field='.\farm\Farmer::VARIETY.'" class="dropdown-item">'.s("Voir les variétés").'</a>';
						$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::AREA, $season).'&field='.\farm\Farmer::SOIL.'" class="dropdown-item">'.s("Voir l'assolement").'</a>';
					$h .= '</div>';

				$h .= '</div>';
				$h .= '<div class="util-grid-header text-end">';

					$label = match($viewArea) {
						\farm\Farmer::AREA => s("Surface"),
						\farm\Farmer::LENGTH => s("Linéaire")
					};

					$h .= '<a class="dropdown-toggle" data-dropdown="bottom-end">'.$label.'</a>';
					$h .= '<div class="dropdown-list bg-secondary">';
						$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::AREA, $season).'&area='.\farm\Farmer::AREA.'" class="dropdown-item">'.s("Afficher toujours la surface").'</a>';
						$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::AREA, $season).'&area='.\farm\Farmer::LENGTH.'" class="dropdown-item">'.s("Afficher en priorité le linéaire").'</a>';
					$h .= '</div>';
				$h .= '</div>';

				if($viewHarvestExpected) {

					$h .= '<div class="util-grid-header text-end">';

						$label = match($viewHarvestExpected) {
							\farm\Farmer::TOTAL => s("Objectif de récolte<br/>total"),
							\farm\Farmer::WEEKLY => s("Objectif de récolte<br/>hebdo")
						};

						$h .= '<a class="dropdown-toggle" data-dropdown="bottom-end">'.$label.'</a>';
						$h .= '<div class="dropdown-list bg-secondary">';
							$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::AREA, $season).'&harvestExpected='.\farm\Farmer::TOTAL.'" class="dropdown-item">'.s("Voir l'objectif total").'</a>';
							$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::AREA, $season).'&harvestExpected='.\farm\Farmer::WEEKLY.'" class="dropdown-item">'.s("Voir l'objectif hebdomadaire").'</a>';
						$h .= '</div>';

					$h .= '</div>';

				}

				$h .= '<div class="util-grid-header series-item-planning-timeline">';
					$h .= $this->getListSeason($eFarm, $season);
				$h .= '</div>';

			$h .= '</div>';

			$hasTargeted = FALSE;

			$h .= '<div class="series-item-body">';

				$h .= $this->getListGrid($eFarm, $season, hasWeeks: TRUE);

				foreach($ccCultivation as $cCultivation) {

					$ePlant = $cCultivation->first()['plant'];

					$cultivations = '';
					$hasAnnual = FALSE;

					$totalHarvestExpected = [];
					$totalArea = [
						'length' => 0,
						'lengthTarget' => 0,
						'area' => 0,
						'areaTarget' => 0,
					];

					foreach($cCultivation as $eCultivation) {

						$eSeries = $eCultivation['series'];

						$eCultivation->expects(['cTask']);

						$cultivations .= '<div class="series-item series-item-planning series-item-planning-'.$eSeries['cycle'].' series-item-status-'.$eSeries['status'].'" id="series-item-'.$eCultivation['id'].'">';

							$batch = [];

							if($eSeries->acceptOpen() === FALSE) {
								$batch[] = 'not-open';
							}

							if($eSeries->acceptClose() === FALSE) {
								$batch[] = 'not-close';
							}

							if($eSeries->acceptDuplicate() === FALSE) {
								$batch[] = 'not-duplicate';
							}

							if($eSeries->acceptSeason() === FALSE) {
								$batch[] = 'not-season';
							}

							if($eSeries['cycle'] === Series::ANNUAL) {
								$cultivations .= '<label class="util-checkbox">';
									$cultivations .= '<input type="checkbox" class="series-item-planning-checkbox" name="batch[]" value="'.$eSeries['id'].'" oninput="Series.changeSelection()" data-plant="'.$ePlant['id'].'" data-batch="'.implode(' ', $batch).'"/>';
									$hasAnnual = TRUE;
								$cultivations .= '</label>';
							}
							$cultivations .= '<a href="/serie/'.$eSeries['id'].'" class="series-item-planning-details '.($viewField === \farm\Farmer::VARIETY ? 'series-item-planning-details-with-variety' : '').'">';
								$cultivations .= $this->getSeriesForDisplay($eSeries, $eCultivation, tag: 'span');
								if($viewField === \farm\Farmer::VARIETY) {
									$cultivations .= '<span class="series-item-planning-details-variety">'.new \sequence\SliceUi()->getLine($eCultivation, $eCultivation['cSlice']).'</span>';
								}
							$cultivations .= '</a>';

							if($viewField === \farm\Farmer::SOIL) {
								$cultivations .= '<div class="series-item-planning-place">';
									$places = $this->displayPlaces($eSeries['use'], $eSeries['cccPlace']);
									if($places) {
										$cultivations .= $places;
									} else {
										$cultivations .= '-';
									}
								$cultivations .= '</div>';
							}

							$area = '';
							$areaMissing = FALSE;

							if($viewArea === \farm\Farmer::LENGTH and $eSeries['use'] === Series::BED) {

								if($eSeries['length'] !== NULL) {
									if($eSeries['lengthTarget'] !== NULL and $eSeries['lengthTarget'] > $eSeries['length']) {
										$area = s("{value} / {target} mL", ['value' => $eSeries['length'], 'target' => $eSeries['lengthTarget']]);
										$areaMissing = TRUE;
									} else if($eSeries['lengthTarget'] !== NULL and $eSeries['lengthTarget'] < $eSeries['length']) {
										$area = s("{value} / {target} mL", ['value' => $eSeries['length'], 'target' => $eSeries['lengthTarget']]);
									} else {
										$area = s("{value} mL", $eSeries['length']);
									}
								} else if($eSeries['lengthTarget'] !== NULL) {
									$area = s("0 / {value} mL", $eSeries['lengthTarget']);
									$areaMissing = TRUE;
								}

								$totalArea['length'] += ($eSeries['length'] ?? 0);
								$totalArea['lengthTarget'] += ($eSeries['lengthTarget'] ?? $eSeries['length'] ?? 0);

							} else {

								if($eSeries['area'] !== NULL) {
									if($eSeries['areaTarget'] !== NULL and $eSeries['areaTarget'] > $eSeries['area']) {
										$area = s("{value} / {target} m²", ['value' => $eSeries['area'], 'target' => $eSeries['areaTarget']]);
										$areaMissing = TRUE;
									} else if($eSeries['areaTarget'] !== NULL and $eSeries['areaTarget'] < $eSeries['area']) {
										$area = s("{value} / {target} m²", ['value' => $eSeries['area'], 'target' => $eSeries['areaTarget']]);
									} else {
										$area = s("{value} m²", $eSeries['area']);
									}
								} else if($eSeries['areaTarget'] !== NULL) {
									$area = s("0 / {value} m²", $eSeries['areaTarget']);
									$areaMissing = TRUE;
								}

								$totalArea['area'] += ($eSeries['area'] ?? 0);
								$totalArea['areaTarget'] += ($eSeries['areaTarget'] ?? $eSeries['area'] ?? 0);

							}

							$cultivations .= '<div class="series-item-planning-summary '.($areaMissing ? 'color-warning' : '').'">';
								$cultivations .= $area;
							$cultivations .= '</div>';

							switch($viewHarvestExpected) {

								case \farm\Farmer::TOTAL :

									if($eCultivation['harvestExpected'] !== NULL) {

										$cultivations .= '<div class="series-item-planning-summary">';
											$cultivations .= $eCultivation->format('harvestExpected', ['short' => TRUE]);
										$cultivations .= '</div>';

										$totalHarvestExpected[$eCultivation['mainUnit']] ??= 0;
										$totalHarvestExpected[$eCultivation['mainUnit']] += $eCultivation['harvestExpected'];

									} else if($eCultivation['harvestExpectedTarget'] !== NULL) {

										$cultivations .= '<div class="series-item-planning-summary color-warning">';
											$cultivations .= $eCultivation->format('harvestExpectedTarget', ['short' => TRUE]).'&nbsp;*';
										$cultivations .= '</div>';

										$totalHarvestExpected[$eCultivation['mainUnit']] ??= 0;
										$totalHarvestExpected[$eCultivation['mainUnit']] += $eCultivation['harvestExpectedTarget'];

										$hasTargeted = TRUE;

									} else {
										$cultivations .= '<div></div>';
									}

									break;

								case \farm\Farmer::WEEKLY :

									if($eCultivation['harvestExpected'] !== NULL) {

										$cultivations .= '<div class="series-item-planning-summary">';
											if($eCultivation['harvestWeeksExpected']) {
												$cultivations .= \selling\UnitUi::getValue(round($eCultivation['harvestExpected'] / count($eCultivation['harvestWeeksExpected'])), $eCultivation['mainUnit'], TRUE);
											}
										$cultivations .= '</div>';

									} else if($eCultivation['harvestExpectedTarget'] !== NULL) {

										$cultivations .= '<div class="series-item-planning-summary color-warning">';
											if($eCultivation['harvestWeeksExpected']) {
												$cultivations .= \selling\UnitUi::getValue(round($eCultivation['harvestExpectedTarget'] / count($eCultivation['harvestWeeksExpected'])), $eCultivation['mainUnit'], TRUE).'&nbsp;*';
											}
										$cultivations .= '</div>';

										$hasTargeted = TRUE;

									} else {
										$cultivations .= '<div></div>';
									}

									break;

							}

							$cultivations .= '<div class="series-item-planning-timeline">';
								$cultivations .= $this->getTimeline($eFarm, $season, $eSeries, $eCultivation, $eCultivation['cTask']);
							$cultivations .= '</div>';

						$cultivations .= '</div>';

					}

					$h .= '<div class="series-item series-item-planning series-item-planning-'.($hasAnnual ? Series::ANNUAL : Series::PERENNIAL).' series-item-title">';
						if($hasAnnual) {
							$h .= '<div class="util-checkbox">';
								if($cCultivation->count() > 1) {
									$h .= '<label>';
										$h .= '<input type="checkbox" class="series-item-planning-checkbox" oninput="Series.changePlantSelection(this, '.$ePlant['id'].')"/>';
									$h .= '</label>';
								}
							$h .= '</div>';
						}
						$h .= '<div class="series-item-title-plant">';
							$h .= \plant\PlantUi::getVignette($ePlant, '2.6rem');
							$h .= '<span class="series-item-title-plant-name">'.encode($ePlant['name']).'</span>';
						$h .= '</div>';

					$h .= '<div class="series-item-planning-place">';
					$h .= '</div>';

					$h .= '<div class="series-item-planning-summary">';

						if($totalArea['lengthTarget'] > 0) {

							$h .= '<div>';
								$h .= $totalArea['length'];
								if($totalArea['length'] !== $totalArea['lengthTarget']) {
									$h .= ' / '.$totalArea['lengthTarget'];
								}
								$h .= ' '.s("mL");
							$h .= '</div>';

						}

						if($totalArea['areaTarget'] > 0) {

							$h .= '<div>';
								$h .= $totalArea['area'];
								if($totalArea['area'] !== $totalArea['areaTarget']) {
									$h .= ' / '.$totalArea['areaTarget'];
								}
								$h .= ' '.s("m²");
							$h .= '</div>';

						}

					$h .= '</div>';

					if($viewHarvestExpected) {
						$h .= '<div class="series-item-planning-summary">';
							foreach($totalHarvestExpected as $unit => $value) {
								$h .= '<div>'.\selling\UnitUi::getValue($value, $unit, short: TRUE).'</div>';
							}
						$h .= '</div>';

					}
					$h .= '</div>';
					$h .= $cultivations;

				}

			$h .= '</div>';

		$h .= '</div>';

		if($hasTargeted) {
			$h .= $this->getWarningTargeted();
		}

		$h .= $this->getBatch($season);

		return $h;

	}

	public function getBatch(int $season): string {

		$menu = '<a data-ajax-submit="/series/series:doUpdateStatusCollection" post-status="'.Series::OPEN.'" class="batch-menu-open batch-menu-item">';
			$menu .= \Asset::icon('unlock');
			$menu .= '<span>'.s("Réouvrir").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/series/series:doUpdateStatusCollection" post-status="'.Series::CLOSED.'" class="batch-menu-close batch-menu-item" data-confirm="'.s("Des séries clôturées sont des séries pour lesquelles vous avez terminé toutes les interventions culturales. Confirmer ?").'">';
			$menu .= \Asset::icon('lock');
			$menu .= '<span>'.s("Clôturer").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/series/series:duplicate" data-ajax-method="get" class="batch-menu-duplicate batch-menu-item">';
			$menu .= \Asset::icon('copy');
			$menu .= '<span>'.s("Dupliquer").'</span>';
		$menu .= '</a>';

		$menu .= '<a data-ajax-submit="/series/series:updateSeasonCollection" data-ajax-method="get" class="batch-menu-season batch-menu-item">';
			$menu .= \Asset::icon('calendar');
			$menu .= '<span>'.s("Changer de saison").'</span>';
		$menu .= '</a>';

		$danger = '<a data-ajax-submit="/series/series:doDeleteCollection" data-confirm="'.s("Vous vous apprêtez à supprimer définitivement des séries de la saison {value}. Voulez-vous continuer ?", $season).'" class="batch-menu-item batch-menu-item-danger">';
			$danger .= \Asset::icon('trash');
			$danger .= '<span>'.s("Supprimer").'</span>';
		$danger .= '</a>';

		return \util\BatchUi::group($menu, $danger, title: s("Pour les séries sélectionnées"), hide: 'Series.hideSelection()');

	}

	public function displayPlaces(string $use, \Collection $cccPlace): string {

		$list = [];

		foreach($cccPlace as $ccPlace) {

			foreach($ccPlace as $cPlace) {

				$ePlaceFirst = $cPlace->first();

				if($ePlaceFirst['plot']['zoneFill'] === FALSE) {
					$h = encode($ePlaceFirst['plot']['name']);
				} else {
					$h = encode($ePlaceFirst['zone']['name']);
				}

				if($use === Series::BED) {

					$beds = $cPlace
						->find(fn($ePlace) => $ePlace['bed']['plotFill'] === FALSE)
						->makeArray(fn($ePlace) => encode($ePlace['bed']['name']));

					if($beds) {

						$h .= ' '.\Asset::icon('chevron-right').' ';
						$h .= implode(', ', $beds);

					}

				}

				$list[] = $h;

			}

		}

		return implode('<span>&nbsp;+&nbsp;</span>', $list);

	}

	public function displayByForecast(\farm\Farm $eFarm, int $season, \Collection $ccForecast) {

		$h = '<div id="series-wrapper" class="series-item-wrapper series-item-forecast-wrapper util-overflow-lg stick-md">';

			$h .= '<div class="series-item-header series-item-forecast">';

				$h .= '<div class="util-grid-header" style="grid-row: span 2">'.s("Espèce").'</div>';
				$h .= '<div class="util-grid-header text-center color-private" style="grid-row: span 2">'.s("Prix et volume<br/>particuliers").'</div>';
				$h .= '<div class="util-grid-header text-center color-pro" style="grid-row: span 2">'.s("Prix et volume<br/>professionnels").'</div>';
				$h .= '<div class="util-grid-header series-item-forecast-objective-title" style="grid-column: span 3">'.s("Prévisionnel").'</div>';
				$h .= '<div class="util-grid-header series-item-forecast-objective-title" style="grid-column: span 4">'.s("Planifié").'</div>';
				$h .= '<div class="util-grid-header" style="grid-row: span 2"></div>';

				$h .= '<div class="util-grid-header series-item-forecast-objective series-item-forecast-objective-first" style="grid-column: span 2">'.s("Volume").'</div>';
				$h .= '<div class="util-grid-header series-item-forecast-objective series-item-forecast-objective-last">'.s("Ventes").'</div>';
				$h .= '<div class="util-grid-header series-item-forecast-objective series-item-forecast-objective-first text-end">'.s("Surface").'</div>';
				$h .= '<div class="util-grid-header series-item-forecast-objective" style="grid-column: span 2">'.s("Volume").'</div>';
				$h .= '<div class="util-grid-header series-item-forecast-objective series-item-forecast-objective-last">'.s("Ventes").'</div>';

			$h .= '</div>';

			$gap = '<div class="series-item series-item-forecast series-item-gap">';
				$gap .= '<div></div>';
				$gap .= '<div style="grid-column: span 2"></div>';
				$gap .= '<div class="series-item-forecast-objective series-item-forecast-objective-first series-item-forecast-objective-last" style="grid-column: span 3"></div>';
				$gap .= '<div class="series-item-forecast-objective series-item-forecast-objective-first series-item-forecast-objective-last" style="grid-column: span 4"></div>';
				$gap .= '<div></div>';
			$gap .= '</div>';

			$h .= '<div class="series-item-body">';

				$totalForecast = 0;
				$totalSeries = 0;

				foreach($ccForecast as $cForecast) {

					$ePlant = $cForecast->first()['plant'];

					$h .= '<div class="series-item series-item-forecast series-item-title">';
						$h .= '<div class="series-item-title-plant" style="grid-column: span 3">';
							$h .= \plant\PlantUi::getVignette($ePlant, '2.6rem');
							$h .= '<span class="series-item-title-plant-name">'.encode($ePlant['name']).'</span>';
						$h .= '</div>';
						$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-first series-item-forecast-objective-last" style="grid-column: span 3"></div>';
						$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-first series-item-forecast-objective-last" style="grid-column: span 4"></div>';
						$h .= '<div></div>';
					$h .= '</div>';
					$h .= $gap;

					foreach($cForecast as $unit => $eForecast) {

						$cCultivation = $eForecast['cCultivation'];

						$area = round($cCultivation->reduce(fn($eCultivation, $n) => $n + ($eCultivation['series']['area'] ?? $eCultivation['series']['areaTarget'] ?? 0), 0));
						$harvestExpected = $cCultivation->reduce(fn($eCultivation, $n) => $n + round(($eCultivation['yieldExpected']) * ($eCultivation['series']['area'] ?? $eCultivation['series']['areaTarget'] ?? 0)), 0);

						$sales = fn($harvest) =>
								($harvest * $eForecast['proPrice'] * ($eForecast['proPart'] ?? 0) / 100) +
								($harvest * $eForecast['privatePrice'] * ($eForecast['privatePart'] ?? 0) / 100);

						$salesForecast = ($eForecast['harvestObjective'] === NULL) ? 0 : $sales($eForecast['harvestObjective']);
						$salesSeries = $sales($harvestExpected);

						$totalForecast += $salesForecast;
						$totalSeries += $salesSeries;

						$h .= '<div class="series-item series-item-forecast">';
							$h .= '<div class="series-item-forecast-series">';
								$h .= \plant\ForecastUi::p('unit')->values[$eForecast['unit']];
							$h .= '</div>';


							$colorPrivate = ($eForecast['privatePart'] === 0) ? 'muted' : 'private';
							$colorPro = ($eForecast['proPart'] === 0) ? 'muted' : 'pro';

							$h .= '<div class="series-item-forecast-price color-'.$colorPrivate.'">';
								$h .= '<div>';
									if($eForecast['privatePrice'] === NULL) {
											$h .= $eForecast->quick('privatePrice', \Asset::icon('plus-circle').' '.s("Prix"), class: 'btn btn-sm btn-outline-'.$colorPrivate);
									} else {
										$h .= $eForecast->quick('privatePrice', \util\TextUi::money($eForecast['privatePrice']));
									}
								$h .= '</div>';
								$h .= '<div>';
									$h .= $eForecast->quick('privatePart', s("{value} %", $eForecast['privatePart']));
								$h .= '</div>';
							$h .= '</div>';

							$h .= '<div class="series-item-forecast-price color-'.$colorPro.'">';
								$h .= '<div>';
									if($eForecast['proPrice'] === NULL) {
											$h .= $eForecast->quick('proPrice', \Asset::icon('plus-circle').' '.s("Prix"), class: 'btn btn-sm btn-outline-'.$colorPro);
									} else {
										$h .= $eForecast->quick('proPrice', \util\TextUi::money($eForecast['proPrice']));
									}
								$h .= '</div>';
								$h .= '<div>';
									$h .= $eForecast->quick('proPart', s("{value} %", $eForecast['proPart']));
								$h .= '</div>';
							$h .= '</div>';

							if($eForecast['harvestObjective'] === NULL) {
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-first series-item-forecast-objective-last" style="grid-column: span 3; justify-content: center">';
									$h .= $eForecast->quick('harvestObjective', \Asset::icon('plus-circle').' '.s("Volume"), class: 'btn btn-sm btn-outline-muted');
								$h .= '</div>';
							} else {
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-first" style="padding-right: 0">';
									$h .= $eForecast->quick('harvestObjective', $eForecast['harvestObjective']);
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-unit">';
									$h .= \selling\UnitUi::getSingular($unit, short: TRUE);
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-last series-item-forecast-sales">';
									if($salesForecast > 0) {
										$h .= \util\TextUi::money($salesForecast, precision: 0);
									}
								$h .= '</div>';
							}

							if($cCultivation->count() === 0) {

								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-first series-item-forecast-objective-last text-center color-muted" style="grid-column: span 4">';
									$h .= s("Aucune série");
								$h .= '</div>';

							} else {

								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-first series-item-forecast-area">';
									if($area > 0) {
										$h .= s("{value} m²", round($area));
									}
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-harvest-expected">';
									if($harvestExpected > 0) {
										$h .= $harvestExpected;
									}
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-unit">';
									if($harvestExpected > 0) {
										$h .= \selling\UnitUi::getSingular($unit, short: TRUE);
									}
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-last series-item-forecast-sales">';
									if($salesSeries > 0) {
										$h .= \util\TextUi::money($salesSeries, precision: 0);
									}
								$h .= '</div>';

							}

							$h .= '<div class="series-item-forecast-actions">';
								$h .= '<a href="/plant/forecast:update?id='.$eForecast['id'].'" class="btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
							$h .= '</div>';

						$h .= '</div>';

						foreach($cCultivation as $eCultivation) {

							$eSeries = $eCultivation['series'];
							$harvestExpected = (int)(($eCultivation['yieldExpected']) * ($eCultivation['series']['area'] ?? $eCultivation['series']['areaTarget'] ?? 0));

							$area = $eSeries['area'] ?? $eSeries['areaTarget'];

							$h .= '<div class="series-item series-item-forecast">';
								$h .= '<div class="series-item-forecast-series series-item-forecast-series-gap" style="grid-column: span 3">';
									$h .= SeriesUi::link($eSeries);
									$h .= \sequence\CropUi::start($eCultivation, \farm\FarmSetting::$mainActions);
									if($area === NULL) {
										$h .= '  <a href="/series/series:update?id='.$eSeries['id'].'" class="series-item-forecast-missing">'.\Asset::icon('exclamation-triangle-fill').' '.s("Définir l'objectif de surface").'</a>';
									}
									if($eCultivation['yieldExpected'] === NULL) {
										$h .= '  <a href="/series/cultivation:update?id='.$eCultivation['id'].'" class="series-item-forecast-missing">'.\Asset::icon('exclamation-triangle-fill').' '.s("Définir l'objectif de rendement").'</a>';
									}
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-first series-item-forecast-objective-last" style="grid-column: span 3">';
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-first series-item-forecast-area color-muted">';
									if($area !== NULL) {
										$h .= s("{value} m²", $area);
									} else {
										$h .= '?';
									}
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-harvest-expected color-muted">';
									if($harvestExpected > 0) {
										$h .= $harvestExpected;
									} else {
										$h .= '?';
									}
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-unit color-muted">';
									if($harvestExpected > 0) {
										$h .= \selling\UnitUi::getSingular($unit, short: TRUE);
									}
								$h .= '</div>';
								$h .= '<div class="series-item-forecast-objective series-item-forecast-objective-last series-item-forecast-sales color-muted">';
									$amount = $sales($harvestExpected);
									if($amount > 0) {
										$h .= \util\TextUi::money($amount, precision: 0);
									}
								$h .= '</div>';
								$h .= '<div></div>';

							$h .= '</div>';

						}


						$h .= $gap;

					}

				}

			$h .= '</div>';

		$h .= '</div>';

		if($ccForecast->count() > 1) {

			$total = '<div class="util-block series-total-forecast">';
				$total .= '<div class="series-total-forecast-label">';
					$total .= s("Cultures");
				$total .= '</div>';

				$total .= '<div class="series-total-forecast-value">';
					$total .= $ccForecast->count();
				$total .= '</div>';
				$total .= '<div class="series-total-forecast-label">';
					$total .= s("Total prévisionnel");
				$total .= '</div>';

				$total .= '<div class="series-total-forecast-value">';
					if($totalForecast > 0) {
						$total .= \util\TextUi::money($totalForecast, precision: 0);
					} else {
						$total .= '/';
					}
				$total .= '</div>';

				$total .= '<div class="series-total-forecast-label">';
					$total .= s("Total planifié");
				$total .= '</div>';

				$total .= '<div class="series-total-forecast-value">';
					if($totalSeries > 0) {
						$total .= \util\TextUi::money($totalSeries, precision: 0);
					} else {
						$total .= '/';
					}
				$total .= '</div>';

				$total .= '<div>';
				$total .= '</div>';

			$total .= '</div>';

		} else {
			$total = '';
		}

		return $total.$h;

	}

	public function displayBySeedlingByStartWeek(\farm\Farm $eFarm, int $season, array $items, \Collection $cSupplier, \Search $search) {

		$h = '';

		foreach($items as $key => $item) {

			$h .= '<div class="util-title">';

				if($key) {

					$week = ($key + 1000) % 100;

					if($key < 0) {
						$year = $season - 1;
					} else if($key > 100) {
						$year = $season + 1;
					} else {
						$year = $season;
					}

					$h .= '<h3>'.s("Semaine {week}, {year}", ['week' => $week, 'year' => $year]).'</h3>';
					$h .= '<a href="'.\farm\FarmUi::urlPlanningWeekly($eFarm, $year.'-W'.sprintf('%02d', $week)).'" class="btn btn-outline-primary">'.\Asset::icon('calendar3').'</a>';

				} else {
					$h .= '<h3>'.s("Semaine non renseignée").'</h3>';
				}


			$h .= '</div>';

			$h .= $this->displayBySeedling($eFarm, $item, $cSupplier, $search);

		}

		return $h;

	}

	public function displayBySeedling(\farm\Farm $eFarm, array $items, \Collection $cSupplier, \Search $search) {

		$eSupplier = $search->get('supplier');
		$classItem = 'series-item-seeds '.($search->get('seedling') ? 'series-item-seeds-'.$search->get('seedling').'' : 'series-item-seeds-all');

		$h = '<div id="series-wrapper" class="series-item-wrapper series-item-seeds-wrapper util-overflow-md stick-sm">';

			$h .= '<div class="series-item-header '.$classItem.'">';

				$h .= '<div class="util-grid-header">';
					$h .= s("Variété");
				$h .= '</div>';
				$h .= '<div class="util-grid-header text-end series-item-sowing">';
					$h .= s("Semences<br/> à acheter");
				$h .= '</div>';
				$h .= '<div class="util-grid-header text-end series-item-young-plant">';
					$h .= s("Plants<br/> à produire");
				$h .= '</div>';
				$h .= '<div class="util-grid-header text-end series-item-young-plant-bought">';
					$h .= s("Plants<br/> à acheter");
				$h .= '</div>';
				$h .= '<div class="util-grid-header series-item-seeds-series">';
					$h .= s("Séries");
				$h .= '</div>';
				$h .= '<div class="util-grid-header"></div>';
				$h .= '<div class="util-grid-header"></div>';

			$h .= '</div>';

			$hasTargeted = FALSE;

			$h .= '<div class="series-item-body">';

				foreach($items as ['plant' => $ePlant, 'seeds' => $seeds]) {

					$h .= '<div class="series-item series-item-seeds series-item-title"  data-ref="plant-'.$ePlant['id'].'">';
						$h .= '<div class="series-item-title-plant">';
							$h .= \plant\PlantUi::getVignette($ePlant, '2.6rem');
							$h .= '<span class="series-item-title-plant-name">'.encode($ePlant['name']).'</span>';
							if($ePlant['seedsSafetyMargin'] !== NULL and $ePlant['plantsSafetyMargin'] !== NULL) {
								$h .= '<a href="/plant/plant:update?id='.$ePlant['id'].'" class="series-item-title-plant-safety">'.\Asset::icon('arrow-right').' '.s("marge de sécurité de {seedsSafetyMargin} % sur semis directs et de {plantsSafetyMargin} % sur plants autoproduits", $ePlant).'</a>';
							} else if($ePlant['seedsSafetyMargin'] !== NULL) {
								$h .= '<a href="/plant/plant:update?id='.$ePlant['id'].'" class="series-item-title-plant-safety">'.\Asset::icon('arrow-right').' '.s("marge de sécurité de {seedsSafetyMargin} % sur semis directs", $ePlant).'</a>';
							} else if($ePlant['plantsSafetyMargin'] !== NULL) {
								$h .= '<a href="/plant/plant:update?id='.$ePlant['id'].'" class="series-item-title-plant-safety">'.\Asset::icon('arrow-right').' '.s("marge de sécurité de {plantsSafetyMargin} % sur plants autoproduits", $ePlant).'</a>';
							}
						$h .= '</div>';
					$h .= '</div>';

					foreach($seeds as $seedsVariety) {

						$eVariety = $seedsVariety['variety'];

						$rows = count($seedsVariety['cultivations']);

						if($eVariety->notEmpty()) {

							$eVariety['farm'] = $eFarm;

							$isSeedSupplier = ($seedsVariety['seeds'] > 0 and $eSupplier->notEmpty() and $eVariety['supplierSeed']->notEmpty() and $eSupplier['id'] === $eVariety['supplierSeed']['id']);
							$isPlantSupplier = ($seedsVariety['youngPlantsBought'] > 0 and $eSupplier->notEmpty() and $eVariety['supplierPlant']->notEmpty() and $eSupplier['id'] === $eVariety['supplierPlant']['id']);

						} else {
							$isSeedSupplier = FALSE;
							$isPlantSupplier = FALSE;
						}

						$h .= '<div class="series-item '.$classItem.'">';

							$h .= '<div style="grid-row: span '.$rows.'">';
								if($eVariety->notEmpty()) {
									$h .= '<a href="/plant/variety?id='.$eFarm['id'].'&plant='.$ePlant['id'].'">'.encode($eVariety['name']).'</a>';
								} else {
									$h .= '<i>'.s("Non renseignée").'</i>';
								}
							$h .= '</div>';
							$h .= '<div style="grid-row: span '.$rows.'; '.($isSeedSupplier ? 'font-weight: bold;' : '').'" class="series-item-seeds-value series-item-sowing '.($seedsVariety['error'] ? 'color-danger' : ($seedsVariety['targeted'] ? 'color-warning' : '')).'">';
								if($seedsVariety['seeds'] === 0) {
									$h .= '-';
								} else {

									$number = number_format($seedsVariety['seeds'], 0, NULL, ' ');
									if($eVariety->notEmpty() and $cSupplier->notEmpty()) {
										$h .= $eVariety->quick('supplierSeed', '<span title="'.s("Modifir le fournisseur de semences").'">'.$number.'</span>');
									} else {
										$h .= $number;
									}
									if($seedsVariety['targeted']) {
										$h .= '&nbsp;*';
									}
									if($eVariety->notEmpty() and $eVariety['supplierSeed']->notEmpty()) {
										$h .= '<div class="series-item-seeds-supplier">'.encode($eVariety['supplierSeed']['name']).'</div>';
									}
									if($eVariety->notEmpty() and $eVariety['weightSeed1000'] !== NULL) {
										$h .= '<small class="color-muted">('.\plant\VarietyUi::getSeedsWeight1000($eVariety, $seedsVariety['seeds']).')</small>';
									}

								}
							$h .= '</div>';
							$h .= '<div style="grid-row: span '.$rows.'" class="series-item-seeds-value series-item-young-plant '.($seedsVariety['error'] ? 'color-danger' : ($seedsVariety['targeted'] ? 'color-warning' : '')).'">';
								if($seedsVariety['youngPlantsProduced'] === 0) {
									$h .= '-';
								} else {
									$h .= number_format($seedsVariety['youngPlantsProduced'], 0, NULL, ' ');
									if($seedsVariety['targeted']) {
										$h .= '&nbsp;*';
									}
									if($eVariety->notEmpty() and $eVariety['numberPlantKilogram'] !== NULL) {
										$h .= '<small class="color-muted"> / '.\plant\VarietyUi::getPlantsWeight($eVariety, $seedsVariety['youngPlantsProduced']).'</small>';
									}
								}
							$h .= '</div>';
							$h .= '<div style="grid-row: span '.$rows.'; '.($isPlantSupplier ? 'font-weight: bold;' : '').'" class="series-item-seeds-value series-item-young-plant-bought '.($seedsVariety['error'] ? 'color-danger' : ($seedsVariety['targeted'] ? 'color-warning' : '')).'">';
								if($seedsVariety['youngPlantsBought'] === 0) {
									$h .= '-';
								} else {
									$number = number_format($seedsVariety['youngPlantsBought'], 0, NULL, ' ');
									if($eVariety->notEmpty() and $cSupplier->notEmpty()) {
										$h .= $eVariety->quick('supplierPlant', '<span title="'.s("Modifier le fournisseur de plants").'">'.$number.'</span>');
									} else {
										$h .= $number;
									}
									if($seedsVariety['targeted']) {
										$h .= '&nbsp;*';
									}
									if($eVariety->notEmpty() and $eVariety['numberPlantKilogram'] !== NULL) {
										$h .= '<small class="color-muted"> / '.\plant\VarietyUi::getPlantsWeight($eVariety, $seedsVariety['youngPlantsBought']).'</small>';
									}
									if($eVariety->notEmpty() and $eVariety['supplierPlant']->notEmpty()) {
										$h .= '<div class="series-item-seeds-supplier">'.encode($eVariety['supplierPlant']['name']).'</div>';
									}
								}
							$h .= '</div>';

							foreach($seedsVariety['cultivations'] as $cultivation) {

								$eSeries = $cultivation['series'];
								$eCultivation = $cultivation['cultivation'];

								$h .= '<div class="series-item-seeds-series">';
									$h .= SeriesUi::link($eSeries);
									$h .= \sequence\CropUi::start($eCultivation, \farm\FarmSetting::$mainActions);
								$h  .= '</div>';
								$h .= '<div>';

									if($cultivation['error'] !== NULL) {

										[$short, $long] = match($cultivation['error']) {
											'seedling' => [s("Implantation"), s("Implantation à définir")],
											'area' => [s("Surface"), s("Surface à définir")],
											'length' => [s("Surface"), s("Longueur de planche à définir")],
											'density' => [s("Densité"), s("Densité à définir")],
										};

										$h .= '<span class="color-danger">';
											$h .= \Asset::icon('exclamation-circle').' ';
											$h .= '<span class="hide-lg-up">'.$short.'</span>';
											$h .= '<span class="hide-md-down">'.$long.'</span>';
										$h .= '</span>';

									} else if($cultivation['targeted']) {

										$h .= '<span class="color-warning">';
											$h .= \Asset::icon('exclamation-circle').' ';
											$h .= '<span class="hide-lg-up">'.s("Assolement *").'</span>';
											$h .= '<span class="hide-md-down">'.s("Assolement à définir *").'</span>';
										$h .= '</span>';
									}

								$h  .= '</div>';
							}

						$h .= '</div>';

					}
					
					if($seedsVariety['targeted']) {
						$hasTargeted = TRUE;
					}

				}

			$h .= '</div>';

		$h .= '</div>';

		if($hasTargeted) {
			$h .= $this->getWarningTargeted();
		}

		return $h;

	}

	public function getWarningTargeted(): string {

		$h = '<p class="util-warning">';
			$h .= s("* Valeur basée sur l'objectif de surface car l'assolement n'a pas encore été défini.");
		$h .= '</p>';

		return $h;

	}

	public function displayByWorkingTime(\farm\Farm $eFarm, \Collection $ccCultivation): string {

		$h = '<div id="series-wrapper" class="series-item-wrapper series-item-working-time-wrapper util-overflow-md stick-sm">';

			$h .= '<div class="series-item-header series-item-working-time">';

				$h .= '<div class="util-grid-header">';
					$h .= s("Série");
				$h .= '</div>';
				$h .= '<div class="util-grid-header text-end">';
					$h .= s("Surface");
				$h .= '</div>';
				$h .= '<div class="util-grid-header series-item-working-time-harvested text-end">';
					$h .= s("Récolté");
				$h .= '</div>';
				$h .= '<div class="util-grid-header text-center">';
					$h .= s("Travaillé");
				$h .= '</div>';
				$h .= '<div class="util-grid-header series-item-working-time-tasks">';
					$h .= s("Tâches chronophages");
				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="series-item-body">';

				foreach($ccCultivation as $cCultivation) {

					$ePlant = $cCultivation->first()['plant'];

					$cultivations = '';

					foreach($cCultivation as $eCultivation) {

						$cultivations .= '<div class="series-item series-item-working-time series-item-status-'.$eCultivation['series']['status'].'" id="series-item-'.$eCultivation['id'].'">';
							$cultivations .= '<div class="series-item-planning-details">';
								$cultivations .= $this->getSeriesForDisplay($eCultivation['series'], $eCultivation);
								$cultivations .= '<span class="series-item-planning-details-variety">'.new \sequence\SliceUi()->getLine($eCultivation, $eCultivation['cSlice']).'</span>';
							$cultivations .= '</div>';
							$cultivations .= '<div class="text-end">';
								if($eCultivation['area'] !== NULL) {
									$cultivations .= s("{value} m²", $eCultivation['area']);
								} else {
									$cultivations .= '-';
								}
							$cultivations .= '</div>';
							$cultivations .= '<div class="series-item-working-time-harvested text-end">';
								if($eCultivation['harvestedByUnit'] !== NULL) {
									$cultivations .= $this->getHarvestedByUnits($eCultivation);
								} else {
									$cultivations .= '-';
								}
							$cultivations .= '</div>';
							$cultivations .= '<div class="text-center">';
								$cultivations .= '<b>'.TaskUi::convertTime($eCultivation['totalTimeSoil'] + $eCultivation['totalTimePlant']).'</b>';
							$cultivations .= '</div>';

							$cultivations .= '<div class="series-item-working-time-tasks">';

								foreach($eCultivation['cTask'] as $eTask) {

									if($eTask['totalTime'] > 0) {

										$cultivations .= new SeriesUi()->getWorkingTimeBox($eCultivation['series'], $eCultivation, $eTask['action'], $eTask['totalTime'], $eCultivation['cTaskHarvested']);

									}

								}

							$cultivations .= '</div>';

						$cultivations .= '</div>';

					}

					$hPlant = '<div class="series-item series-item-title" id="series-item-'.$eCultivation['id'].'" data-ref="plant-'.$ePlant['id'].'">';
						$hPlant .= '<div class="series-item-title-plant">';
							$hPlant .= \plant\PlantUi::getVignette($ePlant, '2.6rem');
							$hPlant .= '<span class="series-item-title-plant-name">'.encode($ePlant['name']).'</span>';
						$hPlant .= '</div>';
					$hPlant .= '</div>';

					$h .= $hPlant;
					$h .= $cultivations;

				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function displayByHarvesting(\Collection $ccCultivation): string {

		$h = '<div id="series-wrapper" class="series-item-wrapper series-item-harvesting-wrapper util-overflow-md stick-sm">';

			$h .= '<div class="series-item-header series-item-harvesting">';

				$h .= '<div style="grid-row: span 2" class="util-grid-header">';
					$h .= s("Série");
				$h .= '</div>';
				$h .= '<div style="grid-row: span 2" class="util-grid-header text-end">';
					$h .= s("Surface");
				$h .= '</div>';
				$h .= '<div style="grid-column: span 2" class="util-grid-header series-item-header-highlight text-center">';
					$h .= s("Récolte");
				$h .= '</div>';
				$h .= '<div style="grid-column: span 2" class="util-grid-header series-item-header-highlight text-center">';
					$h .= s("Rendement par m²");
				$h .= '</div>';
				$h .= '<div style="grid-row: span 2" class="util-grid-header series-item-harvesting-weeks">';
					$h .= s("Campagne de récolte");
				$h .= '</div>';

				$h .= '<div class="util-grid-header text-end">';
				$h .= s("Obtenue");
				$h .= '</div>';

				$h .= '<div class="util-grid-header text-end">';
				$h .= s("Objectif");
				$h .= '</div>';

				$h .= '<div class="util-grid-header text-end">';
					$h .= s("Obtenu");
				$h .= '</div>';

				$h .= '<div class="util-grid-header text-end">';
					$h .= s("Objectif");
				$h .= '</div>';

			$h .= '</div>';

			$hasTargeted = FALSE;

			$h .= '<div class="series-item-body">';

				foreach($ccCultivation as $cCultivation) {

					$ePlant = $cCultivation->first()['plant'];
					$cCultivationTotal = new \Collection();

					$cultivations = '';

					$totalHarvest = [];
					$totalHarvestExpected = [];

					foreach($cCultivation as $eCultivation) {

						$unit = $eCultivation['mainUnit'];

						if($cCultivationTotal->offsetExists($unit) === FALSE) {

							$cCultivationTotal[$unit] = new Cultivation([
								'area' => NULL,
								'harvested' => NULL,
								'harvestExpected' => NULL,
								'mainUnit' => $unit
							]);

						}

						if($eCultivation['area'] !== NULL) {
							$cCultivationTotal[$unit]['area'] += $eCultivation['area'];
						}

						if($eCultivation['harvested'] !== NULL) {
							$cCultivationTotal[$unit]['harvested'] += $eCultivation['harvested'];
						}

						if($eCultivation['harvestExpected'] !== NULL) {
							$cCultivationTotal[$unit]['harvestExpected'] += $eCultivation['harvestExpected'];
						}

						if($eCultivation['harvestedByUnit'] === NULL) {
							$eCultivation['harvestedByUnit'][$eCultivation['mainUnit']] = NULL;
						}

						$position = 0;

						foreach($eCultivation['harvestedByUnit'] as $unit => $harvested) {

							$position++;

							$cultivations .= '<div class="series-item series-item-harvesting series-item-status-'.$eCultivation['series']['status'].' '.($position > 1 ? 'series-item-group' : '').'" id="series-item-'.$eCultivation['id'].'">';
								$cultivations .= '<div class="series-item-planning-details">';

									if($position === 1) {
										$cultivations .= $this->getSeriesForDisplay($eCultivation['series'], $eCultivation);
										$cultivations .= '<span class="series-item-planning-details-variety">'.new \sequence\SliceUi()->getLine($eCultivation, $eCultivation['cSlice']).'</span>';
									}

								$cultivations .= '</div>';
								$cultivations .= '<div class="text-end">';

									if($position === 1) {
										if($eCultivation['area'] !== NULL) {
											$cultivations .= s("{value} m²", $eCultivation['area']);
										} else {
											$cultivations .= '-';
										}
									}

								$cultivations .= '</div>';
								$cultivations .= '<div class="series-item-harvesting-yield text-end">';

									if($harvested !== NULL) {

										$cultivations .= '<a href="/series/cultivation:harvest?id='.$eCultivation['id'].'" style="color: inherit">'.\selling\UnitUi::getValue($harvested, $unit, TRUE).'</a>';

										$totalHarvest[$unit] ??= 0;
										$totalHarvest[$unit] += $harvested;

									} else {
										$cultivations .= '-';
									}
								$cultivations .= '</div>';
								$cultivations .= '<div class="series-item-harvesting-yield text-end">';
									if($eCultivation['mainUnit'] === $unit) {

										if($eCultivation['harvestExpected'] !== NULL) {

											$cultivations .= $eCultivation->format('harvestExpected', ['short' => TRUE]);

											$totalHarvestExpected[$eCultivation['mainUnit']] ??= 0;
											$totalHarvestExpected[$eCultivation['mainUnit']] += $eCultivation['harvestExpected'];

										} else if($eCultivation['harvestExpectedTarget'] !== NULL) {

											$cultivations .= '<span class="color-warning">'.$eCultivation->format('harvestExpectedTarget', ['short' => TRUE]).' *</span>';
											$hasTargeted = TRUE;

											$totalHarvestExpected[$eCultivation['mainUnit']] ??= 0;
											$totalHarvestExpected[$eCultivation['mainUnit']] += $eCultivation['harvestExpectedTarget'];

										} else {
											$cultivations .= '-';
										}

									} else {
										$cultivations .= '-';
									}
								$cultivations .= '</div>';
								$cultivations .= '<div class="series-item-harvesting-yield text-end">';

									if($eCultivation['yieldByUnit'] !== NULL and $eCultivation['mainUnit'] === $unit and $eCultivation['series']['area'] > 0 and $eCultivation['yieldByUnit'][$eCultivation['mainUnit']] !== NULL) {

										$yield = round($eCultivation['yieldByUnit'][$eCultivation['mainUnit']] / $eCultivation['series']['area'], 1);
										$cultivations .= '<b>'.\selling\UnitUi::getValue($yield, $unit, TRUE).'</b>';

										if($eCultivation['mainUnit'] === $unit and $eCultivation['harvestExpected'] !== NULL) {

											$value = round(($eCultivation['yieldByUnit'][$eCultivation['mainUnit']] / $eCultivation['harvestExpected'] - 1) * 100);

											$cultivations .= '<div class="series-item-harvesting-evolution">';
											if($value < 0) {
												$cultivations .= '<span class="color-danger">'.$value.' %</span>';
											} else if($value > 0) {
												$cultivations .= '<span class="color-success">+'.$value.' %</span>';
											} else {
												$cultivations .= '=';
											}
											$cultivations .= '</div>';

										}

									} else {
										$cultivations .= '-';
									}

								$cultivations .= '</div>';
								$cultivations .= '<div class="series-item-harvesting-yield-expected text-end">';
									if($eCultivation['mainUnit'] === $unit and $eCultivation['yieldExpected'] !== NULL) {
										$cultivations .= '<span class="color-muted">'.$eCultivation->format('yieldExpected', ['short' => TRUE]).'</span>';
									} else {
										$cultivations .= '-';
									}
								$cultivations .= '</div>';

								$cultivations .= '<div class="series-item-harvesting-weeks">';

									if($eCultivation['harvesting']['firstHarvest'] !== NULL) {
										$cultivations .= self::getHarvestInterval($eCultivation['harvesting']['firstHarvest'], $eCultivation['harvesting']['lastHarvest']);
										if($eCultivation['harvesting']['firstHarvest'] !== $eCultivation['harvesting']['lastHarvest']) {
											$cultivations .= '<div class="series-item-harvesting-evolution color-muted">'.p("{value} semaine", "{value} semaines", $eCultivation['harvesting']['nHarvest']).'</div>';
										}
									} else {
										$cultivations .= '-';
									}

								$cultivations .= '</div>';

							$cultivations .= '</div>';
						}

					}

					$cCultivationTotal->ksort();

					$hPlant = '<div class="series-item-harvesting series-item series-item-title" id="series-item-'.$eCultivation['id'].'" data-ref="plant-'.$ePlant['id'].'">';
						$hPlant .= '<div class="series-item-title-plant" style="grid-column: span 2">';
							$hPlant .= \plant\PlantUi::getVignette($ePlant, '2.25rem');
							$hPlant .= '<span class="series-item-title-plant-name">'.encode($ePlant['name']).'</span>';
						$hPlant .= '</div>';
						if($cCultivation->count() > 1) {
							$hPlant .= '<div class="series-item-harvesting-yield text-end">';
								foreach($totalHarvest as $unit => $value) {
									$hPlant .= '<div>'.\selling\UnitUi::getValue($value, $unit, TRUE).'</div>';
								}
							$hPlant .= '</div>';
							$hPlant .= '<div class="series-item-harvesting-yield text-end">';
								foreach($totalHarvestExpected as $unit => $value) {
									$hPlant .= '<div>'.\selling\UnitUi::getValue($value, $unit, TRUE).'</div>';
								}
							$hPlant .= '</div>';
						}
					$hPlant .= '</div>';

					$h .= $hPlant;
					$h .= $cultivations;

				}

			$h .= '</div>';

		$h .= '</div>';

		if($hasTargeted) {
			$h .= $this->getWarningTargeted();
		}

		return $h;

	}

	public static function getHarvestInterval(string $first, string $last): string {

		if($first !== $last) {
			return s("Semaines {from} à {to}", ['from' => date('W', $first), 'to' => date('W', $last)]).', '.date('Y', $last);
		} else {
			return s("Semaine {value}", date('W', $first)).' '.date('Y', $first);
		}

	}

	protected function getSeriesForDisplay(Series $eSeries, Cultivation $eCultivation = new Cultivation(), string $tag = 'a'): string {

		$h = '<'.$tag.' href="/serie/'.$eSeries['id'].'" class="series-item-planning-details-name">'.SeriesUi::name($eSeries).'</'.$tag.'> ';

		if($eCultivation->notEmpty()) {
			$h .= \sequence\CropUi::start($eCultivation, \farm\FarmSetting::$mainActions);
		}

		if($eSeries->isClosed()) {
			$h .= \Asset::icon('lock-fill', ['style' => 'margin-left: 0.25rem']);
		}

		if($eSeries['cycle'] === Series::PERENNIAL) {
			$h .= '<span class="series-item-planning-details-cycle">'.s("Année {value}", $eSeries['perennialSeason']).'</span>';
		}

		return $h;

	}

	public function harvest(Cultivation $eCultivation, \Collection $cTask): \Panel {

		$varieties = $cTask->reduce(fn(Task $eTask, int $n) => $n + $eTask['variety']->notEmpty(), 0);

		$h = '<table>';

			$h .= '<thead>';
				$h .= '<tr>';
					if($varieties) {
						$h .= '<th>'.s("Variété").'</th>';
					}
					$h .= '<th>'.s("Qualité").'</th>';
					$h .= '<th class="text-end">'.s("Récolte").'</th>';
						$h .= '<th class="text-end">'.s("Rendement").'</th>';
					$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

				foreach($cTask as $eTask) {

					$area = NULL;

					if($eTask['variety']->notEmpty()) {
						foreach($eCultivation['cSlice'] as $eSlice) {
							if($eSlice['variety']->is($eTask['variety'])) {
								$area = $eSlice['area'];
							}
						}
					} else {
						$area = $eCultivation['series']['area'];
					}

					$h .= '<tr>';
						if($varieties) {
							$h .= '<td>'.($eTask['variety']->empty() ? '<i>'.s("Non spécifiée").'</u>' : encode($eTask['variety']['name'])).'</td>';
						}
						$h .= '<td>'.($eTask['harvestSize']->empty() ? '-' : encode($eTask['harvestSize']['name'])).'</td>';
						$h .= '<td class="text-end">'.\selling\UnitUi::getValue(sprintf('%.1f', $eTask['totalHarvest']), $eTask['harvestUnit'], TRUE).'</td>';
						$h .= '<td class="text-end">';
							if($area > 0) {
								$h .= s("{value} / m²", \selling\UnitUi::getValue(sprintf('%.2f', $eTask['totalHarvest'] / $area), $eTask['harvestUnit'], TRUE));
							} else {
								$h .= '-';
							}
							$h .= '</td>';
					$h .= '</tr>';

				}

			$h .= '<tbody>';

		$h .= '</table>';

		return new \Panel(
			id: 'panel-series-harvest',
			title: s("Récoltes"),
			subTitle: SeriesUi::getPanelHeader($eCultivation['series']),
			body: $h
		);

	}

	public function getTimeline(\farm\Farm $eFarm, int $season, Series $eSeries, Cultivation $eCultivation, \Collection $cTask): string {

		$eCultivation->expects(['harvestWeeks', 'harvestWeeksExpected']);

		$uiPlace = new PlaceUi();

		$items = [];

		[$startTs, $stopTs] = $uiPlace->getBounds($eFarm, $season);

		[$minTs, $maxTs] = $this->getTimestampBounds($eSeries, $eCultivation, $cTask);

		// Récolte réalisée
		$showHarvest = function(bool $expected, bool $letter, int $first, int $last, \Closure $label) use($uiPlace, $eCultivation, $startTs, $stopTs, $minTs, $maxTs) {

			if($expected) {
				$css = 'background: repeating-linear-gradient(135deg, var(--harvest) 0, var(--harvest) 1px, #fff8 1px, #fff8 7px); color: var(--harvest); border-color: var(--harvest)';
				$class = 'series-item-timeline-event-expected';
			} else {
				$css = 'background-color: var(--harvest);';
				$class = 'series-item-timeline-event-done';
			}

			$harvestFirstWeek = date('W', $first);
			$harvestLastWeek = date('W', $last);

			if($harvestFirstWeek === $harvestLastWeek) {
				$week = s("S{value}", $harvestFirstWeek);
			} else {
				$week = s("S{from} à S{to}", ['from' => $harvestFirstWeek, 'to' => $harvestLastWeek]);
			}

			$id = uniqid('series-item-');

			$item = '<div id="'.$id.'" class="series-item-timeline-event '.$class.'" style="'.$css.'" title="'.$week.' - '.$label($eCultivation).'">';
				if($letter) {
					$item .= '<span>'.s("R").'</span>';
				}
			$item .= '</div>';

			$item .= '<style>';
				$item .= $uiPlace->getPositionStyle($id, $startTs, $stopTs, $first, $last, fixThreshold: 3, fixDirection: 'left');
			$item .= '</style>';

			return [$first, $last, $item];

		};

		// Récolte attendue
		if($eCultivation['harvestWeeksExpected'] !== NULL) {

			$label = fn(Cultivation $eCultivation) => s("Récolte attendue de {plant}", ['plant' => encode($eCultivation['plant']['name'])]);

			$first = NULL;
			$last = NULL;

			foreach($eCultivation['harvestWeeksExpected'] as $week) {

				$current = strtotime($week.' + 3 DAY');

				if($first !== NULL) {

					$difference = ($current - $last) / 86400;

					if($difference > 45) {
						$items[] = $showHarvest(TRUE, ($eCultivation['harvestWeeks'] === NULL), $first, $last, $label);
						$first = NULL;
					}

				}

				$first ??= $current;
				$last = $current;

			}

			if($first !== NULL and $last !== NULL) {
				$items[] = $showHarvest(TRUE, ($eCultivation['harvestWeeks'] === NULL), $first, $last, $label);
			}

		}

		if($eCultivation['harvestWeeks'] !== NULL) {

			$label = fn(Cultivation $eCultivation) => s("Récolte de {plant}", ['plant' => encode($eCultivation['plant']['name'])]);

			$first = NULL;
			$last = NULL;

			foreach($eCultivation['harvestWeeks'] as $week) {

				$current = strtotime($week.' + 3 DAY');

				if($first !== NULL) {

					$difference = ($current - $last) / 86400;

					if($difference > 45) {
						$items[] = $showHarvest(FALSE, TRUE, $first, $last, $label);
						$first = NULL;
					}

				}

				$first ??= $current;
				$last = $current;


			}

			if($first !== NULL and $last !== NULL) {
				$items[] = $showHarvest(FALSE, TRUE, $first, $last, $label);
			}

		}

		// Interventions
		foreach($cTask as $eTask) {

			$eAction = $eTask['action'];

			$week = $eTask['doneWeek'] ?? $eTask['plannedWeek'];

			if($week === NULL) {
				continue;
			}

			$ts = strtotime($week);

			if($eTask['status'] === Task::DONE) {
				$color = 'background-color: '.$eAction['color'].';';
				$class = 'series-item-timeline-event-done';
			} else {
				$color = 'color: '.$eAction['color'].'; border-color: '.$eAction['color'].';';
				$class = 'series-item-timeline-event-expected';
			}

			$id = uniqid('series-item-');

			$item = '<div id="'.$id.'" class="series-item-timeline-event '.$class.'" style="'.$color.'" title="'.s("S{week} - {action} de {plant}", ['week' => week_number($week), 'action' => encode($eAction['name']), 'plant' => encode($eCultivation['plant']['name'])]).'">';
				$item .= \farm\ActionUi::getShort($eAction);
			$item .= '</div>';

			$item .= '<style>';
				$item .= $uiPlace->getPositionStyle($id, $startTs, $stopTs, $ts, $ts, width: '1.2rem');
			$item .= '</style>';

			$items[] = [$ts, $ts, $item];

		}

		if($eSeries['season'] !== $season) {
			$classMuted = 'bed-item-cultivation-muted';
		} else {
			$classMuted = '';
		}

		$full = ($minTs !== $maxTs);

		$h = '<div class="series-timeline-season series-item-timeline '.($full ? 'series-item-timeline-full' : '').' '.$classMuted.'">';

			if($full) {

				$id = uniqid('series-item-');

				$h .= '<div id="'.$id.'" class="series-item-timeline-event series-item-timeline-event-full">';
				$h .= '</div>';

				$h .= '<style>';
					$h .= $uiPlace->getPositionStyle($id, $startTs, $stopTs, $minTs, $maxTs);
				$h .= '</style>';

			}

			foreach($items as [$first, $last, $item]) {
				$class = '';
				if($first === $minTs) {
					$class .= 'series-item-timeline-event-min ';
				}
				if($last === $maxTs) {
					$class .= 'series-item-timeline-event-max ';
				}
				if($class) {
					$h .= '<div class="'.$class.'">'.$item.'</div>';
				} else {
					$h .= $item;
				}
			}

		$h .= '</div>';

		return $h;

	}

	public function getTimestampBounds(Series $eSeries, Cultivation $eCultivation, \Collection $cTask): array {

		$minTs = NULL;
		$maxTs = NULL;

		if($eSeries['cycle'] === Series::PERENNIAL) {

			if($eSeries['perennialSeason'] > 1) {
				$minTs = mktime(0, 0, 0, 1, 0, $eSeries['season']);
			}

			if($eSeries['perennialLifetime'] === NULL or $eSeries['perennialSeason'] < $eSeries['perennialLifetime']) {

				$maxTs = mktime(0, 0, 0, 12, 31, $eSeries['season']);

			}

		}

		[$minHarvest, $maxHarvest] = $eCultivation->getHarvestBounds();

		$minHarvestTs = ($minHarvest !== NULL) ? strtotime($minHarvest.' + 3 DAY') : NULL;
		$maxHarvestTs = ($maxHarvest !== NULL) ? strtotime($maxHarvest.' + 3 DAY') : NULL;

		$minTs = ($minTs === NULL) ? $minHarvestTs : min($minTs, $minHarvestTs);
		$maxTs = ($maxTs === NULL) ? $maxHarvestTs : max($maxTs, $maxHarvestTs);

		// Interventions
		foreach($cTask as $eTask) {

			$week = $eTask['doneWeek'] ?? $eTask['plannedWeek'];

			if($week === NULL) {
				continue;
			}

			$ts = strtotime($week);

			$minTs = ($minTs === NULL) ? $ts : min($minTs, $ts);
			$maxTs = ($maxTs === NULL) ? $ts : max($maxTs, $ts);

		}


		if(
			$eSeries['cycle'] === Series::PERENNIAL and
			$minTs === NULL
		) {
			$minTs = mktime(0, 0, 0, 1, 0, $eSeries['season']);
		}


		if(
			$eSeries['cycle'] === Series::PERENNIAL and
			$maxTs === NULL
		) {
			$maxTs = mktime(0, 0, 0, 12, 31, $eSeries['season']);
		}

		return [$minTs, $maxTs];

	}

	public function getHeader(Series $eSeries): string {

		$h = '<div class="util-action">';

			$h .= '<div>';

				$h .= '<h1 style="margin-bottom: 0.25rem">';
					$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eSeries['farm']).'" class="h-back">'.\Asset::icon('arrow-left').'</a>';
					$h .= $eSeries->quick('name', SeriesUi::name($eSeries));
					if($eSeries['status'] === Series::CLOSED) {
						$h .= '<span class="series-header-closed" title="'.s("Série clôturée").'">'.\Asset::icon('lock-fill').'</span>';
					}
				$h .= '</h1>';

				$infos = [];

				if($eSeries['cycle'] === Series::PERENNIAL) {
					$start = $eSeries['season'] - $eSeries['perennialSeason'] + 1;
					if($eSeries['perennialLifetime'] !== NULL) {
						$perennial = p("Culture pérenne en place depuis la saison {start} pour {value} an", "Culture pérenne en place depuis la saison {start} pour {value} ans", $eSeries['perennialLifetime'], ['start' => $start]);
					} else {
						$perennial = s("Culture pérenne en place depuis la saison {start}", ['start' => $start]);
					}
				} else {
					$perennial = s("Culture annuelle");
				}

				$infos[] = $perennial;

				if($eSeries['sequence']->notEmpty()) {
					$infos[] = s("Itinéraire technique {value}", '<u>'.\sequence\SequenceUi::link($eSeries['sequence']).'</u>');
				}

				$h .= '<div>';
					$h .= '<div class="util-badge bg-secondary" style="margin-right: 0.5rem">'.s("Saison {value}", $eSeries['season']).'</div>';
					$h .= implode(' | ', $infos);
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div>';

				if($eSeries->canWrite()) {

					$h .= '<a data-dropdown="bottom-end" class="btn btn-primary dropdown-toggle">'.\Asset::icon('gear-fill').'</a>';
					$h .= '<div class="dropdown-list">';
						$h .= '<div class="dropdown-title">'.encode($eSeries['name']).'</div>';
						if($eSeries['status'] === Series::OPEN) {
							$h .= '<a href="/series/series:update?id='.$eSeries['id'].'" class="dropdown-item">'.s("Modifier la série").'</a>';
							$h .= '<a data-ajax="/series/series:updateComment" post-id="'.$eSeries['id'].'" class="dropdown-item">'.s("Ajouter des notes sur cette série").'</a>';
							$h .= '<a href="/series/cultivation:create?series='.$eSeries['id'].'" class="dropdown-item">'.s("Ajouter une autre production").'</a>';
							$h .= '<div class="dropdown-divider"></div>';
						}

						if($eSeries->acceptOpen()) {
							$h .= '<a data-ajax="/series/series:doUpdateStatus" post-id="'.$eSeries['id'].'" post-status="'.Series::OPEN.'" class="dropdown-item">'.s("Réouvrir la série").'</a>';
						}

						if($eSeries->acceptClose()) {
							$h .= '<a data-ajax="/series/series:doUpdateStatus" post-id="'.$eSeries['id'].'" post-status="'.Series::CLOSED.'" class="dropdown-item" data-confirm="'.s("Une série clôturée est une série pour laquelle vous avez terminé toutes les interventions culturales. Confirmer ?").'">'.s("Clôturer la série").'</a>';
						}
						if($eSeries->acceptDuplicate()) {
							$h .= '<a href="/series/series:duplicate?ids[]='.$eSeries['id'].'" class="dropdown-item">'.s("Dupliquer la série").'</a>';
							$h .= '<div class="dropdown-divider"></div>';
						}
						$h .= '<a data-ajax="/series/series:doDelete" post-id="'.$eSeries['id'].'" data-confirm="'.s("Souhaitez-vous réellement supprimer cette série de votre plan de culture ?").'" class="dropdown-item">'.s("Supprimer la série").'</a>';
					$h .= '</div>';

				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getList(Series $eSeries, \Collection $cSeriesPerennial, \Collection $cCultivation, \Collection $cTask, \Collection $cPlace, \Collection $cActionMain): string {

		$h = '';

		if($eSeries['cycle'] === Series::PERENNIAL) {

			$h .= '<div class="series-header-perennial-seasons util-card">';

				$h .= '<div class="series-header-perennial-seasons-label">'.s("Année").'</div>';

				for($i = 1; $cSeriesPerennial->notEmpty(); $i++) {

					if($i > 1) {
						$h .= '<div class="series-header-perennial-seasons-separator"></div>';
					}

					if($cSeriesPerennial->offsetExists($i)) {

						$eSeriesPerennial = $cSeriesPerennial[$i];

						$h .= '<a href="'.SeriesUi::url($eSeriesPerennial).'" class="series-header-perennial-seasons-one '.($eSeriesPerennial['perennialSeason'] === $eSeries['perennialSeason'] ? 'series-header-perennial-seasons-one-selected' : '').'">'.$eSeriesPerennial['perennialSeason'].'</a>';

						$cSeriesPerennial->offsetUnset($i);

					} else {
						$h .= '<div class="series-header-perennial-seasons-one series-header-perennial-seasons-one-future">'.$i.'</div>';
					}

				}

				if($eSeries['perennialLifetime'] !== NULL) {

					for($i = $eSeriesPerennial['perennialSeason'] + 1; $i <= $eSeries['perennialLifetime']; $i++) {

						$h .= '<div class="series-header-perennial-seasons-separator"></div>';
						$h .= '<div class="series-header-perennial-seasons-one series-header-perennial-seasons-one-future">'.$i.'</div>';

					}

				}

			$h .= '</div>';

		}

		$h .= '<div class="crop-items">';

		foreach($cCultivation as $eCultivation) {

			$h .= '<div class="crop-item">';
				$h .= $this->getOne($eSeries, $eCultivation, $cTask, $cActionMain);
			$h .= '</div>';

		}

		$h .= '</div>';

		$h .= new SeriesUi()->updatePlace($eSeries, $cPlace);
		$h .= new SeriesUi()->getComment($eSeries);

		return $h;

	}

	public function getOne(Series $eSeries, Cultivation $eCultivation, \Collection $cTask, \Collection $cActionMain): string {

		$ePlant = $eCultivation['plant'];

		$h = '<div class="crop-item-header">';

			$h .= '<div class="crop-item-title">';
				$h .= \plant\PlantUi::getVignette($ePlant, '3rem').' ';
				$h .= '<h2>';
					$h .= \plant\PlantUi::link($ePlant);
					$h .= \sequence\CropUi::start($eCultivation, $cActionMain, fontSize: '0.7em');
				$h .= '</h2>';

				if(
					$eSeries->canWrite() and
					$eSeries['status'] === Series::OPEN
				) {

					$h .= '<div>';
						$h .= '<a data-dropdown="bottom-end" class="btn btn-color-primary dropdown-toggle" title="'.s("Ajouter une intervention").'">'.\Asset::icon('calendar-plus').'</a>';
						$h .= '<div class="dropdown-list">';
							$h .= '<div class="dropdown-title">'.encode($ePlant['name']).'</div>';
							$h .= '<a href="/series/task:createFromSeries?farm='.$eSeries['farm']['id'].'&series='.$eSeries['id'].'&cultivation='.$eCultivation['id'].'&status='.Task::TODO.'" class="dropdown-item">'.s("Planifier une future intervention").'</a>';
							$h .= '<a href="/series/task:createFromSeries?farm='.$eSeries['farm']['id'].'&series='.$eSeries['id'].'&cultivation='.$eCultivation['id'].'&status='.Task::DONE.'" class="dropdown-item">'.s("Ajouter une intervention déjà réalisée").'</a>';
							$h .= '<a href="/series/task:createFromSeries?farm='.$eSeries['farm']['id'].'&series='.$eSeries['id'].'&cultivation='.$eCultivation['id'].'&doneWeek='.currentWeek().'&action='.$cActionMain[ACTION_RECOLTE]['id'].'&status='.Task::DONE.'" class="dropdown-item">'.s("Saisir une récolte").'</a>';
						$h .= '</div>';
						$h .= ' <a data-dropdown="bottom-end" class="btn btn-color-primary dropdown-toggle">'.\Asset::icon('gear-fill').'</a>';
						$h .= '<div class="dropdown-list">';
							$h .= '<div class="dropdown-title">'.encode($ePlant['name']).'</div>';
							$h .= '<a href="/series/cultivation:update?id='.$eCultivation['id'].'" class="dropdown-item">'.s("Modifier la production").'</a>';
							if($eSeries['plants'] > 1) {
								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<a data-ajax="/series/cultivation:doDelete" post-id="'.$eCultivation['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de la production de la série ?").'">'.s("Supprimer la production").'</a>';
							}
						$h .= '</div>';
					$h .= '</div>';
				}

			$h .= '</div>';

			$h .= new \sequence\CropUi()->getVarieties($eCultivation, $eCultivation['cSlice']);

		$h .= '</div>';

		$h .= '<div class="crop-item-presentation">';

			$filled = 0;
			$presentation = $this->getPresentation($eSeries, $eCultivation, $filled);

			if($filled > 0) {
				$h .= $presentation;
			} else {
				$h .= '<div class="text-center">';
					$h .= '<a href="/series/cultivation:update?id='.$eCultivation['id'].'" class="btn mt-1 mb-1 btn-outline-primary">'.s("Configurer maintenant").'</a>';
				$h .= '</div>';
			}

		$h .= '</div>';

		$infos = $this->getVarietiesInfo($eCultivation, $cTask);

		if($infos) {
			$h .= '<div class="crop-item-info">';
				foreach($infos as $info) {
					$h .= '<div>'.$info.'</div>';
				}
			$h .= '</div>';
		}

		return $h;

	}

	protected function getVarietiesInfo(Cultivation $eCultivation, \Collection $cTask): array {

		$infos = [];

		switch($eCultivation['sliceUnit']) {

			case Cultivation::PERCENT :

				if($eCultivation['cSlice']->notEmpty()) {

					$sum = $eCultivation['cSlice']->sum('partPercent');

					if($sum < 100) {
						$infos[] = s("La production couvre {value} % de l'assolement.", $sum);
					} else if($sum > 100) {
						$infos[] = \Asset::icon('exclamation-circle').'  '.s("La production couvre plus de 100 % de l'assolement.");
					}

				}

				break;

			case Cultivation::LENGTH :

				if($eCultivation['series']['use'] === Series::BLOCK) {
					$infos[] = \Asset::icon('exclamation-circle').'  '.s("Les variétés ont été réparties au mL alors que la culture est sur surface au libre au m².");
				} else {

					$sum = $eCultivation['cSlice']->sum('partLength');
					$limit = $eCultivation['series']['length'] ?? $eCultivation['series']['lengthTarget'];

					if(
						$limit !== NULL and
						$sum !== $limit
					) {

						$text = s("La production couvre {value} / {limit} mL.", ['limit' => $limit, 'value' => $sum]);

						if($sum < $limit) {
							$infos[] = $text;
						} else if($sum > $limit) {
							$infos[] = \Asset::icon('exclamation-circle').'  '.$text;
						}
					}

				}

				break;

			case Cultivation::AREA :

				if($eCultivation['series']['use'] === Series::BED) {
					$infos[] = \Asset::icon('exclamation-circle').'  '.s("Les variétés ont été réparties au m³ alors que la culture est sur planches au mL.");
				} else {

					$sum = $eCultivation['cSlice']->sum('partArea');
					$limit = $eCultivation['series']['area'] ?? $eCultivation['series']['areaTarget'];

					if(
						$limit !== NULL and
						$sum !== $limit
					) {

						$text = s("La production couvre {value} / {limit} m².", ['limit' => $limit, 'value' => $sum]);

						if($sum < $limit) {
							$infos[] = $text;
						} else if($sum > $limit) {
							$infos[] = \Asset::icon('exclamation-circle').'  '.$text;
						}
					}

				}

				break;

			case Cultivation::PLANT :

				$sum = $eCultivation['cSlice']->sum('partPlant');

				if($eCultivation['seedling'] === Cultivation::SOWING) {

					$limit = $eCultivation->getSeeds();

					if(
						$limit !== NULL and
						$sum !== $limit
					) {
						$infos[] = s("La production demande {limit} graines mais vous avez réparti les variétés sur {value} graines.", ['limit' => $limit, 'value' => $sum]);
					}

				} else {

					$limit = $eCultivation->getYoungPlants();

					if(
						$limit !== NULL and
						$sum !== $limit
					) {
						$infos[] = s("La production demande {limit} plants mais vous avez réparti les variétés sur {value} plants.", ['limit' => $limit, 'value' => $sum]);
					}

				}

				break;

			case Cultivation::TRAY :

				$sum = $eCultivation['cSlice']->sum('partTray');

				if($eCultivation['seedling'] === Cultivation::SOWING) {
					$infos[] = \Asset::icon('exclamation-circle').'  '.s("Vous avez réparti les variétés en nombre de plateaux de semis alors que la production est implantée en semis direct.");
				} else {

					$plants = $eCultivation->getYoungPlants();
					$limit = $plants ? (int)ceil($plants / $eCultivation['sliceTool']['routineValue']['value']) : NULL;

					if(
						$limit !== NULL and
						$sum !== $limit
					) {
						$infos[] = \Asset::icon('exclamation-circle').'  '.s("La production demande {limit} plateaux de semis mais vous avez réparti les variétés sur {value} plateaux.", ['limit' => $limit, 'value' => $sum]);
					}

					$tools = array_merge(...$cTask
						->find(fn($eTask) => (
							$eTask['action']['fqn'] === ACTION_SEMIS_PEPINIERE and
							($eTask['cultivation']->empty() or $eTask['cultivation']->is($eCultivation))
						))
						->getColumn('tools'));

					if(
						$tools !== [] and
						in_array($eCultivation['sliceTool']['id'], $tools) === FALSE
					) {
						$infos[] = \Asset::icon('exclamation-circle').'  '.s("Vous avez réparti les variétés avec des plateaux de semis {value} alors que les interventions de semis en pépinière sur la série utilisent d'autres plateaux.", '<u>'.encode($eCultivation['sliceTool']['name']).'</u>');
					}

				}

				break;

		}

		return $infos;

	}

	protected function getPresentation(Series $eSeries, Cultivation $eCultivation, int &$filled): string {

		$uiCrop = new \sequence\CropUi();

		$h = '<dl class="util-presentation util-presentation-max-content util-presentation-2">';

			$h .= $uiCrop->getPresentationYieldExpected($eSeries, $eCultivation, $filled);

			$h .= $uiCrop->getPresentationDistance($eSeries, $eCultivation, $filled);
			$h .= '<dt>'.$this->p('yield').'</dt>';
			$h .= '<dd>'.$this->getYieldByUnits($eSeries, $eCultivation, $filled).'</dd>';

			$h .= '<dt>'.s("Implantation").'</dt>';
			$h .= '<dd>';
				if($eCultivation['seedling']) {

					$filled++;

					$h .= [
						\series\Cultivation::SOWING => s("semis direct"),
						\series\Cultivation::YOUNG_PLANT => '<span title="'.s("Autoproduction du plant").'">'.s("plant autoproduit").'</span>',
						\series\Cultivation::YOUNG_PLANT_BOUGHT => '<span title="'.s("Achat du plant").'">'.s("plant acheté").'</span>'
					][$eCultivation['seedling']];

				}
			$h .= '</dd>';

			$h .= '<dt>'.$this->p('harvested').'</dt>';
			$h .= '<dd>'.$this->getHarvestedByUnits($eCultivation, $filled).'</dd>';

			$h .= $uiCrop->getPresentationSeedlingSeeds($eSeries, $eCultivation);

			$h .= '<dt class="crop-item-harvest-label">'.self::p('harvestMonths')->label.'</dt>';

			if($eCultivation['harvestMonthsExpected'] !== NULL) {

				$h .= '<dd class="crop-item-harvest-value">';
					$h .= $this->getPeriod($eSeries['season'], 'month', $eSeries, $eCultivation, filled: $filled);
				$h .= '</dd>';

			} else {

				$h .= '<dd class="crop-item-harvest-value color-muted">';
					$h .= $this->getPeriod($eSeries['season'], 'month', $eSeries, $eCultivation, filled: $filled);
				$h .= '</dd>';

			}

		$h .= '</dl>';

		return $h;

	}

	public function getHarvestedByUnits(Cultivation $eCultivation, ?int &$filled = 0): ?string {

		if($eCultivation['harvestedByUnit'] === NULL) {
			return NULL;
		}

		$this->sortUnits($eCultivation);

		$harvested = [];

		foreach($eCultivation['harvestedByUnit'] as $unit => $value) {
			if($value > 0) {
				$harvested[] = \selling\UnitUi::getValue($value, $unit, TRUE);
			}
		}

		if($harvested) {
			$filled++;
			return '<a href="/series/cultivation:harvest?id='.$eCultivation['id'].'">'.implode('<br/>', $harvested).'</a>';
		} else {
			return NULL;
		}

	}

	public function getYieldByUnits(Series $eSeries, Cultivation $eCultivation, ?int &$filled = 0): ?string {

		if($eCultivation['harvestedByUnit'] === NULL) {
			return NULL;
		}

		$values = [];

		foreach($eCultivation['yieldByUnit'] as $unit => $value) {
			if($eSeries['area'] > 0 and $value > 0) {
				$values[] = s("{value} / m²", \selling\UnitUi::getValue(round($value / $eSeries['area'], 1), $unit, TRUE));
			}
		}

		if($values) {
			$filled++;
		}

		return $values ? implode('<br/>', $values) : NULL;

	}

	protected function sortUnits(Cultivation $eCultivation): void {

		$eCultivation->expects(['harvestedByUnit']);

		if($eCultivation['harvestedByUnit'] === NULL) {
			throw new \Exception('harvestedByUnit must not be NULL');
		}

		uksort($eCultivation['harvestedByUnit'], function($key1, $key2) use($eCultivation) {

			if($key1 === $eCultivation['mainUnit']) {
				return -1;
			}

			if($key2 === $eCultivation['mainUnit']) {
				return 1;
			}

			return strcmp($key1, $key2);

		});

	}

	public function getPeriod(int $season, string $interval, Series $eSeries, Cultivation $eCultivation, ?\util\FormUi $form = NULL, ?string $name = NULL, ?int $filled = 0): string {

		$eSeries->expects([
			'cycle'
		]);

		if($eSeries['cycle'] === Series::PERENNIAL) {

			$months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

			$h = '<div class="cultivation-periods cultivation-periods-00 '.($form === NULL ? '' : 'cultivation-periods-form').'">';
				$h .= '<div class="cultivation-periods-season cultivation-periods-season-12">';
					$h .= match($interval) {
						'month' => $this->getPeriodMonths($form, $name, $this->getHarvestMonths($eCultivation, $form, $season, $months)),
						'week' => $this->getPeriodWeeks($eCultivation, $form, $name, $season, $months)
					};
				$h .= '</div>';
			$h .= '</div>';

		} else {

			$monthsList = [
				$season - 1 => [10, 11, 12],
				$season => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
				$season + 1 => [1, 2, 3, 4, 5, 6],
			];

			$harvestMonths = [];
			$harvestMonthsFilled = [];

			foreach($monthsList as $year => $months) {

				$harvestMonths[$year] = $this->getHarvestMonths($eCultivation, $form, $year, $months);
				$harvestMonthsFilled[$year] = array_filter($harvestMonths[$year]);

			}

			if($harvestMonthsFilled) {
				$filled++;
			}

			if($form !== NULL) {
				$formSequence = '11';
			} else {
				$formSequence = ($harvestMonthsFilled[$season - 1] ? '1' : '0').''.($harvestMonthsFilled[$season + 1] ? '1' : '0');
			}

			$h = '<div class="cultivation-periods cultivation-periods-'.$formSequence.' '.($form === NULL ? '' : 'cultivation-periods-form').'">';

			foreach($monthsList as $year => $months) {

				if(
					$form === NULL and
					$year !== $season and
					$harvestMonthsFilled[$year] === []
				) {
					continue;
				}

				$h .= '<div class="cultivation-periods-season cultivation-periods-season-'.count($months).'">';
					$h .= '<div class="cultivation-periods-year" style="grid-column: span '.count($months).'">'.$year.'</div>';
					$h .= match($interval) {
						'month' => $this->getPeriodMonths($form, $name, $harvestMonths[$year]),
						'week' => $this->getPeriodWeeks($eCultivation, $form, $name, $year, $months)
					};
				$h .= '</div>';

			}

			$h .= '</div>';

		}

		return $h;

	}

	protected function getHarvestMonths(Cultivation $eCultivation, ?\util\FormUi $form, int $year, array $months): array {

		$harvestMonths = [];

		foreach($months as $month) {
			$harvestMonths[$year.'-'.sprintf('%02d', $month)] = NULL;
		}

		if($form === NULL) {

			$eCultivation->expects(['harvestMonths', 'harvestMonthsExpected']);

			foreach($eCultivation['harvestMonths'] ?? [] as $monthHarvest) {
				if(array_key_exists($monthHarvest, $harvestMonths)) {
					$harvestMonths[$monthHarvest] = 'done';
				}
			}

		}

		foreach($eCultivation['harvestMonthsExpected'] ?? [] as $monthExpected) {

			if(array_key_exists($monthExpected, $harvestMonths)) {

				if($harvestMonths[$monthExpected] === NULL) {
					$harvestMonths[$monthExpected] = 'expected';
				}

			}

		}

		return $harvestMonths;

	}

	protected function getPeriodMonths(?\util\FormUi $form, ?string $name, array $harvestMonths): string {

		$labels = \util\DateUi::months(type: 'letter');

		$h = '';

		foreach($harvestMonths as $date => $status) {

			$checked = ($status !== NULL);

			$h .= '<label class="cultivation-month">';

				if($form !== NULL) {
					$h .= $form->inputCheckbox($name.'[]', $date, ['checked' => $checked]);
				} else {
					if($checked) {
						$h .= '<span class="cultivation-month-checked cultivation-month-'.$status.'"></span>';
					}
				}

				$h .= '<div>'.$labels[date_month($date)].'</div>';

			$h .= '</label>';

		}

		return $h;

	}

	protected function getPeriodWeeks(Cultivation $eCultivation, \util\FormUi $form, string $name, int $year, array $months): string {

		$labels = \util\DateUi::months(type: 'letter');

		$h = '';

		foreach($months as $monthNumber) {

			$month = $year.'-'.sprintf('%02d', $monthNumber);

			$h .= '<div class="cultivation-week">';
				$h .= '<div class="cultivation-week-item">'.$labels[date_month($month)].'</div>';

				$weeks = \util\DateLib::convertMonthsToWeeks([$month]);

				foreach($weeks as $position => $week) {

					$monday = strtotime($week);

					$begin = \util\DateUi::textual($monday, \util\DateUi::DAY_MONTH);
					$end = \util\DateUi::textual(strtotime($week.' + 6 days'), \util\DateUi::DAY_MONTH);

					$h .= '<label title="'.$begin.' → '.$end.'"';

					if($position === 0) {

						$day = (strtotime($month.'-04') - $monday) / 86400;
						$h .= ' style="margin-top: '.((6 - $day) / 6 * 1.5).'rem"';

					}

					$checked = in_array($week, $eCultivation['harvestWeeksExpected'] ?? []);

					$h .= '>';
						$h .= $form->inputCheckbox($name.'[]', $week, ['checked' => $checked]);
						$h .= '<div class="cultivation-week-item">'.date('W', $monday).'</div>';
					$h .= '</label>';

				}

			$h .= '</div>';

		}

		return $h;

	}

	public function getCropTitle(\farm\Farm $eFarm, \plant\Plant $ePlant): string {

		$h = '<div class="series-create-plant-title" data-plant-name="'.encode($ePlant['name']).'">';

			$h .= \plant\PlantUi::getVignette($ePlant, '3rem');
			$h .= '<h4>'.\plant\PlantUi::link($ePlant).'</h4>';

		$h .= '</div>';

		return $h;

	}

	public function create(Series $eSeries): \Panel {

		$eCultivation = new Cultivation([
			'farm' => $eSeries['farm'],
			'series' => $eSeries
		]);

		$form = new \util\FormUi();

		$h = $form->openAjax('/series/cultivation:doCreate', ['id' => 'cultivation-create', 'autocomplete' => 'off']);

			$h .= $form->hidden('series', $eSeries['id']);
			$h .= $form->dynamicGroup($eCultivation, 'plant', function($d) {
				$d->autocompleteDispatch = '#cultivation-create';
			});
			$h .= '<div id="cultivation-create-content"></div>';

		$h .= $form->close();

		return new \Panel(
			id: 'panel-cultivation-create',
			title: s("Ajouter une production"),
			body: $h,
			subTitle: SeriesUi::getPanelHeader($eSeries)
		);

	}

	public function createContent(Series $eSeries, Cultivation $eCultivation, \Collection $cAction): string {

		$form = new \util\FormUi([
			'firstColumnSize' => 50
		]);

		$h = '<div id="series-create-plant-list">';
			$h .= '<div class="series-create-plant series-write-plant">';
				$h .= $this->getFieldsCreate($form, $eSeries['use'], $eCultivation, $cAction, '');
			$h .= '</div>';
		$h .= '</div>';

		$h .= $form->group(
			content: $form->submit(s("Ajouter la production"))
		);

		return $h;

	}

	public function getFieldsCreate(\util\FormUi $form, string $use, Cultivation $eCultivation, \Collection $cAction, string $suffix): string {

		$eCultivation->expects([
			'sequence', 'season',
			'series' => ['season', 'cycle', 'area', 'length'],
			'distance',
			'cVariety', 'cSlice'
		]);

		$eCultivation['harvestedByUnit'] ??= [];
		$eCultivation['mainUnit'] ??= Cultivation::model()->getDefaultValue('mainUnit');

		$h = '<div class="cultivation-write">';

			$h .= new \sequence\CropUi()->getVarietyGroup($form, $eCultivation, $eCultivation['cVariety'], $eCultivation['cSlice'], $suffix);
			$h .= new \sequence\CropUi()->getDistanceField($form, $eCultivation, $use, $suffix);

			$h .= $form->dynamicGroup($eCultivation, 'seedling'.$suffix);
			$h .= $form->dynamicGroup($eCultivation, 'seedlingSeeds'.$suffix);

			if($eCultivation['sequence']->empty()) {
				$h .= self::getActionsField($form, $eCultivation, $cAction, $suffix);
			}

			$h .= self::getMainUnitField($form, $eCultivation, $suffix);
			$h .= self::getYieldExpectedField($form, $eCultivation, $suffix);

			if($eCultivation['sequence']->empty()) {
				$h .= self::getHarvestExpectedField($form, $eCultivation, $suffix);
			}

		$h .= '</div>';

		return $h;

	}

	public function update(Cultivation $eCultivation, \Collection $cAction): \Panel {

		$eCultivation->expects([
			'series' => ['use', 'plants'],
			'cSlice', 'cVariety'
		]);

		$form = new \util\FormUi();

		$h = $form->openAjax('/series/cultivation:doUpdate', ['id' => 'cultivation-update', 'class' => 'series-write-plant', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eCultivation['id']);
			$h .= '<div class="cultivation-write" data-length="'.($eCultivation['series']['length'] ?? $eCultivation['series']['lengthTarget']).'" data-area="'.($eCultivation['series']['area'] ?? $eCultivation['series']['areaTarget']).'" data-use="'.$eCultivation['series']['use'].'" data-bed-width="'.$eCultivation['series']['bedWidth'].'" data-alley-width="'.$eCultivation['series']['alleyWidth'].'">';

				$h .= $form->dynamicGroup($eCultivation, 'plant', function($d) use($eCultivation) {
					$d->autocompleteDispatch = '#cultivation-update';
					$d->attributes = [
						'post-id' => $eCultivation['id']
					];
				});

				$h .= new \sequence\CropUi()->getVarietyGroup($form, $eCultivation, $eCultivation['cVariety'], $eCultivation['cSlice']);
				$h .= new \sequence\CropUi()->getDistanceField($form, $eCultivation, $eCultivation['series']['use']);

				$h .= $form->dynamicGroup($eCultivation, 'seedling');
				$h .= $form->dynamicGroup($eCultivation, 'seedlingSeeds');

				if($cAction->notEmpty()) {
					$h .= self::getActionsField($form, $eCultivation, $cAction);
				}

				$h .= self::getMainUnitField($form, $eCultivation);
				$h .= self::getYieldExpectedField($form, $eCultivation);
				$h .= self::getHarvestExpectedField($form, $eCultivation);
			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		$title = \plant\PlantUi::getVignette($eCultivation['plant'], '3rem').' '.encode($eCultivation['plant']['name']);

		return new \Panel(
			id: 'panel-cultivation-update',
			title: $title,
			body: $h,
			subTitle: SeriesUi::getPanelHeader($eCultivation['series']),
		);

	}

	private static function getHarvestExpectedField(\util\FormUi $form, Cultivation $eCultivation, string $suffix = ''): string {

		$eCultivation->expects(['harvestPeriodExpected']);

		$h = '<div class="cultivation-periods-wrapper">';

			$h .= $form->hidden('harvestPeriodExpected'.$suffix, $eCultivation['harvestPeriodExpected']);
			$h .= $form->dynamicGroup($eCultivation, 'harvestMonthsExpected'.$suffix, function($d) use($eCultivation) {
				$d->after = '<div class="field-action cultivation-periods-field"><a '.attr('onclick', 'Cultivation.changeExpectedHarvest(this, "week")').'>'.s("Raisonner par semaine").'</a></div>';
				$d->group['class'] = ($eCultivation['harvestPeriodExpected'] === Cultivation::MONTH ? '' : 'hide');
			});
			$h .= $form->dynamicGroup($eCultivation, 'harvestWeeksExpected'.$suffix, function($d) use($eCultivation) {
				$d->after = '<div class="field-action cultivation-periods-field"><a '.attr('onclick', 'Cultivation.changeExpectedHarvest(this, "month")').'>'.s("Raisonner par mois").'</a></div>';
				$d->group['class'] = ($eCultivation['harvestPeriodExpected'] === Cultivation::WEEK ? '' : 'hide');
			});

		$h .= '</div>';

		return $h;

	}

	private static function getMainUnitField(\util\FormUi $form, Cultivation $eCultivation, ?string $suffix = NULL): string {

		return $form->dynamicGroup($eCultivation, 'mainUnit'.$suffix, function(\PropertyDescriber $d) use($eCultivation, $suffix) {
			$d->attributes = ['onchange' => 'Cultivation.changeUnit(this, "'.$suffix.'")'];
		});

	}

	private static function getYieldExpectedField(\util\FormUi $form, Cultivation $eCultivation, ?string $suffix = NULL): string {

		$h = $form->dynamicGroup($eCultivation, 'yieldExpected'.$suffix, function(\PropertyDescriber $d) use($eCultivation, $suffix) {
			$d->append = s("{value}&nbsp;/ m²", '<span data-ref="cultivation-unit-'.$suffix.'">'.self::p('mainUnit')->values[$eCultivation['mainUnit']].'</span>');
		});

		if($eCultivation['harvestedByUnit']) {

			$h .= '<div data-ref="cultivation-weight-'.$suffix.'" '.($eCultivation['mainUnit'] !== Cultivation::KG ? 'hide' : '').'>';

			foreach(['bunch', 'unit'] as $unit) {

				if(array_key_exists($unit, $eCultivation['harvestedByUnit'])) {

					$h .= $form->dynamicGroup($eCultivation, $unit.'Weight'.$suffix);

				} else {
					$h .= $form->hidden($unit.'Weight', '');
				}

			}

			$h .= '</div>';

		}

		return $h;

	}

	private static function getActionsField(\util\FormUi $form, Cultivation $eCultivation, \Collection $cAction, ?string $suffix = NULL): string {

		$h = $form->group(
			'<span class="util-badge ml-1" style="background-color: '.$cAction[ACTION_SEMIS_PEPINIERE]['color'].'">'.s("Semaine de semis en pépinière").'</span>',
			$form->week('actions'.$suffix.'['.ACTION_SEMIS_PEPINIERE.']', $eCultivation['season']),
			['class' => 'hide']
		);

		$h .= $form->group(
			'<span class="util-badge ml-1" style="background-color: '.$cAction[ACTION_PLANTATION]['color'].'">'.s("Semaine de plantation").'</span>',
			$form->week('actions'.$suffix.'['.ACTION_PLANTATION.']', $eCultivation['season']),
			['class' => 'hide']
		);

		$h .= $form->group(
			'<span class="util-badge ml-1" style="background-color: '.$cAction[ACTION_SEMIS_DIRECT]['color'].'">'.s("Semaine de semis direct").'</span>',
			$form->week('actions'.$suffix.'['.ACTION_SEMIS_DIRECT.']', $eCultivation['season']),
			['class' => 'hide']
		);

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Cultivation::model()->describer($property, [
			'farm' => s("Ferme"),
			'sequence' => s("Itinéraire technique"),
			'plant' => s("Espèce"),
			'density' => s("Densité d'implantation"),
			'rows' => s("Nombre de rangs par planche"),
			'rowSpacing' => s("Espace inter-rangs"),
			'plantSpacing' => s("Espace sur le rang"),
			'seedling' => s("Implantation"),
			'seedlingSeeds' => '<span class="cultivation-field-young-plant">'.s("Nombre de graines par plant").'</span><span class="cultivation-field-sowing">'.s("Nombre de graines par trou").'</span>',
			'harvested' => s("Récolte"),
			'yield' => s("Rendement obtenu"),
			'yieldExpected' => s("Objectif de rendement"),
			'mainUnit' => s("Unité de récolte principale"),
			'bunchWeight' => s("Poids d'une botte"),
			'unitWeight' => s("Poids d'une unité"),
			'harvestMonths' => s("Mois de récolte"),
			'harvestMonthsExpected' => s("Mois de récolte attendus"),
			'harvestWeeks' => s("Semaines de récolte"),
			'harvestWeeksExpected' => s("Semaines de récolte attendues"),
			'createdAt' => s("Créé le"),
		]);

		switch($property) {

			case 'rowSpacing' :
				$d->append = s("cm inter-rangs");
				$d->attributes['oninput'] = 'Cultivation.updateDensity(this)';
				break;

			case 'plantSpacing' :
				$d->append = s("cm sur le rang");
				$d->attributes['oninput'] = 'Cultivation.updateDensity(this)';
				break;

			case 'rows' :
				$d->append = s("rangs");
				$d->attributes['oninput'] = 'Cultivation.updateDensity(this)';
				break;

			case 'density' :
				$d->append = s("plantes / m²");
				$d->attributes['oninput'] = 'Cultivation.updateDensity(this)';
				break;

			case 'harvested' :
				$d->values = \selling\UnitUi::getBasicList();
				break;

			case 'plant' :
				$d->autocompleteBody = function(\util\FormUi $form, Cultivation $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'new' => TRUE
					];
				};
				new \plant\PlantUi()->query($d);
				break;

			case 'sequence' :
				new \sequence\SequenceUi()->query($d);
				break;

			case 'seedling' :
				$d->field = 'select';
				$d->values = [
					Cultivation::SOWING => s("semis direct"),
					Cultivation::YOUNG_PLANT => s("plant autoproduit"),
					Cultivation::YOUNG_PLANT_BOUGHT => s("plant acheté")
				];
				$d->attributes = fn(\util\FormUi $form, $e) => [
					'onchange' => 'Cultivation.changeSeedling(this)'
				];
				break;

			case 'seedlingSeeds' :
				$d->append = '<span class="cultivation-field-young-plant">'.s("graine(s) / plant").'</span><span class="cultivation-field-sowing">'.s("graine(s) / trou").'</span>';
				$d->group = fn($e) => [
					'data-action' => $e['seedling'] ?? ''
				];
				$d->attributes = [
					'onfocus' => 'this.select()'
				];
				break;

			case 'mainUnit' :
				$d->field = 'select';
				$d->attributes = ['mandatory' => TRUE];
				$d->values = \selling\UnitUi::getBasicList();
				break;

			case 'unitWeight' :
				$d->append = s("kg / unité");
				break;

			case 'bunchWeight' :
				$d->append = s("kg / botte");
				break;

			case 'harvestMonthsExpected' :
				$d->field = function(\util\FormUi $form, Cultivation $e, string $property, \PropertyDescriber $d) {
					return new CultivationUi()->getPeriod($e['series']['season'], 'month', $e['series'], $e, form: $form, name: $d->name);
				};
				break;

			case 'harvestWeeksExpected' :
				$d->field = function(\util\FormUi $form, Cultivation $e, string $property, \PropertyDescriber $d) {
					return new CultivationUi()->getPeriod($e['series']['season'], 'week', $e['series'], $e, form: $form, name: $d->name);
				};
				break;

		}

		return $d;

	}

}
?>
