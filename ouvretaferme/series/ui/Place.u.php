<?php
namespace series;

class PlaceUi {

	private bool $alert;

	public function __construct() {

		\Asset::css('series', 'place.css');
		\Asset::js('series', 'place.js');

	}

	public function update(string $source, Series|Task $e, \Collection $cZone, \Collection $cPlace, \Search $search): \Panel {

		$form = new \util\FormUi();

		$title = match($source) {
			'series' => match($e['use']) {
				Series::BED => s("Assolement sur planches de {value} cm", $e['bedWidth']),
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
				$h .= '<a href="'.\farm\FarmUi::urlSoil($e['farm'], $e['season']).'" class="btn btn-secondary">'.s("Configurer mes emplacements").'</a>';
			$h .= '</div>';

			return new \Panel(
				title: $title,
				subTitle: $subTitle,
				body: $h,
			);

		}



		// Positionné avant pour récupérer les alertes
		$places = $this->getUpdatePlaces($form, $source, $e, $cZone, $cPlace);

		$h = '';

		if($e['use'] === Series::BED) {

			if($source === 'series') {

				if($e['bedStartCalculated'] === NULL or $e['bedStopCalculated'] === NULL) {

					$h .= '<div class="util-warning">';
						$h .= \Asset::icon('exclamation-circle-fill').' '.s("Pensez à renseigner sur la série {value} les dates de semis direct ou de plantation ainsi que les périodes de récolte attendues afin qu'elle s'affiche au bon endroit sur le diagramme de temps.", SeriesUi::link($e, TRUE));
					$h .= '</div>';

				}

				$h .= '<div class="place-update-filter">';
					$h .= '<a href="'.\farm\FarmUi::urlCartography($e['farm'], $e['season']).'" class="btn btn-primary">'.\Asset::icon('geo-alt-fill').' '.s("Modifier le plan de la ferme").'</a> ';
					$h .= ' <a '.attr('onclick', 'Lime.Search.toggle("#place-search")').' class="btn btn-primary">';
						$h .= \Asset::icon('search').' '.s("Filtrer les planches");
					$h .= '</a>';
				$h .= '</div>';

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
								$submit .= s("Sélectionné : {value} mL", '<span id="place-update-length">'.($e['length'] ?? 0).'</span>');
								if($e['lengthTarget']) {
									$submit .= s(" / Objectif : {value} mL", $e['lengthTarget']);
								}
								break;

							case Series::BLOCK;
								$submit .= s("Sélectionné : {value} m²", '<span id="place-update-area">'.($e['area'] ?? 0).'</span>');
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

		$h = '<div id="place-search" class="util-block-search stick-xs '.($search->empty(['canWidth']) ? 'hide' : '').'">';

			$h .= $form->openAjax(LIME_REQUEST_PATH, ['method' => 'get', 'id' => 'form-search']);
				$h .= $form->hidden('search', 1);
				$h .= $form->hidden('series', $eSeries['id']);

				$h .= '<div>';

					if($search->get('canWidth')) {

						$h .= $form->inputGroup(
							$form->addon('Largeur de planche').
							$form->select('width', [
								0 => s("Toutes"),
								1 => s("Seulement {value} cm", $eSeries['bedWidth']),
							], (int)$search->get('width'), ['mandatory' => TRUE, 'onchange' => 'Place.updateSearch()'])
						);

					}

					$h .= $form->inputGroup(
						$form->addon('Mode de culture').
						$form->select('mode', [
							NULL => s("Tous"),
							\map\Plot::OPEN_FIELD => s("Plein champ"),
							\map\Plot::GREENHOUSE => s("Tunnel"),
						], $search->get('mode'), ['mandatory' => TRUE, 'onchange' => 'Place.updateSearch()'])
					);

					if(
						$eSeries['bedStartCalculated'] !== NULL and
						$eSeries['bedStopCalculated'] !== NULL
					) {
						$input = $form->select('free', [
								0 => s("Non"),
								100 => s("Oui"),
								1 => s("À ± 1 semaine"),
								2 => s("À ± 2 semaines"),
							], $search->get('available'), ['mandatory' => TRUE, 'onchange' => 'Place.updateSearch()']);
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
						], $search->get('rotation'), ['mandatory' => TRUE, 'onchange' => 'Place.updateSearch()'])
					);
					$h .= '<div>';
						$h .= '<a onclick="Place.resetSearch()" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
					$h .= '</div>';

				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}
	
	protected function getUpdatePlaces(\util\FormUi $form, string $source, Series|Task $e, \Collection $cZone, \Collection $cPlace): string {

		if($cZone->count() === 1) {
			$h = '<div class="place-grid-'.$source.'">';
				$h .= $this->getUpdateZone($form, $source, $e, $cZone->first(), $cPlace);
			$h .= '</div>';
			return $h;
		}

		$zones = array_count_values($cPlace->getColumnCollection('zone')->getIds());
		asort($zones);

		$h = '<div class="tabs-h place-grid-'.$source.'" id="place-grid-wrapper" onrender="'.encode('Lime.Tab.restore(this, "map-place-update"'.($zones ? ', '.array_key_last($zones) : '').')').'">';

			$h .= '<div class="tabs-item">';

				foreach($cZone as $eZone) {

					$beds = $zones[$eZone['id']] ?? 0;

					$h .= '<a class="tab-item" data-tab="'.$eZone['id'].'" onclick="Lime.Tab.select(this)">';
						$h .= encode($eZone['name']);
						$h .= '<span class="tab-item-count">';
							if($beds > 0) {
								$h .= $beds;
							}
						$h .= '</span>';
					$h .= '</a>';

				}

			$h .= '</div>';

			foreach($cZone as $eZone) {
				$h .= $this->getUpdateZone($form, $source, $e, $eZone, $cPlace);
			}

		$h .= '</div>';

		return $h;

	}

	protected function getUpdateZone(\util\FormUi $form, string $source, Series|Task $e, \map\Zone $eZone, \Collection $cPlace): string {

		$e->expects([
			'farm' => ['calendarMonths', 'calendarMonthStart', 'calendarMonthStop'],
			'season'
		]);

		$h = '<div class="tab-panel stick-sm" data-tab="'.$eZone['id'].'">';

			// Titre de la zone
			$ePlotZone = $eZone['cPlot']->first();
			$eBedZone = $ePlotZone['cBed']->first();

			$h .= $this->getUpdatePlace(
				$form,
				$source,
				$e,
				$cPlace[$eBedZone['id']] ?? new Place(),
				'zone',
				$eBedZone,
				s("Parcelle {value}", encode($eZone['name']))
			);

			$h .= '<div class="util-overflow-xs">';

			// Planches de la zone
			if($ePlotZone['beds'] > 0) {

				[$beds, $isVisible] = $this->getUpdateBeds($form, $source, $e, $cPlace, $ePlotZone['cBed']);

				if($beds !== '') {

					$h .= '<div class="place-grid-container" data-hide="'.($isVisible ? 0 : 1).'">';
						$h .= '<div class="place-grid-label" title="'.s("Tout cocher / Tout décocher").'">';
							$h .= '<label class="place-grid-select">';
								$h .= '<input type="checkbox" onclick="Place.toggleSelection(this)"/>';
							$h .= '</label>';
							$h .= '<div></div>';
							$h .= (new CultivationUi())->getListSeason($e['farm'], $e['season']);
						$h .= '</div>';
						$h .= '<div class="place-grid-content">';
							$h .= (new CultivationUi())->getListGrid($e['farm'], $e['season']);
							$h .= $beds;
						$h .= '</div>';
					$h .= '</div>';

				}

			}

			// Browse plots
			if($eZone['plots'] > 0) {

				foreach($eZone['cPlot'] as $ePlot) {

					if($ePlot['zoneFill']) {
						continue;
					}

					$eBedPlot = $ePlot['cBed']->first();

					$h .= $this->getUpdatePlace(
						$form,
						$source,
						$e,
						$cPlace[$eBedPlot['id']] ?? new Place(),
						'plot',
						$eBedPlot,
						s("Bloc {value}", encode($ePlot['name']))
					);

					if($ePlot['beds'] > 0) {

						[$beds, $isVisible] = $this->getUpdateBeds($form, $source, $e, $cPlace, $ePlot['cBed']);

						if($beds !== '') {

							$h .= '<div class="place-grid-container" data-hide="'.($isVisible ? 0 : 1).'">';
								$h .= '<div class="place-grid-label">';
									$h .= '<label class="place-grid-select">';
										$h .= '<input type="checkbox" onclick="Place.toggleSelection(this)"/>';
									$h .= '</label>';
									$h .= '<div></div>';
									$h .= (new CultivationUi())->getListSeason($e['farm'], $e['season']);
								$h .= '</div>';

								$h .= '<div class="place-grid-content">';
									$h .= (new CultivationUi())->getListGrid($e['farm'], $e['season']);
									$h .= $beds;
								$h .= '</div>';
							$h .= '</div>';

						}

					}


				}

			}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getUpdateBeds(\util\FormUi $form, string $source, Series|Task $e, \Collection $cPlace, \Collection $cBed): array {

		$h = '';
		$isVisible = FALSE;

		$cBed->sort('name', natural: TRUE);

		foreach($cBed as $eBed) {

			if($eBed['plotFill']) {
				continue;
			}

			$ePlace = $cPlace[$eBed['id']] ?? new Place();

			$h .= $this->getUpdatePlace(
				$form,
				$source,
				$e,
				$ePlace,
				'bed',
				$eBed,
				s("Planche {value}", encode($eBed['name']))
			);

			if(($eBed['test']['hide'] ?? FALSE) === FALSE) {
				$isVisible = TRUE;
			}

		}

		return [$h, $isVisible];

	}

	protected function getUpdatePlace(\util\FormUi $form, string $source, Series|Task $e, Place $ePlace, string $type, \map\Bed $eBed, string $name): string {

		if(isset($eBed['test'])) {
			$test = 'data-same-width="'.($eBed['test']['sameWidth'] ? 1 : 0).'" data-greenhouse="'.($eBed['test']['hasGreenhouse'] ? 1 : 0).'" data-rotation="'.$eBed['test']['rotation'].'" data-free="'.$eBed['test']['free'].'" data-hide="'.($eBed['test']['hide'] ? 1 : 0).'"';
		} else {
			$test = '';
		}

		$h = '<div class="place-grid place-grid-'.$type.' '.($ePlace->notEmpty() ? 'selected' : '').'" '.$test.'>';

			if(
				($e['use'] === Series::BED and $type === 'bed') or
				($e['use'] === Series::BLOCK and in_array($type, ['plot', 'zone']))
			) {
				$h .= '<label class="place-grid-select">'.$form->inputCheckbox('beds[]', $eBed['id'], ['checked' => $ePlace->notEmpty()]).'</label>';
			} else if($e['use'] === Series::BED and in_array($type, ['plot', 'zone'])) {
				$h .= '<label class="place-grid-select">'.$form->inputCheckbox('beds[]', $eBed['id'], ['checked' => $ePlace->notEmpty()]).'</label>';
			} else {
				$h .= '<div class="place-grid-select place-grid-noselect">×</div>';
			}

			$h .= '<label class="place-grid-name" for="'.$form->getLastFieldId().'">';
				$h .= '<div class="place-grid-name-content">';
					$h .= '<div>';
						$h .= $name;
						if($eBed['plotFill'] === FALSE and $eBed['zoneFill'] === FALSE) {
							$h .= ' '.$eBed->getGreenhouseIcon();
						}
					$h .= '</div>';
					if(($eBed['test']['rotation'] ?? NULL) > 0) {
						$h .= '<div class="place-grid-name-content-rotation" title="'.s("Rotation sur la même famille").'">';
							$h .= \Asset::icon('arrow-clockwise').' '.p("{value} an", "{value} ans", $eBed['test']['rotation']);
						$h .= '</div>';
					}
				$h .= '</div>';
			$h .= '</label>';
			$h .= '<div class="place-grid-area">';
				if($eBed['plotFill'] or $eBed['zoneFill']) {
					$h .= s("{area} m²", $eBed);
				} else {
					if(
						$e['use'] === Series::BED and
						$e['bedWidth'] !== NULL and
						$e['bedWidth'] !== $eBed['width']
					) {
						$h .= s("{length} mL x <danger>{width} cm</danger>", $eBed->extracts(['length', 'width']) + ['danger' => '<span class="color-danger" style="font-weight: bold">'.\Asset::icon('exclamation-circle').' ']);
					} else {
						$h .= s("{length} mL x {width} cm", $eBed);
					}
				}
			$h .= '</div>';

			if($source === 'series') {

				$h .= '<div class="place-grid-range">';

					if($eBed['length'] !== NULL) {

						$h .= $form->inputGroup(
							$form->addon(s("Utiliser")).
							'<div class="form-control">'.$form->range('sizes['.$eBed['id'].']', 0, $eBed['length'], 1, $ePlace->notEmpty() ? $ePlace['length'] : $eBed['length'], [
								'data-label' => 'mL'
							]).'</div>'
						);

					} else {

						$h .= match($e['use']) {

							Series::BED => $form->inputGroup(
								$form->addon(s("Planche temporaire")).
								$form->number('sizes['.$eBed['id'].']', $ePlace->notEmpty() ? $ePlace['length'] : '', ['min' => 0]).
								$form->addon(s("mL"))
							),

							Series::BLOCK => $form->inputGroup(
								$form->addon(s("Surface cultivée")).
								$form->number('sizes['.$eBed['id'].']', $ePlace->notEmpty() ? $ePlace['area'] : $eBed['area'], ['min' => 0, 'max' => $eBed['area']]).
								$form->addon(s("m²"))
							)

						};

					}

				$h .= '</div>';

			}

			$h .= '<div class="place-grid-series">';

				if($eBed['plotFill'] or $eBed['zoneFill']) {

					$values = [
						Series::BED => [],
						Series::BLOCK => []
					];

					foreach($eBed['cPlace'] as $ePlaceCurrent) {

						// Filtre uniquement sur la série en cours sur le parcellaire
						if(
							$ePlaceCurrent['series']->empty() or
							$ePlaceCurrent['series']['season'] !== $e['season']
						) {
							continue;
						}

						if($ePlace->empty() or $ePlaceCurrent['id'] !== $ePlace['id']) {

							$use = $ePlaceCurrent['series']['use'];

							$size = match($use) {
								Series::BED => s("{length} mL", $ePlaceCurrent),
								Series::BLOCK => s("{area} m²", $ePlaceCurrent)
							};

							$values[$use][] = '<u>'.SeriesUi::link($ePlaceCurrent['series']).'</u> <i class="util-unit">('.$size.')</i>';

						}

					}

					if($values[Series::BED]) {
						$h .= '<div>'.s("{value} sur planches temporaires cette saison", ['value' => implode(' / ', $values[Series::BED])]).'</div>';
					}

					if($values[Series::BLOCK]) {
						$h .= '<div>'.s("{value} cette saison", ['value' => implode(' / ', $values[Series::BLOCK])]).'</div>';
					}

				} else {

					$h .= $this->getTimeline($e['farm'], $eBed, $eBed['cPlace'], $e['season'], $source, $e);

				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getTimeline(\farm\Farm $eFarm, \map\Bed $eBed, \Collection $cPlace, int $season, ?string $placeholderSource = NULL, Series|Task|null $ePlaceholder = NULL): string {

		$lines = [];

		$firstWeekShown = ($eFarm['calendarMonthStart'] ? 0 - (int)date('W', strtotime(($season - 1).'-'.$eFarm['calendarMonthStart'].'-01')) : 0);
		$lastWeekShown = ($eFarm['calendarMonthStop'] ? 100 + (int)date('W', strtotime(($season + 1).'-'.$eFarm['calendarMonthStop'].'-28')) : 100);


		// Tri par démarrage
		foreach($cPlace as $ePlace) {
			$this->positionPlace($ePlace, $season, $firstWeekShown, $lastWeekShown);
		}

		$cPlace->sort('positionStart');

		foreach($cPlace as $ePlace) {

			// Il faut au moins une date de démarrage
			if($ePlace['positionStart'] === NULL) {
				continue;
			}

			$added = FALSE;

			foreach($lines as $key => $line) {

				$ePlaceLast = end($line);

				if(
					$ePlace['visible'] === FALSE or
					$ePlace['positionStart'] - $ePlaceLast['positionStart'] >= 4 or
					($ePlaceLast['positionStop'] !== NULL and $ePlace['positionStart'] - $ePlaceLast['positionStop'] >= 0)
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


		switch(count($lines)) {

			case 0 :
				$totalHeight = '2.8rem';
				break;

			default :
				$totalHeight = (count($lines) * 2 + (count($lines) - 1) * 0.3 + 0.6).'rem';
				break;

		}

		$h = '<div class="place-grid-series-timeline-lines" style="height: '.$totalHeight.'">';

			foreach($lines as $key => $line) {

				$positions = [];

				foreach($line as $ePlace) {

					$isPlaceholder = (
						$placeholderSource !== NULL and
						$ePlaceholder->notEmpty() and
						$ePlaceholder['id'] === $ePlace['series']['id']
					);

					if($isPlaceholder) {
						continue;
					}

					$top = match($key) {

						0 => '0.3rem',
						default => '0.3rem + ('.$key.' * (2rem + 0.3rem))'

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
							$top .= ' + '.((count($positions) - 1) * 0.3).'rem';
						}

					}

					$h .= $this->getSeriesTimeline($eFarm, $eBed, $season, $ePlace, $ePlace['series'], $ePlace['series']['cCultivation'], FALSE, 'top: calc('.$top.')');

				}

			}

			if($placeholderSource !== NULL) {

				$ePlace = new Place([
					'missing' => FALSE,
					'series' => new Series(),
					'task' => new Task()
				]);

				$ePlace[$placeholderSource] = $ePlaceholder;

				$this->positionPlace($ePlace, $season, $firstWeekShown, $lastWeekShown);

				if($ePlace['positionStart'] !== NULL and $ePlace['positionStop'] !== NULL) {
					$h .= $this->getSeriesTimeline($eFarm, $eBed, $season, $ePlace, $ePlaceholder, $ePlaceholder['cCultivation'], TRUE, 'height: '.$totalHeight.'; top: 0;');
				}

			}

		$h .= '</div>';

		return $h;

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

				$position = $ePlace['positionStart'] + \Setting::get('missingWeeks') + ($ePlace['series']['season'] - $season) * 100;
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

	protected function positionToTimestamp(Series $eSeries, int $position, int $season): int {

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

		return strtotime($year.'-W'.$week.' + 3 DAYS');

	}

	protected function getSeriesTimeline(\farm\Farm $eFarm, \map\Bed $eBed, int $season, Place $ePlace, Series $eSeries, \Collection $cCultivation, bool $isPlaceholder, string $style): string {

		$ePlace->expects(['missing']);
		$eFarm->expects(['calendarMonths', 'calendarMonthStart', 'calendarMonthStop']);

		$cCultivation->expects([
			'harvestWeeks', 'harvestWeeksExpected',
			'cTask'
		]);

		$minTs = $this->positionToTimestamp($eSeries, $ePlace['positionStart'], $season);
		$maxTs = $this->positionToTimestamp($eSeries, $ePlace['positionStop'], $season);

		$h = '';

		$details = '';

		if($isPlaceholder) {
			$class = 'place-grid-series-timeline-light';
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

		[$startTs, $stopTs] = $this->getPositionTimestamp($eFarm, $season);

		$id = uniqid('place-timeline-');

		if($details) {
			$dropdown = 'data-dropdown="bottom-'.($minTs < $startTs ? 'end' : 'start').'" data-dropdown-hover="true" data-dropdown-offset-x="0" data-dropdown-enter-timeout="0"';
		} else {
			$dropdown = '';
		}

		$h .= '<a href="'.SeriesUi::url($eSeries).'" id="'.$id.'" class="place-grid-series-timeline '.$class.' '.($ePlace['missing'] ? 'place-grid-series-timeline-alert' : '').' '.($details ? 'place-grid-series-timeline-with-details' : '').'" style="'.$style.'" '.$dropdown.' data-ajax-navigation="notouch">';

			if($isPlaceholder) {

				if($ePlace['missing']) {
					$h .= \Asset::icon('exclamation-circle-fill', ['class' => 'color-white']).' ';
				}

			} else {

				$h .= '<div class="place-grid-series-timeline-images">';

					foreach($cCultivation as $eCultivation) {
						$h .= '<div class="place-grid-series-timeline-plant">';
							$h .= \plant\PlantUi::getVignette($eCultivation['plant'], '1.4rem');
						$h .= '</div>';
					}
					if($eSeries['status'] === Series::CLOSED) {
						$h .= '<div class="place-grid-series-timeline-lock">';
							$h .= \Asset::icon('lock-fill');
						$h .= '</div>';
					}

				$h .= '</div>';

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

			$h .= '<style>';
				$h .= $this->getPositionStyle($id, $startTs, $stopTs, $minTs, $maxTs);
			$h .= '</style>';

		$h .= '</a>';

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

	public function getPositionTimestamp(\farm\Farm $eFarm, int $season): array {

		$startTs = $eFarm['calendarMonthStart'] ? strtotime(($season - 1).'-'.sprintf('%02d', $eFarm['calendarMonthStart']).'-01') : strtotime($season.'-01-01');

		if($eFarm['calendarMonthStop']) {

			$date = new \DateTime(($season + 1).'-'.$eFarm['calendarMonthStop'].'-01');
			$date->modify('last day of this month');

			$stopTs = $date->getTimestamp();

		} else {
			$stopTs = strtotime($season.'-12-31');
		}

		return [$startTs, $stopTs];

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
			$css .= '#'.$id.' { left: calc('.$left.'%'.($shiftLeft ? ' + '.$shiftLeft : '').'); width: '.($width ?: 'calc('.($right - $left).'% + 1px)').'; }';
		} else {
			$css .= '#'.$id.' { right: calc('.(100 - $right).'%'.($shiftRight ? ' + '.$shiftRight : '').'); width: '.($width ?: 'calc('.($right - $left).'% + 1px)').'; }';
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
