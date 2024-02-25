<?php
namespace map;

class BedUi {

	public function __construct() {

		\Asset::css('map', 'bed.css');
		\Asset::js('map', 'bed.js');

	}

	public function displayFromPlot(\farm\Farm $eFarm, Plot $ePlot, int $season): string {

		$beds = $this->displayBedsFromPlot($eFarm, $ePlot, $season);

		if($beds === '') {
			return '';
		}

		$view = \Setting::get('main\viewMap');

		\Asset::css('series', 'series.css');

		$class = 'bed-item-grid bed-item-grid-'.$view;
		if($view === \farm\Farmer::HISTORY) {
			$class .= ' bed-item-grid-history-'.$eFarm['rotationYears'];
		}

		$h = '<div class="'.$class.' bed-item-grid-header">';

			$h .= '<div class="util-grid-header bed-item-header">'.s("Emplacement").'</div>';

			if($view === \farm\Farmer::SOIL) {
				$h .= '<div class="util-grid-header bed-item-header bed-item-header-size"></div>';
			}

			$h .= '<div class="util-grid-header bed-item-header bed-item-area"></div>';

			$h .= match($view) {
				\farm\Farmer::SOIL => $this->displayHeaderBySeason($eFarm, $season),
				\farm\Farmer::HISTORY => $this->displayHeaderByHistory($season, $eFarm['rotationYears'])
			};

		$h .= '</div>';

		$h .= '<div class="bed-item-wrapper">';

			if($view === \farm\Farmer::SOIL) {
				$h .= (new \series\CultivationUi())->getListGrid($eFarm, $season);
			}

			$h .= $beds;

		$h .= '</div>';

		return $h;

	}

	public function displayBedsFromPlot(\farm\Farm $eFarm, Plot $ePlot, int $season): string {

		$view = \Setting::get('main\viewMap');

		$cBed = $ePlot['cBed'];

		$h = '';

		foreach($cBed as $eBed) {

			if(
				($view === \farm\Farmer::HISTORY and $eBed['plotFill']) or
				($view === \farm\Farmer::SOIL and $eBed['plotFill'] and $eBed['cPlace']->empty())
			) {
				continue;
			}

			$place = match($view) {
				\farm\Farmer::SOIL => $this->displayPlaceBySeason($eFarm, $eBed, $eBed['cPlace'], $season),
				\farm\Farmer::HISTORY => $this->displayPlaceByHistory($eBed['cPlace'], $season, $eFarm)
			};

			$class = 'bed-item-grid bed-item-grid-'.$view;
			if($view === \farm\Farmer::HISTORY) {
				$class .= ' bed-item-grid-history-'.$eFarm['rotationYears'];
			}

			$h .= '<div class="'.$class.'">';

				if($eBed['plotFill']) {

					$h .= '<div class="bed-item-bed bed-item-bed-fill">';
						$h .= s("Surface libre");
					$h .= '</div>';

				} else {

					$name = s("Planche {value}", encode($eBed['name']));

					$h .= '<div class="bed-item-bed">';

						$h .= '<div>';
							$h .= '<a data-dropdown="bottom-start">'.$name.'</a>';

							$h .= '<div class="dropdown-list bg-primary">';
								$h .= '<div class="dropdown-title">'.$name.'</div>';
								$h .= '<a href="/map/bed:swapSeries?id='.$eBed['id'].'&season='.$season.'" class="dropdown-item">'.s("Échanger les séries").'</a>';
							$h .= '</div>';
						$h .= '</div>';

						if($eBed['plotFill'] === FALSE and $eBed['zoneFill'] === FALSE) {
							$h .= $eBed->getGreenhouseIcon();
						}

					$h .= '</div>';

					if($view === \farm\Farmer::SOIL) {
						$h .= '<div class="bed-item-size">';
							$h .= s("{length}&nbsp;mL x&nbsp;{width}&nbsp;cm", $eBed);
						$h .= '</div>';
					}

					$h .= '<div class="bed-item-area">';
						$h .= s("{area} m²", $eBed);
					$h .= '</div>';

				}

				$h .= $place;

			$h .= '</div>';

		}

		return $h;

	}

	protected function displayHeaderBySeason(\farm\Farm $eFarm, int $season): string {

		$h = '<div class="bed-item-header bed-item-places">';
			$h .= (new \series\CultivationUi())->getListSeason($eFarm, $season);
		$h .= '</div>';

		return $h;

	}

	protected function displayPlaceBySeason(\farm\Farm $eFarm, Bed $eBed, \Collection $cPlace, int $season): string {

		$h = '<div class="bed-item-places">';
			$h .= (new \series\PlaceUi())->getTimeline($eFarm, $eBed, $cPlace, $season);
		$h .= '</div>';

		return $h;

	}

	protected function displayHeaderByHistory(int $season, int $number): string {

		$h = '';

		for($i = $season; $i > $season - $number; $i--) {
			$h .= '<div class="util-grid-header bed-item-header '.($i === $season ? 'ml-1' : '').'">'.$i.'</div>';
		}

		return $h;

	}

	protected function displayPlaceByHistory(\Collection $cPlace, int $season, \farm\Farm $eFarm): string {

		$cCultivationBySeason = new \Collection();
		$cFamily = new \Collection();

		foreach($cPlace as $ePlace) {

			foreach($ePlace['series']['cCultivation'] as $eCultivation) {

				$cultivationSeason = $eCultivation['season'];
				$cCultivationBySeason[$cultivationSeason][] = $eCultivation;

				if($eCultivation['plant']['family']->empty()) {
					$cFamily[$cultivationSeason][NULL] = $eCultivation['plant']['family'];
				} else {
					$cFamily[$cultivationSeason][$eCultivation['plant']['family']['id']] = $eCultivation['plant']['family'];
				}

			}

		}

		$h = '';

		for($i = $season; $i > $season - $eFarm['rotationYears']; $i--) {

			$h .= '<div class="bed-item-cultivation '.($i === $season ? 'ml-1' : '').'">';

			if(empty($cCultivationBySeason[$i])) {
				$h .= '-';
			} else {

				$h .= '<div class="bed-item-cultivation-family">';

				foreach($cFamily[$i] as $eFamily) {

					$h .= '<div>';
						$h .= \plant\FamilyUi::getLetterVignette($eFamily, $eFarm);
					$h .= '</div>';

				}

				$h .= '</div>';

				$h .= '<div class="bed-item-cultivation-plant">';

					$h .= implode(' / ', array_map(function(\series\Cultivation $e) {
						return '<a href="'.\series\SeriesUi::url($e['series']).'">'.encode($e['plant']['name']).'</a>';;
					}, $cCultivationBySeason[$i]));

				$h .= '</div>';

			}

			$h .= '</div>';

		}

		return $h;

	}

	public function getHead(Plot $ePlot): string {

		$h = '<div>';
			$h .= '<h4>'.p("{beds} planche permanente de {bedsArea} m²", "{beds} planches permanentes totalisant {bedsArea} m²", $ePlot['beds'], ['beds' => $ePlot['beds'], 'bedsArea' => $ePlot['bedsArea']]).'</h4>';
		$h .= '</div>';

		return $h;

	}

	public function configure(int $season, Zone $eZone, Plot $ePlot, \Collection $cBed, \Collection $cGreenhouse): string {

		$form = new \util\FormUi();
		$formId = 'bed-update-selection-'.$ePlot['id'];

		$h = $this->getHead($ePlot);

		$h .= $form->open($formId);
		$h .= $form->hidden('plot', $ePlot['id']);
		$h .= $form->hidden('season', $season);

		$withGreenhouse = ($ePlot['zoneFill']);

		$h .= '<div class="stick-xs bed-update-grid '.($withGreenhouse ? 'bed-update-grid-with-greenhouse' : '').'">';

			if($eZone['farm']->canManage()) {
				$h .= '<label class="util-grid-header bed-update-grid-select" title="'.s("Tout cocher / Tout décocher").'">';
					$h .= '<input type="checkbox" onclick="Bed.toggleSelection(this)"/>';
				$h .= '</label>';
			} else {
				$h .= '<label></label>';
			}
			$h .= '<div class="util-grid-header">'.s("Planche").'</div>';
			$h .= '<div class="util-grid-header" style="grid-column: span 4">'.s("Dimensions").'</div>';
			$h .= '<div class="util-grid-header"></div>';
			$h .= '<div class="util-grid-header bed-update-grid-actions"></div>';

			$cBed->sort('name', natural: TRUE);

			foreach($cBed as $eBed) {

				if($eBed['plotFill']) {
					continue;
				}

				if($eZone['farm']->canManage()) {
					$h .= '<label class="bed-update-grid-select">'.$form->inputCheckbox('ids[]', $eBed['id'], [
						'onclick' => 'Bed.changeSelection(this)',
						'data-greenhouse' => $eBed['greenhouse']->empty() ? '' : $eBed['greenhouse']['id'],
						'data-drawn' => $eBed['drawn'] ? 1 : 0,
					]).'</label>';
				} else {
					$h .= '<label></label>';
				}

				$h .= '<div class="bed-update-grid-name">';

					if($eZone['farm']->canManage()) {
						$h .= '<span class="hide-sm-down">'.$eBed->quick('name', encode($eBed['name'])).'</span>';
						$h .= '<a data-dropdown="bottom-start" class="hide-md-up">'.encode($eBed['name']).'</a>';
						$h .= '<div class="dropdown-list">';
							$h .= '<div class="dropdown-title">'.encode($eBed['name']).'</div>';
							$h .= '<a href="/map/bed:update?id='.$eBed['id'].'" class="dropdown-item">'.s("Modifier la planche").'</a> ';
							$h .= '<a data-ajax="/map/bed:doDelete" post-id="'.$eBed['id'].'" data-confirm="'.s("Souhaitez-vous réellement supprimer la planche {value} ?", encode($eBed['name'])).'" class="dropdown-item">'.s("Supprimer la planche").'</a>';
						$h .= '</div>';
					} else {
						$h .= encode($eBed['name']);
					}

					$h .='<span class="bed-update-grid-name-interval hide-xs-down">'.SeasonUi::getInterval($eBed).'</span>';

				$h .= '</div>';
				$h .= '<div class="bed-update-grid-length">'.s("{length} mL", $eBed).'</div>';
				$h .= '<div class="bed-update-grid-x">x</div>';
				$h .= '<div>'.s("{width} cm", $eBed).'</div>';
				$h .= '<div class="bed-update-grid-area">'.s("{area} m²", $eBed).'</div>';

				$h .= '<div>';

					if($eBed['drawn']) {
						$h .= '<span class="bed-update-grid-name-drawn hide-xs-down" title="'.s("Dessiné sur la carte").'">'.\Asset::icon('geo-fill').'</span>';
					}

					if($eBed['greenhouse']->notEmpty()) {

						$h .= \Asset::icon('greenhouse');
						if($ePlot['zoneFill']) {
							$h .= '&nbsp;&nbsp;'.encode($eBed['greenhouse']['name']);
						}
					}

				$h .= '</div>';

				$h .= '<div class="bed-update-grid-actions">';
					if($eZone['farm']->canManage()) {
						$h .= '<a href="/map/bed:update?id='.$eBed['id'].'" class="btn btn-sm btn-outline-secondary">'.\Asset::icon('pencil-fill').'</a> ';
						$h .= '<a data-ajax="/map/bed:doDelete" post-id="'.$eBed['id'].'" post-season="'.$season.'" data-confirm="'.s("Souhaitez-vous réellement supprimer cette planche ?").'" class="btn btn-sm btn-outline-secondary">'.\Asset::icon('trash').'</a>';
					}
				$h .= '</div>';

			}

			if($eZone['farm']->canManage()) {

				$h .= '<label class="bed-update-grid-select bed-update-grid-group">'.\Asset::icon('arrow-return-right').'</label>';
				$h .= '<div class="bed-configure-actions bed-update-grid-group">';
					$h .= '<a class="dropdown-toggle" data-dropdown="top-start">'.s("Modifier les planches sélectionnées").'</a>';
					$h .= '<div class="dropdown-list bg-secondary">';
						$h .= '<a class="dropdown-item" data-ajax-target="#'.$formId.'" data-ajax-method="get" data-ajax-submit="/map/bed:updateSizeCollection">'.s("Modifier les dimensions").'</a>';
						$h .= '<a class="dropdown-item" data-ajax-target="#'.$formId.'" data-ajax-method="get" data-ajax-submit="/map/bed:updateSeasonCollection">'.s("Modifier les saisons d'exploitation").'</a>';
						$h .= '<a class="dropdown-item" data-ajax-target="#'.$formId.'" data-ajax-method="post" data-ajax-submit="/map/bed:doDeleteCollection" data-confirm="'.s("Êtes-vous sûr de vouloir supprimer définitivement les planches sélectionnées ?").'">'.s("Supprimer les planches").'</a>';


						$h .= '<div class="dropdown-title">'.s("Dessiner").'</div>';
						$h .= '<a class="dropdown-item" data-ajax-target="#'.$formId.'" data-ajax-method="get" data-ajax-submit="/map/bed:updateBedLineCollection">'.s("Dessiner sur la carte").'</a>';
						$h .= '<a class="dropdown-item hide" data-batch="draw-delete" data-ajax-target="#'.$formId.'" data-ajax-method="post" data-ajax-submit="/map/bed:doDeleteBedLineCollection" data-confirm="'.s("Les planches sélectionnées ne seront plus visibles sur la carte, voulez-vous continuer ?").'">'.s("Effacer de la carte").'</a>';


						if($ePlot['zoneFill']) {

							$cGreenhousePlot = $cGreenhouse->find(fn($eGreenhouse) => $eGreenhouse['plot']['id'] === $ePlot['id']);
							$hasBedGreenhouse = $cBed->find(fn($eBed) => $eBed['greenhouse']->notEmpty())->notEmpty();

							if(
								$cGreenhousePlot->notEmpty() or
								$hasBedGreenhouse
							) {

								$h .= '<div class="dropdown-title">'.s("Abris").'</div>';

								foreach($cGreenhousePlot as $eGreenhouse) {

									$h .= '<a class="dropdown-item" data-ajax-target="#'.$formId.'" data-ajax-method="post" data-ajax-submit="/map/bed:doUpdateGreenhouseCollection" post-season="'.$season.'" post-greenhouse="'.$eGreenhouse['id'].'">'.s("Installer sous l'abri {value}", '<u>'.encode($eGreenhouse['name']).'</u>').'</a>';

								}

								if($hasBedGreenhouse) {
									$h .= '<a class="dropdown-item hide" data-batch="greenhouse-delete" data-ajax-target="#'.$formId.'" data-ajax-method="post" data-ajax-submit="/map/bed:doUpdateGreenhouseCollection" post-season="'.$season.'" post-greenhouse="">'.s("Enlever l'abri").'</a>';
								}

							}

						}

					$h .= '</div>';
				$h .= '</div>';

			}

		$h .= '</div>';

		$h .= $form->close();

		return $h;

	}

	public function createCollection(int $season, Plot $ePlot, \Collection $cGreenhouse): \Panel {

		$eBed = new Bed();

		$h = '<p class="util-info">';
			$h .= s("Ajoutez ici la liste des planches de cet emplacement, en précisant le nom, la longueur et la largeur travaillée de chaque planche. Vous pouvez ajouter simultanément plusieurs planches de mêmes dimensions !");
		$h .= '</p>';

		$form = new \util\FormUi();

		$h .= $form->openAjax('/map/bed:doCreate', ['id' => 'bed-create', 'autocomplete' => 'off']);

			$beds = '<div class="input-group">';
				$beds .= $form->number('number', attributes: ['id' => 'bed-create-number']);
				$beds .= $form->button(s("Valider"), ['id' => 'bed-create-button']);
			$beds .= '</div>';

			$h .= $form->group(
				s("Nombre de planches à ajouter"),
				$beds,
				['class' => 'bed-create-number']
			);

			$h .= $form->hidden('plot', $ePlot);
			$h .= $form->hidden('season', $season);

			$h .= '<div id="bed-create-form" data-number="0">';

				$names = '<div class="util-block-optional">';
					$names .= '<h4>'.s("Aide au remplissage automatique du nom des planches").'</h4>';
					$names .= '<div class="bed-create-fill">';
						$names .= '<div class="input-group">';
							$names .= '<span class="input-group-addon">'.\Asset::icon('1-circle-fill').'&nbsp;'.s("Préfixe").'</span>';
							$names .= $form->text(attributes: ['id' => 'bed-create-prefix', 'placeholder' => s("XXX-")]);
						$names .= '</div>';
						$names .= '<div class="input-group">';
							$names .= '<span class="input-group-addon">'.\Asset::icon('2-circle-fill').'&nbsp;'.s("Début de numérotation").'</span>';
							$names .= $form->number(value: 1, attributes: ['id' => 'bed-create-start']);
						$names .= '</div>';
						$names .= '<div>';
							$names .= $form->button(s("Nommer"), ['id' => 'bed-create-auto']);
						$names .= '</div>';
					$names .= '</div>';
				$names .= '</div>';
				$names .= '<div id="bed-create-names" data-input="'.encode('<div class="bed-create-one"><h5>'.s("Planche").'</h5>'.$form->text('names[]', '', ['placeholder' => s("Donnez un nom")]).'</div>').'"></div>';

				$h .= $form->group(
					s("Nom des planches"),
					$names,
					['wrapper' => 'names']
				);

				$h .= $form->group(
					s("Dimensions de ces planches"),
					$this->getSizeField($form, $eBed, $ePlot['farm']['defaultBedLength'], $ePlot['farm']['defaultBedWidth']),
					['wrapper' => 'width length']
				);

				if(
					$cGreenhouse->notEmpty() and
					$ePlot['zoneFill']
				) {

					$h .= $form->dynamicGroup($eBed, 'greenhouse', function(\PropertyDescriber $d) use ($cGreenhouse) {
						$d->values = $cGreenhouse;
					});

				}

				$h .= $form->group(
					content: $form->submit(s("Ajouter"))
				);

			$h .= '</div>';

		$h .= $form->close();

		return new \Panel(
			id: 'panel-bed-create',
			title: s("Ajouter des planches"),
			body: $h
		);

	}

	public function update(int $season, Bed $eBed, \Collection $cPlot): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/map/bed:doUpdate', ['id' => 'bed-update', 'autocomplete' => 'off', 'data-ajax-origin' => \Route::getRequestedOrigin()]);

			$h .= $form->hidden('id', $eBed);
			$h .= $form->hidden('season', $season);

				$h .= $form->dynamicGroup($eBed, 'plot', function(\PropertyDescriber $d) use ($cPlot) {

					$d->values = $cPlot->makeArray(function(Plot $ePlot, ?int &$key) {
						$key = $ePlot['id'];
						if($ePlot['zoneFill']) {
							return s("Parcelle");
						} else {
							return s("Bloc {value}", $ePlot['name']);
						}
					});

				});

				$h .= $form->dynamicGroup($eBed, 'name');

				$h .= $form->group(
					s("Dimensions de la planche"),
					$this->getSizeField($form, $eBed),
					['wrapper' => 'width length']
				);

				$h .= $form->group(
					s("Exploitée"),
					(new SeasonUi)->getField($form, $eBed),
					['wrapper' => 'seasonFirst seasonLast']
				);

				$h .= $form->group(
					content: $form->submit(s("Modifier"))
				);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-bed-update',
			title: s("Modifier une planche"),
			body: $h
		);

	}

	public function updateBedLineCollection(int $season, Plot $ePlot, \Collection $cBed): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/map/bed:doUpdateBedLineCollection', ['autocomplete' => 'off']);

			$h .= $form->hidden('plot', $ePlot);
			$h .= $form->hidden('season', $season);

			$h .= $this->updateCollection($form, $ePlot, $cBed);

			$h .= $form->group(
				s("Applicable"),
				s("À partir de la saison {value}", '<b>'.$season.'</b>')
			);

			$h .= $form->group(
				s("Ligne de départ"),
				'<p class="util-info">'.s("Tracez une première ligne qui correspond à la ligne de départ des planches, puis une deuxième ligne qui correspond à la direction des planches.").'</p>'.
				$this->getBedLineField($form, $season, $ePlot, $cBed),
				['wrapper' => 'coordinates']
			);

			$h .= $form->group(
				content: $form->submit(s("Modifier"), ['class' => 'btn btn-secondary'])
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-bed-line',
			title: s("Dessiner des planches sur la carte"),
			body: $h
		);

	}

	protected function getBedLineField(\util\FormUi $form, int $season, Plot $ePlot, \Collection $cBed): string {

		$eZone = $ePlot['zone'];

		$h = '';

		$container = 'bed-map-line';

		$h .= (new MapboxUi())->getDrawingBedLine($container, $form);

		$h .= '<script>';
			$h .= 'document.ready(() => setTimeout(() => {
				new Cartography("'.$container.'", '.$season.', false, true)';

					$h .= '.addZone('.$eZone['id'].', "'.addcslashes($eZone['name'], '"').'", '.json_encode($eZone['coordinates']).')';

					foreach($eZone['cPlot'] as $ePlotZone) {

						if($ePlotZone['zoneFill'] === FALSE) {

							$h .= '.addPlot('.$ePlotZone['id'].', "'.addcslashes($ePlotZone['name'], '"').'", '.$eZone['id'].', '.json_encode($ePlotZone['coordinates']).')';

						}

						if($ePlot['id'] === $ePlotZone['id']) {
							$h .= (new MapUi())->addBeds($ePlotZone, $season, 0.33);
						}

					}


					if($ePlot['zoneFill']) {
						$h .= '.fitZoneBounds('.$eZone['id'].', {duration: 0})';
					} else {
						$h .= '.fitPlotBounds('.$ePlot['id'].', {duration: 0})';
					}
				$h .= '.setBedsTheme("'.($ePlot['zoneFill'] ? 'zone' : 'plot').'")';
				$h .= '.setBedsList('.json_encode($cBed->toArray(fn($eBed) => $eBed->extracts(['length', 'width', 'name']))).')';
				$h .= '.drawBeds('.json_encode(array_unique($ePlot['zoneFill'] ? $eZone['coordinates'] : $ePlot['coordinates'], SORT_REGULAR)).');
			}, 100));';
		$h .= '</script>';
		
		return $h;

	}

	public function updateSizeCollection(int $season, Plot $ePlot, \Collection $cBed): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/map/bed:doUpdateSizeCollection', ['autocomplete' => 'off']);

			$h .= $form->hidden('plot', $ePlot);
			$h .= $form->hidden('season', $season);

			$h .= $this->updateCollection($form, $ePlot, $cBed);

			$h .= $form->group(
				s("Nouvelles dimensions"),
				$this->getSizeField($form, new Bed()),
				['wrapper' => 'width length']
			);

			$h .= $form->group(
				content: $form->submit(s("Modifier"), ['class' => 'btn btn-secondary'])
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-bed-size',
			title: s("Modifier une planche"),
			body: $h
		);

	}

	protected function getSizeField(\util\FormUi $form, Bed $eBed, ?int $defaultBedLength = NULL, ?int $defaultBedWidth = NULL): string {

		if($eBed->empty()) {
			$area = 0;
			$eBed['length'] = $defaultBedLength;
			$eBed['width'] = $defaultBedWidth;
		} else {
			$area = $eBed['area'];
		}

		$h = '<div class="bed-write-size">';
			$h .= '<div class="bed-write-size-form">';
				$h .= $form->dynamicField($eBed, 'length');
				$h .= '<span>x</span>';
				$h .= $form->dynamicField($eBed, 'width');
			$h .= '</div>';
			$h .= '<div class="bed-write-size-area" data-area="'.$area.'">';
				$h .= s("Surface de planche : {value} m²", '<span>'.$area.'</span>');
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function updateSeasonCollection(int $season, Plot $ePlot, \Collection $cBed): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/map/bed:doUpdateSeasonCollection', ['autocomplete' => 'off']);

			$h .= $form->hidden('plot', $ePlot);
			$h .= $form->hidden('season', $season);

			$h .= $this->updateCollection($form, $ePlot, $cBed);

			$eBedField = new Bed([
				'plot' => $ePlot,
				'zone' => $ePlot['zone'],
				'farm' => $ePlot['farm'],
			]);

			$h .= $form->group(
				s("Exploité"),
				(new SeasonUi)->getField($form, $eBedField),
				['wrapper' => 'seasonFirst seasonLast']
			);

			$h .= $form->group(
				content: $form->submit(s("Modifier"), ['class' => 'btn btn-secondary'])
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-bed-season',
			title: s("Modifier une planche"),
			body: $h
		);

	}

	public function swapSeries(Bed $eBed, int $season, \Collection $cZone): \Panel {

		$form = new \util\FormUi();

		$beds = [];

		foreach($cZone as $eZone) {

			if($eZone['cPlot']->empty()) {
				continue;
			}

			$zoneBeds = [];

			foreach($eZone['cPlot'] as $ePlot) {

				if($ePlot['cBed']->empty()) {
					continue;
				}

				if($ePlot['zoneFill'] === FALSE) {

					$zoneBeds[] = [
						'label' => s("Bloc {value}", $ePlot['name']),
						'attributes' => ['disabled', 'style' => 'font-weight: bold; background-color: var(--background)']
					];

					$prefix = $ePlot['name'];

				} else {
					$prefix = $eZone['name'];
				}

				$ePlot['cBed']->sort('name', natural: TRUE);

				foreach($ePlot['cBed'] as $eBedSwap) {
					if($eBedSwap['plotFill'] === FALSE) {
						$zoneBeds[] = [
							'value' => $eBedSwap['id'],
							'label' => $prefix.' > '.$eBedSwap['name']
						];
					}
				}

			}

			$beds[] = [
				'label' => s("Zone {value}", $eZone['name']),
				'attributes' => ['disabled', 'style' => 'font-weight: bold; color: white; background-color: var(--text)']
			];

			$beds = array_merge($beds, $zoneBeds);

		}

		$h = $form->openAjax('/map/bed:doSwapSeries', ['id' => 'bed-update', 'autocomplete' => 'off', 'data-ajax-origin' => \Route::getRequestedOrigin()]);

			$h .= $form->hidden('id', $eBed);
			$h .= $form->hidden('season', $season);

			$h .= '<div class="util-info">';
				$h .= s("Choisissez la planche avec laquelle vous souhaitez échanger les séries pour la saison {value}.", $season);
			$h .= '</div>';

			$bed1 = s("Zone {value}", '<b>'.encode($eBed['zone']['name'])).'</b>';
			if($eBed['plot']['zoneFill'] === FALSE) {
				$bed1 .= ' &gt; <b>'.encode($eBed['plot']['name']).'</b> ';
			}
			$bed1 .= ' &gt; <b>'.encode($eBed['name']).'</b>';

			$h .= $form->group(
				s("Planche 1"),
				$bed1
			);

			$h .= $form->group(
				s("Planche 2"),
				$form->select('swapId', $beds, $eBed)
			);

			$h .= $form->group(
				content: $form->submit(s("Échanger les séries"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Échanger les séries de deux planches"),
			body: $h,
			close: 'reload'
		);

	}

	protected function updateCollection(\util\FormUi $form, Plot $ePlot, \Collection $cBed): string {

		$beds = [];

		foreach($cBed as $eBed) {
			$beds[] = $form->hidden('ids[]', $eBed['id']).'<b>'.encode($eBed['name']).'</b>';
		}

		$h = '';

		if($ePlot['zoneFill']) {

			$h .= $form->group(
				s("Parcelle"),
				'<div class="form-control disabled">'.encode($ePlot['zone']['name']).'</div>'
			);

		} else {

			$h .= $form->group(
				s("Bloc"),
				'<div class="form-control disabled">'.encode($ePlot['name']).'</div>'
			);

		}

		$h .= $form->group(
			s("Planches"),
			implode(', ', $beds)
		);

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Bed::model()->describer($property, [
			'name' => s("Nom de la planche"),
			'farm' => s("Ferme"),
			'zone' => s("Parcelle"),
			'plot' => s("Bloc"),
			'length' => s("Longueur de planche"),
			'width' => s("Largeur travaillée de planche"),
			'greenhouse' => s("Abri"),
			'seasonFirst' => s("Exploité depuis"),
			'seasonLast' => s("Exploité jusqu'à"),
			'area' => s("Surface"),
			'createdAt' => s("Créée le"),
		]);

		switch($property) {

			case 'name' :
				$d->attributes = [
					'placeholder' => s("Ex. : A1")
				];
				break;

			case 'plot' :
				$d->attributes = ['mandatory' => TRUE];
				break;

			case 'length' :
				$d->prepend = s("Longueur");
				$d->append = s("m");
				break;

			case 'width' :
				$d->prepend = s("Largeur travaillée");
				$d->append = s("cm");
				break;

			case 'seasonFirst' :
			case 'seasonLast' :
				$d->field = function(\util\FormUi $form, \Element $e, $property) {

					$e->expects(['plot', 'zone', 'farm']);

					$placeholder = [
						'seasonFirst' => s("la création du bloc"),
						'seasonLast' => s("la disparition du bloc")
					][$property];

					return (new SeasonUi())->getDescriberField($form, $e, $e['farm'], $e['zone'], $e['plot'], $property, $placeholder);
				};
				break;

		}

		return $d;

	}

}
?>
