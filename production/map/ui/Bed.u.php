<?php
namespace map;

class BedUi {

	public function __construct() {

		\Asset::css('map', 'bed.css');
		\Asset::js('map', 'bed.js');

	}

	public function displayBedsFromPlot(\farm\Farm $eFarm, Plot $ePlot, int $season, \series\Series|\series\Task $eUpdate, bool $print): string {

		$form = new \util\FormUi();

		$cBed = $ePlot['cBed'];

		$h = '';

		foreach($cBed as $eBed) {

			$place = $this->displayPlaceBySeason($eFarm, $eBed, $eBed['cPlace'], $season, $eUpdate, $print);

			if(
				$eUpdate->empty() and
				$eBed['plotFill'] and
				$place === ''
			) {
				continue;
			}

			if($eUpdate->notEmpty()) {

				if(
					$eBed['plotFill'] === FALSE and
					$eUpdate['use'] === \series\Series::BLOCK
				) {
					continue;
				}

				if(
					$eUpdate instanceof \series\Task and
					$eBed['plotFill']
				) {
					continue;
				}

			}

			if($eBed['zoneFill']) {
				$class = 'bed-item-fill bed-item-fill-zone';
			} else if($eBed['plotFill']) {
				$class = 'bed-item-fill bed-item-fill-plot';
			} else {
				$class = '';
			}

			if($eUpdate->notEmpty()) {

				$ePlace = $eUpdate['cPlace'][$eBed['id']] ?? new \series\Place();


			} else {
				$ePlace = new \series\Place();
			}

			$h .= '<div class="bed-item-grid bed-item-grid-plan '.$class.'" '.$this->getTest($eBed).'>';

				if($eBed['plotFill']) {

					$h .= '<div class="bed-item-bed">';

						$h .= '<div class="bed-item-content">';

							if($eUpdate->notEmpty()) {

								$h .= '<div class="bed-write">';

									switch($eUpdate['use']) {

										case \series\Series::BED :

											$h .= '<b>'.s("Planche temporaire").'</b>';
											$h .= '<div class="bed-item-size bed-item-size-fill">';

													$h .= $form->inputGroup(
														$form->number('sizes['.$eBed['id'].']', $ePlace->notEmpty() ? $ePlace['length'] : $eBed['length'], [
															'min' => 0,
															'max' => $eBed['length'],
															'onfocus' => 'this.select()',
															'oninput' => 'Place.selectBed(this)'
														]).
														$form->addon(s("mL"))
													);

											$h .= '</div>';
											break;

										case \series\Series::BLOCK :

											$h .= s("Surface libre");
											$h .= '<div class="bed-item-size bed-item-size-fill">';

												$h .= $form->inputGroup(
													$form->number('sizes['.$eBed['id'].']', $ePlace->notEmpty() ? $ePlace['area'] : $eBed['area'], [
														'min' => 0,
														'max' => $eBed['area'],
														'onfocus' => 'this.select()',
														'oninput' => 'Place.selectBed(this)'
													]).
													$form->addon(s("m²"))
												);

											$h .= '</div>';
											break;

									}

								$h .= '</div>';

							}

							$h .= '<div class="bed-read">'.s("Surface libre").'</div>';

						$h .= '</div>';
					$h .= '</div>';

				} else {

					$h .= '<div class="bed-item-bed">';

						if($eUpdate->notEmpty()) {

							$h .= '<label class="bed-item-select bed-write">'.$form->inputCheckbox('beds[]', $eBed['id'], ['checked' => $ePlace->notEmpty()]).'</label>';

						}

						$h .= '<div class="bed-item-content">';
							$h .= '<div class="bed-item-name">';

								$h .= '<div>';
									$h .= '<a data-dropdown="bottom-start">';
										$h .= '<b>'.encode($eBed['name']).'</b>';
										if($eBed['plotFill'] === FALSE and $eBed['zoneFill'] === FALSE) {
											$greenhouse = $eBed->getGreenhouseIcon();
											if($greenhouse) {
												$h .= '  '.$greenhouse;
											}
										}
									$h .= '</a>';
									$h .= '<div class="dropdown-list bg-primary">';
										$h .= '<div class="dropdown-title">';
											$h .= s("Planche {value}", encode($eBed['name']));
											$h .= '<div class="font-sm">'.s("{length} mL x {width} cm", $eBed).'</div>';
										$h .= '</div>';
										$h .= '<a href="/map/bed:swapSeries?id='.$eBed['id'].'&season='.$season.'" class="dropdown-item">'.s("Échanger les séries").'</a>';
									$h .= '</div>';
								$h .= '</div>';

								if(($eBed['test']['rotation'] ?? NULL) > 0) {
									$h .= '<div class="bed-item-rotation" title="'.s("Rotation sur la même famille").'">';
										$h .= \Asset::icon('arrow-clockwise').' '.p("{value} an", "{value} ans", $eBed['test']['rotation']);
									$h .= '</div>';
								}

							$h .= '</div>';


							if($eUpdate->notEmpty() and $eUpdate instanceof \series\Series) {

								$h .= '<div class="bed-item-size bed-write">';

									if(
										$eUpdate['use'] === \series\Series::BED and
										$eUpdate['bedWidth'] !== NULL and
										$eUpdate['bedWidth'] !== $eBed['width']
									) {
										$width = '<span class="color-danger" style="font-weight: bold">'.\Asset::icon('exclamation-circle').' '.s("{width} cm", $eBed).'</span>';
									} else {
										$width = s("{width} cm", $eBed);
									}

									$h .= '<div class="bed-item-size-write">';

										$h .= $form->inputGroup(
											$form->number('sizes['.$eBed['id'].']', $ePlace->notEmpty() ? $ePlace['length'] : $eBed['length'], [
												'min' => 0,
												'max' => $eBed['length'],
												'onfocus' => 'this.select()'
											]).
											$form->addon(s("mL x {width}", ['width' => $width]))
										);

									$h .= '</div>';

									$h .= '<div class="bed-item-size-read">';
										$h .= s("{length} mL x {width}", ['length' => $eBed['length'], 'width' => $width]);
									$h .= '</div>';

								$h .= '</div>';

							}

							$h .= '<div class="bed-item-size bed-read">';
								$h .= '<span title="'.s("{area} m²", $eBed).'">'.s("{length} mL x {width} cm", $eBed).'</span>';
							$h .= '</div>';

						$h .= '</div>';
					$h .= '</div>';

				}

				$h .= $place;

			$h .= '</div>';

		}

		return $h;

	}

	protected function getTest(Bed $eBed): string {

		if(isset($eBed['test'])) {
			return 'data-same-width="'.($eBed['test']['sameWidth'] ? 1 : 0).'" data-greenhouse="'.($eBed['test']['hasGreenhouse'] ? 1 : 0).'" data-rotation="'.$eBed['test']['rotation'].'" data-free="'.$eBed['test']['free'].'" data-hide="'.($eBed['test']['hide'] ? 1 : 0).'"';
		} else {
			return '';
		}

	}

	protected function displayPlaceBySeason(\farm\Farm $eFarm, Bed $eBed, \Collection $cPlace, int $season, \series\Series|\series\Task $ePlaceholder, bool $print): string {

		$timeline = new \series\PlaceUi()->getTimeline($eFarm, $eBed, $cPlace, $season, $ePlaceholder, $print);

		if($timeline !== '') {
			return '<div class="bed-item-places">'.$timeline.'</div>';
		} else {
			return '';
		}

	}

	public function getRotations(\farm\Farm $eFarm, \Collection $cBed, int $season): string {

		$h = '';

		foreach($cBed as $eBed) {

			if($eBed['plotFill']) {
				continue;
			}

			$h .= '<div class="bed-item-grid bed-item-grid-rotation-'.$eFarm['rotationYears'].'">';

				$h .= '<div class="bed-item-bed">';

					$h .= '<div class="bed-item-content">';
						$h .= '<div class="bed-item-name">';
							$h .= '<b>'.encode($eBed['name']).'</b>';
						$h .= '</div>';

					$h .= '</div>';
				$h .= '</div>';


				$h .= new BedUi()->displayPlaceByHistory($eBed['cPlace'], $season, $eFarm);

			$h .= '</div>';

		}

		return $h;

	}

	public function displayPlaceByHistory(\Collection $cPlace, int $season, \farm\Farm $eFarm): string {

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
						$h .= '<a data-ajax="/map/bed:doDelete" post-id="'.$eBed['id'].'" post-season="'.$season.'" data-confirm="'.s("Souhaitez-vous réellement supprimer cette planche ? Si vous souhaitez conserver un historique de votre plan de ferme, modifiez plutôt les saisons d'exploitation.").'" class="btn btn-sm btn-outline-secondary">'.\Asset::icon('trash').'</a>';
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
						$h .= '<a class="dropdown-item" data-ajax-target="#'.$formId.'" data-ajax-method="post" data-ajax-submit="/map/bed:doDeleteCollection" data-confirm="'.s("Êtes-vous sûr de vouloir supprimer les planches sélectionnées ? Si vous souhaitez conserver un historique de votre plan de ferme, modifiez plutôt les saisons d'exploitation.").'">'.s("Supprimer les planches").'</a>';


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
		$eFarm = $ePlot['farm'];

		$h = '';

		if($eFarm['defaultBedWidth'] === NULL) {
			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Vous pouvez gagner du temps en indiquant les longueur et largeur de planche par défaut sur votre ferme.").'</p>';
				$h .= '<a href="/farm/farm:updateProduction?id='.$eFarm['id'].'" class="btn btn-secondary" target="_blank">'.s("Configurer les dimensions").'</a>';
			$h .= '</div>';
		}

		$form = new \util\FormUi();

		$h .= $form->openAjax('/map/bed:doCreate', ['id' => 'bed-create', 'autocomplete' => 'off']);

			$h .= $form->group(
				s("Emplacement"),
				$form->fake(encode($ePlot['zoneFill'] ? $ePlot['zone']['name'] : $ePlot['name']))
			);

			$beds = '<div class="input-group">';
				$beds .= $form->number('number', attributes: ['id' => 'bed-create-number']);
				$beds .= $form->button(s("Valider"), ['id' => 'bed-create-button']);
			$beds .= '</div>';

			$h .= $form->group(
				s("Nombre de planches à ajouter"),
				$beds
			);

			$h .= $form->hidden('plot', $ePlot);
			$h .= $form->hidden('season', $season);

			$h .= '<div id="bed-create-form" data-number="0">';

			$numbering = '<div id="bed-create-customize-label" class="mb-1">';
				$numbering .= '<a onclick="Bed.startCustomizeNumbering()" class="btn btn-outline-primary">'.\Asset::icon('plus').' '.s("Personnaliser la numérotation").'</a>';
			$numbering .= '</div>';
			$numbering .= '<div id="bed-create-customize" class="hide mb-1">';
					$numbering .= '<h5>'.s("Renuméroter les planches").'</h5>';
					$numbering .= '<div class="bed-create-fill">';
						$numbering .= '<div class="input-group input-group-sm">';
							$numbering .= '<span class="input-group-addon">'.s("Préfixe").'</span>';
							$numbering .= $form->text(attributes: ['id' => 'bed-create-prefix', 'placeholder' => s("XXX-")]);
						$numbering .= '</div>';
						$numbering .= '<div class="input-group input-group-sm">';
							$numbering .= '<span class="input-group-addon">'.s("Début de numérotation").'</span>';
							$numbering .= $form->number(value: 1, attributes: ['id' => 'bed-create-start', 'onfocus' => 'this.select()']);
						$numbering .= '</div>';
						$numbering .= '<div class="input-group input-group-sm">';
							$numbering .= '<span class="input-group-addon">'.s("Suffixe").'</span>';
							$numbering .= $form->text(attributes: ['id' => 'bed-create-suffix', 'placeholder' => s("-XXX")]);
						$numbering .= '</div>';
						$numbering .= '<div>';
							$numbering .= $form->button(s("Appliquer"), ['id' => 'bed-create-auto']);
						$numbering .= '</div>';
					$numbering .= '</div>';
				$numbering .= '</div>';

				$names = '<div class="util-block bg-background">';

					$names .= $numbering;
					$names .= '<div id="bed-create-names" data-input="'.encode('<div class="bed-create-one"><h5>'.s("Planche").'</h5>'.$form->text('names[]', '', ['placeholder' => s("Donnez un nom")]).'</div>').'"></div>';

				$names .= '</div>';

				$h .= $form->group(
					s("Numérotation des planches"),
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

					$h .= $form->dynamicGroup($eBed, 'greenhouse', function(\PropertyDescriber $d) use($cGreenhouse) {
						$d->values = $cGreenhouse;
					});

				}

				$h .= $form->group(
					content: $form->submit(s("Ajouter"), ['id' => 'bed-create-submit', 'disabled' => TRUE])
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

				$h .= $form->dynamicGroup($eBed, 'plot', function(\PropertyDescriber $d) use($cPlot) {

					$d->values = $cPlot->makeArray(function(Plot $ePlot, ?int &$key) {
						$key = $ePlot['id'];
						if($ePlot['zoneFill']) {
							return s("Parcelle");
						} else {
							return s("Jardin {value}", $ePlot['name']);
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
					new SeasonUi()->getField($form, $eBed),
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

		$h .= new MapboxUi()->getDrawingBedLine($container, $form);

		$h .= '<script>';
			$h .= 'document.ready(() => setTimeout(() => {
				new Cartography("'.$container.'", '.$season.', false, true)';

					$h .= '.addZone('.$eZone['id'].', "'.addcslashes($eZone['name'], '"').'", '.json_encode($eZone['coordinates']).')';

					foreach($eZone['cPlot'] as $ePlotZone) {

						if($ePlotZone['zoneFill'] === FALSE) {

							$h .= '.addPlot('.$ePlotZone['id'].', "'.addcslashes($ePlotZone['name'], '"').'", '.$eZone['id'].', '.json_encode($ePlotZone['coordinates']).')';

						}

						if($ePlot['id'] === $ePlotZone['id']) {
							$h .= new MapUi()->addBeds($ePlotZone, $season, 0.33);
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
				new SeasonUi()->getField($form, $eBedField),
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
						'label' => s("Jardin {value}", $ePlot['name']),
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

			$h .= '<div class="util-block-help">';
				$h .= '<h3>'.s("Échanger les séries entre deux planches").'</h3>';
				$h .= '<p>'.s("Vous pouvez choisissez une deuxième planche avec laquelle toutes les séries de la saison {value} de la première planche seront échangées.", $season).'</p>';
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
			id: 'panel-bed-swap',
			title: s("Échanger les séries"),
			body: $h
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
				$form->fake($ePlot['zone']['name'])
			);

		} else {

			$h .= $form->group(
				s("Jardin"),
				$form->fake($ePlot['name'])
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
			'plot' => s("Jardin"),
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
						'seasonFirst' => s("la création du jardin"),
						'seasonLast' => s("la disparition du jardin")
					][$property];

					return new SeasonUi()->getDescriberField($form, $e, $e['farm'], $e['zone'], $e['plot'], $property, $placeholder);
				};
				break;

		}

		return $d;

	}

}
?>
