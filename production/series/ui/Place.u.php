<?php
namespace series;

class PlaceUi {

	private bool $alert;

	public function __construct() {

		\Asset::css('series', 'place.css');
		\Asset::js('series', 'place.js');

	}

	public function update(\farm\Farm $eFarm, string $source, Series|Task $e, \Collection $cZone, \Search $search): \Panel {

		$e->expects(['cPlace']);

		$form = new \util\FormUi();

		$title = match($source) {
			'series' => match($e['use']) {
				Series::BED => s("Assolement sur planches"),
				Series::BLOCK => s("Assolement sur surface libre")
			},
			'task' => s("Assolement")
		};

		$subTitle = match($source) {
			'series' => SeriesUi::getPanelHeader($e),
			'task' => TaskUi::getPanelHeader($e)
		};

		if($cZone->empty()) {

			$h = '<div class="util-block-help">';
				$h .= '<p>';
					$h .= match($source) {
						'series' => s("Vous ne pouvez pas assoler cette série car vous n'avez pas encore configuré vos parcelles et vos planches de culture."),
						'task' => s("Vous ne pouvez pas assoler cette intervention car vous n'avez pas encore configuré vos parcelles et vos planches de culture.")
					};
				$h .= '</p>';
				$h .= '<a href="'.\farm\FarmUi::urlSoil($eFarm, $e['season']).'" class="btn btn-secondary">'.s("Configurer mes emplacements").'</a>';
			$h .= '</div>';

			return new \Panel(
				id: 'panel-place-update',
				title: $title,
				subTitle: $subTitle,
				body: $h,
			);

		}



		// Positionné avant pour récupérer les alertes
		$places = new \map\ZoneUi()
			->setUpdate($e)
			->getPlan($eFarm, $cZone, new \map\Zone(), $e['season']);

		$h = '';

		if($e['use'] === Series::BED) {

			if($source === 'series') {

				if($e['bedStartCalculated'] === NULL or $e['bedStopCalculated'] === NULL) {

					$h .= '<div class="util-warning">';
						$h .= \Asset::icon('exclamation-circle-fill').' '.s("Pensez à renseigner sur la série {value} les dates de semis direct ou de plantation ainsi que les périodes de récolte attendues afin qu'elle s'affiche au bon endroit sur le diagramme de temps.", SeriesUi::link($e, TRUE));
					$h .= '</div>';

				}

				$h .= $this->getPlaceSearch($e, $search);

			}

		}

		$h .= $form->openAjax('/series/place:doUpdate', ['id' => 'place-update']);

			$h .= $form->hidden($source, $e['id']);
			$h .=	$places;
			$h .= $form->submit(attributes: ['style' => 'visibility: hidden']); // Juste pour la validation avec Entrée

			$submit = '<div class="place-update-submit">';
				$submit .= $form->button(s("Enregistrer"), ['onclick' => 'Place.submitUpdate()']);
				$submit .= '<div>';

					if($source === 'series') {

						switch($e['use']) {

							case Series::BED;
								$submit .= s("Sélectionné : {value} mL", '<span id="place-update-value">'.($e['length'] ?? 0).'</span>');
								if($e['lengthTarget']) {
									$submit .= s(" / Objectif : {value} mL", $e['lengthTarget']);
								}
								break;

							case Series::BLOCK;
								$submit .= s("Sélectionné : {value} m²", '<span id="place-update-value">'.($e['area'] ?? 0).'</span>');
								if($e['areaTarget']) {
									$submit .= s(" / Objectif : {value} m²", $e['areaTarget']);
								}
								break;

						}

					}

				$submit .= '</div>';
			$submit .= '</div>';

		$h .= $form->close();

		return new \Panel(
			id: 'panel-place-update',
			title: $title,
			subTitle: $subTitle,
			body: $h,
			footer: $submit,
			close: INPUT('close', ['reloadIgnoreCascade', 'passthrough'], 'passthrough')
		);

	}

	public function getPlaceSearch(Series $eSeries, \Search $search): string {

		$form = new \util\FormUi();

		$h = '<div id="place-search" class="util-block-search">';

			$h .= $form->openAjax(LIME_REQUEST_PATH, ['method' => 'get', 'id' => 'form-search']);
				$h .= $form->hidden('search', 1);
				$h .= $form->hidden('series', $eSeries['id']);

				$h .= '<div>';

					$h .= $form->inputGroup(
						$form->addon('Planches').
						$form->select('mode', [
							NULL => s("Plein champ et tunnel"),
							\map\Plot::OPEN_FIELD => s("Plein champ"),
							\map\Plot::GREENHOUSE => s("Tunnel"),
						], $search->get('mode'), ['mandatory' => TRUE, 'onchange' => 'Place.search()'])
					);

					if($search->get('canWidth')) {

						$h .= $form->select('width', [
							0 => s("Toutes largeurs"),
							1 => s("{value} cm", $eSeries['bedWidth']),
						], (int)$search->get('width'), ['mandatory' => TRUE, 'onchange' => 'Place.search()']);

					}

					if(
						$eSeries['bedStartCalculated'] !== NULL and
						$eSeries['bedStopCalculated'] !== NULL
					) {
						$input = $form->select('free', [
								0 => s("Non"),
								100 => s("Oui"),
								1 => s("À ± 1 semaine"),
								2 => s("À ± 2 semaines"),
							], $search->get('available'), ['mandatory' => TRUE, 'onchange' => 'Place.search()']);
					} else {
						$input = $form->addon(\Asset::icon('exclamation-circle-fill'), ['title' => s("Indiquez les dates de semis direct, de plantation ou les périodes de récolte attendues sur cette séries pour utiliser ce filtre.")]);
					}

					$h .= $form->inputGroup(
						$form->addon('Seulement les planches libres').
						$input
					);

					$h .= $form->inputGroup(
						$form->addon('Délai de retour sur même famille').
						$form->select('rotation', [
							0 => s("Peu importe"),
							2 => s("2 ans"),
							3 => s("3 ans"),
							4 => s("4 ans"),
							5 => s("5 ans")
						], $search->get('rotation'), ['mandatory' => TRUE, 'onchange' => 'Place.search()'])
					);

				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getTimeline(\farm\Farm $eFarm, \map\Bed $eBed, \Collection $cPlace, int $season, Series|Task|\Element $ePlaceholder, bool $print): string {

		$lines = [];

		$firstWeekShown = ($eFarm['calendarMonthStart'] ? 0 - (int)date('W', strtotime(($season - 1).'-'.$eFarm['calendarMonthStart'].'-01')) : 0);
		$lastWeekShown = ($eFarm['calendarMonthStop'] ? 100 + (int)date('W', strtotime(($season + 1).'-'.$eFarm['calendarMonthStop'].'-28')) : 100);


		// Tri par démarrage
		foreach($cPlace as $ePlace) {
			$this->positionPlace($ePlace, $season, $firstWeekShown, $lastWeekShown);
		}

		$cPlace->sort('positionStart');

		$overlay = $eFarm->getView('viewSoilOverlay');

		if($overlay) {
			$lines[0] = [];
		}

		foreach($cPlace as $ePlace) {

			// Il faut au moins une date de démarrage
			if($ePlace['positionStart'] === NULL) {
				continue;
			}

			if($overlay) {
				$lines[0][] = $ePlace;
			} else {

				$added = FALSE;

				foreach($lines as $key => $line) {

					$ePlaceLast = end($line);

					if(
						$ePlace['visible'] === FALSE or
						$ePlaceLast['positionStop'] === NULL or
						$ePlace['positionStart'] >= $ePlaceLast['positionStop']
					) {

						$lines[$key][] = $ePlace;
						$added = TRUE;
						break;

					}

				}

				if($added === FALSE) {
					$lines[] = [$ePlace];
				}

			}

		}

		if($lines === []) {
			return '';
		}
		$baseHeight = $print ? 1.8 : 2;
		$basePadding = $print ? ($eFarm->getView('viewSoilTasks') ? 0.2 : 0.1) : 0.25;
		$baseGap = $print ? 0.2 : 0.3;
		$totalHeight = (count($lines) * $baseHeight + (count($lines) - 1) * $baseGap + $basePadding * 2);

		$list = '';
		$gap = 0;

		foreach($lines as $key => $line) {

			$positions = [];

			foreach($line as $ePlace) {

				$isPlaceholder = (
					$ePlaceholder->notEmpty() and
					$ePlace['series']->is($ePlaceholder)
				);

				if($isPlaceholder) {
					continue;
				}

				$top = match($key) {

					0 => $basePadding.'rem',
					default => $basePadding.'rem + ('.$key.' * ('.$baseHeight.'rem + '.$baseGap.'rem))'

				};

				if($ePlace['visible']) {

					$reset = TRUE;

					foreach($positions as $positionStop) {

						if($ePlace['positionStart'] < $positionStop) {
							$reset = FALSE;
							break;
						}

					}

					if($reset) {
						$positions = [$ePlace['positionStop']];
					} else {
						$positions[] = $ePlace['positionStop'];
						$top .= ' + '.((count($positions) - 1) * $baseGap).'rem';
					}

					$gap = max($gap, (count($positions) - 1) * $baseGap);

				}

				$list .= $this->getSeriesTimeline($eFarm, $eBed, $season, $ePlace, $ePlace['series'], $ePlace['series']['cCultivation'], FALSE, 'height: '.$baseHeight.'rem; top: calc('.$top.');', $print);

			}

		}

		$totalHeight += $gap;

		if($ePlaceholder->notEmpty()) {

			$ePlace = new Place([
				'missing' => FALSE,
				'series' => $ePlaceholder instanceof Series ? $ePlaceholder : new Series(),
				'task' => $ePlaceholder instanceof Task ? $ePlaceholder : new Task(),
			]);

			$this->positionPlace($ePlace, $season, $firstWeekShown, $lastWeekShown);

			if($ePlace['positionStart'] !== NULL and $ePlace['positionStop'] !== NULL) {
				$list .= $this->getSeriesTimeline($eFarm, $eBed, $season, $ePlace, $ePlaceholder, $ePlaceholder['cCultivation'], TRUE, 'height: '.$totalHeight.'rem; top: 0rem;', $print);
			}

		}

		if($list === '') {
			return '';
		} else {

			$h = '<div class="place-grid-series-timeline-lines" style="height: '.$totalHeight.'rem">';
				$h .= $list;
			$h .= '</div>';

			return $h;

		}

	}

	protected function positionPlace(Place $ePlace, int $season, int $firstWeekShown, int $lastWeekShown): void {

		if($ePlace['series']->empty()) {
			$ePlace['positionStart'] = NULL;
			$ePlace['positionStop'] = NULL;
			return;
		}

		$ePlace['missing'] = FALSE;

		if(
			$ePlace['series']['cycle'] === Series::PERENNIAL and
			$ePlace['series']['perennialSeason'] > 1
		) {

			$ePlace['positionStart'] = 1 + ($ePlace['series']['season'] - $season) * 100;

		} else {

			$ePlace['positionStart'] = $ePlace['series']['bedStartCalculated'] !== NULL ?
				($ePlace['series']['bedStartCalculated'] + ($ePlace['series']['season'] - $season) * 100) :
				NULL;

		}

		if(
			$ePlace['series']['cycle'] === Series::PERENNIAL and
			($ePlace['series']['perennialLifetime'] === NULL or $ePlace['series']['perennialSeason'] < $ePlace['series']['perennialLifetime'])
		) {

			$ePlace['positionStop'] = 52 + ($ePlace['series']['season'] - $season) * 100;

		} else {

			$ePlace['positionStop'] = $ePlace['series']['bedStopCalculated'] !== NULL ?
				($ePlace['series']['bedStopCalculated'] + ($ePlace['series']['season'] - $season) * 100) :
				NULL;

		}

		if($ePlace['positionStop'] === NULL) {

			$ePlace['missing'] = TRUE;

			if($ePlace['positionStart'] !== NULL) {

				$position = $ePlace['positionStart'] + SeriesSetting::MISSING_WEEKS + ($ePlace['series']['season'] - $season) * 100;
				$positionSeason = floor($position / 100);
				$positionWeek = $position - $positionSeason * 100;

				if($positionWeek > 52) {
					$position = $positionSeason + 100 + $positionWeek % 52;
				}

				$ePlace['positionStop'] = $position;

			}

		}

		$ePlace['visible'] = (
			$ePlace['positionStart'] !== NULL and
			$ePlace['positionStop'] !== NULL and
			$ePlace['positionStop'] >= $firstWeekShown and
			$ePlace['positionStart'] < $lastWeekShown
		);

	}

	protected function positionToTimestamp(Series $eSeries, int $position, int $season, int $gap): int {

		$positionSeason = (int)floor($position / 100);
		$positionWeek = (int)($position - $positionSeason * 100);

		$year = $positionSeason + $season;

		if($eSeries['cycle'] === Series::PERENNIAL) {

			if($positionWeek === 1) {
				return strtotime($year.'-01-01');
			}

			if($positionWeek >= 52) {
				return strtotime($year.'-12-31');
			}

		}

		$week = sprintf('%02d', $positionWeek);

		return strtotime($year.'-W'.$week.' + '.$gap.' DAYS');

	}

	protected function getSeriesTimeline(\farm\Farm $eFarm, \map\Bed $eBed, int $season, Place $ePlace, Series $eSeries, \Collection $cCultivation, bool $isPlaceholder, string $style, bool $print): string {

		$ePlace->expects(['missing']);
		$eFarm->expects(['calendarMonths', 'calendarMonthStart', 'calendarMonthStop']);

		$cCultivation->expects([
			'harvestWeeks', 'harvestWeeksExpected',
			'cTask'
		]);

		$soilColor = $eFarm->getView('viewSoilColor');

		$minTs = $this->positionToTimestamp($eSeries, $ePlace['positionStart'], $season, 0);
		$maxTs = $this->positionToTimestamp($eSeries, $ePlace['positionStop'], $season, 6);

		$h = '';

		$details = '';
		$actions = [];

		if($isPlaceholder) {
			$class = 'place-grid-series-timeline-placeholder bed-write';
		} else {
			$class = ($season === $eSeries['season']) ? 'place-grid-series-timeline-season' : 'place-grid-series-timeline-not-season';

			foreach($cCultivation as $eCultivation) {

				if(
					$eCultivation['startAction'] === NULL and
					$eCultivation['harvestWeeksExpected'] === NULL and
					$eCultivation['harvestWeeks'] === NULL
				) {
					continue;
				}

				$details .= '<div>';
					$details .= \plant\PlantUi::getVignette($eCultivation['plant'], '1.6rem');
				$details .= '</div>';
				$details .= '<div>';

					if($eCultivation['startAction'] !== NULL) {

						if($eFarm->getView('viewSoilTasks')) {

							$actions[] = [
								'action' => match($eCultivation['startAction']) {
										Cultivation::PLANTING => ACTION_PLANTATION,
										Cultivation::SOWING => ACTION_SEMIS_DIRECT,
								},
								'weekStart' => $eCultivation->getStartWeek($eCultivation['season']),
								'weekStop' => $eCultivation->getStartWeek($eCultivation['season']),
							];

						}

						$start = ($eCultivation['startWeek'] + 1000) % 100;

						$details .= '<div>';
							$details .= match($eCultivation['startAction']) {
								Cultivation::PLANTING => s("Plantation : s{value}", $start),
								Cultivation::SOWING => s("Semis : s{value}", $start)
							};
						$details .= '</div>';

					}

					if($eCultivation['harvestWeeksExpected'] !== NULL or $eCultivation['harvestWeeks'] !== NULL) {

						$harvests = array_merge($eCultivation['harvestWeeksExpected'] ?? [], $eCultivation['harvestWeeks'] ?? []);

						$start = min($harvests);
						$stop = max($harvests);

						if($eFarm->getView('viewSoilTasks')) {

							$actions[] = [
								'action' => ACTION_RECOLTE,
								'weekStart' => $start,
								'weekStop' => $stop,
							];

						}

						$details .= '<div>';
							if($start !== $stop) {
								$details .= s("Récolte : s{from} à s{to}", ['from' => week_number($start), 'to' => week_number($stop)]);
							} else {
								$details .= s("Récolte : s{value}", week_number($start));
							}
						$details .= '</div>';
					}

				$details .= '</div>';

			}
		}

		[$startTs, $stopTs] = $this->getBounds($eFarm, $season);

		$id = uniqid('place-timeline-');

		if($details) {
			$dropdown = 'data-dropdown="bottom-'.($minTs < $startTs ? 'end' : 'start').'" data-dropdown-hover="true" data-dropdown-offset-x="0" data-dropdown-enter-timeout="0"';
		} else {
			$dropdown = '';
		}

		if($soilColor === \farm\Farmer::PLANT) {

			$colors = $cCultivation->getColumnCollection('plant')->getColumn('color');
			$nColors = count($colors);

			if($nColors === 1) {
				$style .= 'background-color: '.$colors[0].';';
			} else {

				$style .= 'background: linear-gradient(to right';
					foreach($colors as $position => $color) {
						$style .= ', '.$color.' '.(100 * $position / ($nColors - 1)).'%';
					}
				$style .= ');';

			}

		}

		$tag = $isPlaceholder ? 'div' : 'a';

		$h .= '<'.$tag.' href="'.SeriesUi::url($eSeries).'" id="'.$id.'" class="place-grid-series-timeline '.$class.' '.($ePlace['missing'] ? 'place-grid-series-timeline-alert' : '').' '.($details ? 'place-grid-series-timeline-with-details' : '').'" style="'.$style.'" '.$dropdown.' data-ajax-navigation="notouch" '.($isPlaceholder ? '': 'data-series="'.$eSeries['id'].'').'">';

			if($isPlaceholder) {

				if($ePlace['missing']) {
					$h .= \Asset::icon('exclamation-circle-fill', ['class' => 'color-white']).' ';
				}

			} else {

				if($soilColor !== \farm\Farmer::PLANT) {

					$h .= '<div class="place-grid-series-timeline-images">';

						foreach($cCultivation as $eCultivation) {
							$h .= '<div class="place-grid-series-timeline-plant">';
								$h .= \plant\PlantUi::getVignette($eCultivation['plant'], $print ? '1.2rem' : '1.3rem');
							$h .= '</div>';
						}

						if($eSeries['status'] === Series::CLOSED) {
							$h .= '<div class="place-grid-series-timeline-lock">';
								$h .= \Asset::icon('lock-fill');
							$h .= '</div>';
						}

					$h .= '</div>';

				}

				$h .= '<div class="place-grid-series-timeline-name">';

					if($ePlace['missing']) {
						$h .= \Asset::icon('exclamation-circle-fill').' ';
					}

					$h .= encode($eSeries['name']);

					if(
						$ePlace->notEmpty() and
						$eSeries['status'] === Series::OPEN and (
							($eSeries['use'] === Series::BED and $eBed['length'] !== $ePlace['length']) or
							($eSeries['use'] === Series::BLOCK)
						)

					) {
						$h .= '<div class="place-grid-series-timeline-more">'.match($eSeries['use']) {
							Series::BED => s("{length} mL", $ePlace),
							Series::BLOCK => s("{area} m²", $ePlace)
						}.'</div>';
					}

				$h .= '</div>';

			}

			if($actions) {

				usort($actions, fn($action1, $action2) => $action1['weekStart'] > $action2['weekStart'] ? -1 : 1);

				$cAction = \farm\FarmSetting::$mainActions;

				foreach($actions as ['action' => $action, 'weekStart' => $startWeek, 'weekStop' => $stopWeek]) {

					$eAction = $cAction[$action];

					$startActionTs = strtotime(week_date_starts($startWeek).' 00:00:00');
					$stopActionTs = strtotime(week_date_ends($stopWeek).' 23:59:59');

					$left = ($startActionTs - $minTs) / ($maxTs - $minTs) * 100;
					$width = ($stopActionTs - $minTs) / ($maxTs - $minTs) * 100 - $left;

					$h .= '<div class="place-grid-series-timeline-week" style="left: '.$left.'%; width: '.$width.'%; background-color: '.$eAction['color'].'"></div>';

				}

			}

			$h .= '<style>';
				$h .= $this->getPositionStyle($id, $startTs, $stopTs, $minTs, $maxTs);
			$h .= '</style>';

		$h .= '</'.$tag.'>';

		if($details) {
			$h .= '<div class="place-grid-series-timeline-dropdown dropdown-list dropdown-list-unstyled">';
				$h .= '<div class="place-grid-series-timeline-title">'.encode($eSeries['name']).'</div>';
				if($ePlace['missing']) {
					$h .= '<div class="place-grid-series-timeline-alert-message">'.s("Veuillez renseigner les dates de semis, plantation et récoltes attendus pour cette série afin qu'elle s'affiche correctement sur le diagramme !").'</div>';
				}
				$h .= '<div class="place-grid-series-timeline-details">';
					$h .= $details;
				$h .= '</div>';
				$h .= '<div class="place-grid-series-timeline-actions">';
					$h .= '<a href="'.SeriesUi::url($eSeries).'" class="btn btn-transparent">'.s("Voir la série").'</a>';
				$h .= '</div>';
			$h .= '</div>';
		}


		return $h;

	}

	public function getBounds(\farm\Farm $eFarm, int $season): array {

		$startTs = strtotime($eFarm->getCalendarStartDay($season));
		$stopTs = strtotime($eFarm->getCalendarStopDay($season));

		return [$startTs, $stopTs];

	}

	public function getWeeksInBounds(\farm\Farm $eFarm, int $season): array {

		$startDay = $eFarm->getCalendarStartDay($season);
		$stopDay = $eFarm->getCalendarStopDay($season);

		$startWeek = toWeek($startDay);
		$stopWeek = toWeek($stopDay);

		$currentWeek = $startWeek;
		$weeks = [];

		do {
			$weeks[] = $currentWeek;
			$currentWeek = toWeek(strtotime($currentWeek.' + 1 WEEK'));
		} while($currentWeek <= $stopWeek);

		return $weeks;

	}

	public function getPositionStyle($id, $startTs, $stopTs, $minTs, $maxTs, ?string $width = NULL, ?int $fixThreshold = NULL, string $fixDirection = 'left', ?string $shiftLeft = NULL, ?string $shiftRight = NULL): string {

		$left = ($minTs - $startTs) / ($stopTs - $startTs) * 100;
		$right = ($maxTs - $startTs) / ($stopTs - $startTs) * 100;

		if($fixThreshold !== NULL and $right - $left < $fixThreshold) {

			if($fixDirection === 'left') {
				$left = $right - $fixThreshold;
			} else {
				$right = $left + $fixThreshold;
			}

		}

		$css = '';

		if($left < 0 or $right <= 100) {
			$css .= '#'.$id.' { left: calc('.$left.'%'.($shiftLeft ? ' + '.$shiftLeft : '').'); width: '.($width ?: ($right - $left).'%').'; }';
		} else {
			$css .= '#'.$id.' { right: calc('.(100 - $right).'%'.($shiftRight ? ' + '.$shiftRight : '').'); width: '.($width ?: ($right - $left).'%').'; }';
		}

		return $css;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Series::model()->describer($property, [
		]);

		return $d;

	}

}
?>
