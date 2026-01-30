<?php
namespace series;

class SeriesUi {

	public function __construct() {

		\Asset::css('series', 'series.css');
		\Asset::js('series', 'series.js');

	}

	public static function name(Series $eSeries): string {

		$eSeries->expects(['id', 'name', 'mode']);

		$name = encode($eSeries['name']);

		if($eSeries['mode'] === Series::GREENHOUSE) {
			$name .= \Asset::icon('greenhouse', ['style' => 'margin-left: 0.5rem']);
		} else if($eSeries['mode'] === Series::MIX) {
			$name .= \Asset::icon('mix', ['style' => 'margin-left: 0.5rem']);
		}

		return $name;

	}

	public static function link(Series $eSeries, bool $newTab = FALSE): string {

		$eSeries->expects(['id', 'name']);

		return '<a href="'.self::url($eSeries).'" '.($newTab ? 'target="_blank"' : '').'>'.self::name($eSeries).'</a>';

	}

	public static function url(Series $eSeries): string {

		$eSeries->expects(['id']);

		return '/serie/'.$eSeries['id'];

	}

	public static function getPanelHeader(Series $eSeries): string {

		$eSeries->expects(['name', 'season']);

		$h = '<div>';
			$h .= '<div class="util-badge bg-secondary" style="margin-right: 0.5rem">'.s("Saison {value}", $eSeries['season']).'</div>';
			$h .= s("Série {name}", ['name' => self::link($eSeries)]);
		$h .= '</div>';

		return $h;

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
							$h .= '<a href="/series/series:createSequence?id='.$eSeries['id'].'" class="dropdown-item">'.s("Créer un itinéraire technique").'</a>';
							$h .= '<div class="dropdown-divider"></div>';
						}
						$h .= '<a data-ajax="/series/series:doDelete" post-id="'.$eSeries['id'].'" data-confirm="'.s("Souhaitez-vous réellement supprimer cette série de votre plan de culture ?").'" class="dropdown-item">'.s("Supprimer la série").'</a>';
					$h .= '</div>';

				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getSelector(\Collection $ccCultivation): string {

		$h = '<div id="series-selector" class="hide">';
			$h .= '<div class="util-title">';
					$h .= '<h3 class="flex-align-center">';
						$h .= '<span>'.s("Séries").'</span>';
						$h .= new \util\FormUi()->select(
							NULL,
							[
								'all' => s("Toutes"),
								'zero' => s("Non assolées"),
								'gap' => s("Partiellement assolées"),
							],
							attributes: [
								'id' => 'series-selector-filter',
								'onchange' => 'SeriesSelector.filter()',
								'mandatory' => TRUE,
								'style' => 'letter-spacing: -0.3px'
							]);
					$h .= '</h3>';
				$h .= '<a onclick="SeriesSelector.close()" class="btn btn-lg">'.\Asset::icon('x-lg').'</a>';
			$h .= '</div>';
			$h .= $this->getSelectorSeries($ccCultivation);
		$h .= '</div>';

		return $h;

	}

	public function getSelectorSeries(\Collection $ccCultivation) {

		\Asset::js('series', 'seriesSelector.js');

		$h = '<div id="series-selector-list">';

			foreach($ccCultivation as $cCultivation) {

				$ePlant = $cCultivation->first()['plant'];

				$h .= '<div class="series-selector-plant">';

					$h .= '<h4>';
						$h .= \plant\PlantUi::getVignette($ePlant, '1.5rem').' ';
						$h .= encode($ePlant['name']);
					$h .= '</h4>';
					$h .= '<div class="series-selector-cultivations">';

						foreach($cCultivation as $eCultivation) {

							$h .= $this->getSelectorCultivation($eCultivation);

						}

					$h .= '</div>';

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getSelectorCultivation(Cultivation $eCultivation): string {

		$eSeries = $eCultivation['series'];

		$startTs = $eSeries->getBedStart() ? strtotime($eSeries->getBedStart().' 00:00:00') : NULL;
		$stopTs = $eSeries->getBedStop() ? strtotime($eSeries->getBedStop().' 23:59:59') : NULL;

		$h = '<div id="series-selector-'.$eCultivation['id'].'" class="series-selector-cultivation '.($eSeries['status'] === Series::CLOSED ? 'series-selector-closed' : '').'" data-series="'.$eSeries['id'].'" data-cultivation="'.$eCultivation['id'].'" data-start="'.$startTs.'" data-stop="'.$stopTs.'" data-status="'.$eSeries['status'].'">';
			$h .= '<div class="series-selector-header" onclick="SeriesSelector.select('.$eCultivation['id'].')">';
				$h .= '<a href="'.SeriesUi::url($eSeries).'" target="_blank">';
					$h .= '<span class="series-selector-name">'.SeriesUi::name($eSeries).'</span>';
					$h .= \sequence\CropUi::start($eCultivation, \farm\FarmSetting::$mainActions);
					if($eSeries['status'] === Series::CLOSED) {
						$h .= ' '.\Asset::icon('lock-fill');
					}
				$h .= '</a>';

				if($eSeries['use'] === Series::BED) {

					$value = ($eSeries['length'] ?? 0);
					$target = $eSeries['lengthTarget'];
					$unit = s("mL");

				} else {

					$value = ($eSeries['area'] ?? 0);
					$target = $eSeries['areaTarget'];
					$unit = s("m²");

				}

				$color = '';

				if($target !== NULL) {

					if($value === 0) {
						$color = 'zero';
					} else if($value !== $target) {
						$color = 'gap';
					}

				}

				$h .= '<span class="series-selector-place" data-color="'.$color.'" data-value-target="'.$target.'">';
					$h .= '<span class="series-selector-value">'.$value.'</span>';
					if($target !== NULL) {
						$h .= '<span class="series-selector-target"> / '.$target.'</span>';
					} else {
						$target = NULL;
					}
					$h .= ' '.$unit;
				$h .= '</span>';
			$h .= '</div>';
			if(
				$eSeries['status'] !== Series::CLOSED and
				$eCultivation->canWrite()
			) {
				$h .= '<div class="series-selector-more bed-write">';
					$h .= new \util\FormUi()->submit(s("Enregistrer l'assolement"), ['class' => 'btn btn-lg btn-secondary', 'style' => 'height: 4rem']);
					$h .= '<a onclick="SeriesSelector.deselect()" class="btn btn-outline-secondary">'.s("Annuler").'</a> ';
				$h .= '</div>';
				$h .= '<div class="series-selector-more bed-read">';
					$h .= '<a onclick="SeriesSelector.edit(this)" data-cultivation="'.$eCultivation['id'].'" data-ajax-method="get" class="btn btn-outline-secondary">';
						$h .= ($value > 0) ? s("Modifier l'assolement") : s("Choisir l'assolement");
					$h .= '</a> ';
					if($value > 0) {
						$h .= '<a data-ajax="/series/place:doUpdate" post-cultivation="'.$eCultivation['id'].'" class="btn btn-secondary" data-confirm="'.s("Cette série ne sera plus assolée. Continuer ?").'">'.s("Supprimer l'assolement").'</a> ';
					}
				$h .= '</div>';
			}
		$h .= '</div>';

		return $h;

	}

	public function getComment(Series $eSeries): string {

		$h = '<div id="series-comment" class="util-block">';

		if($eSeries['comment'] !== NULL) {

			$h .= '<div class="series-comment-title">';
				$h .= '<h4>'.s("Notes").'</h4>';
				$h .= '<div>';
					$h .= '<a data-ajax="/series/series:updateComment" post-id="'.$eSeries['id'].'">'.\Asset::icon('pencil-fill').'</a>';
				$h .= '</div>';
			$h .= '</div>';

			$h .= new \editor\EditorUi()->value($eSeries['comment']);

		}

		$h .= '</div>';

		return $h;

	}

	public function getCommentField(Series $eSeries): string {

		$form = new \util\FormUi();

		$h = '<div id="series-comment" class="util-block">';

			$h .= '<h4>'.s("Notes").'</h4>';

			$h .= $form->openAjax('/series/series:doUpdateComment');

				$h .= $form->hidden('id', $eSeries['id']);

				$h .= $form->dynamicField($eSeries, 'comment');

				$h .= '<div class="series-comment-submit">';
					$h .= $form->submit(s("Valider"));
					$h .= $form->button(s("Annuler"), ['class' => 'btn', 'data-ajax' => '/series/series:restoreComment', 'post-id' => $eSeries['id']]);
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function updatePlace(Series $eSeries, \Collection $cPlace): string {

		$h = '<div class="crop-item" id="series-soil">';

			$h .= '<div class="crop-item-header">';
				$h .= '<div class="crop-item-title">';
					$h .= \plant\PlantUi::getSoilVignette('3rem');
					$h .= '<h2 class="series-soil-title">';
						$h .= s("Assolement");
					$h .= '</h2>';
					if(
						$eSeries->canWrite() and
						$eSeries['status'] === Series::OPEN and
						$cPlace->notEmpty()
					) {
						$h .= '<div>';
							$h .= '<a href="/series/place:update?series='.$eSeries['id'].($eSeries['mode'] === Series::GREENHOUSE ? '&mode='.Series::GREENHOUSE : '').'" class="btn btn-color-primary">'.\Asset::icon('gear-fill').'</a>';
						$h .= '</div>';
					}
				$h .= '</div>';
				$h .= '<div class="crop-item-soil-infos">';
				$h .= [
					Series::BED => s("Planches {value} cm", $eSeries['bedWidth']),
					Series::BLOCK => s("Surface libre"),
				][$eSeries['use']];

				if($eSeries['alleyWidth'] !== NULL) {
					$h .= ' / '.s("Passe-pieds {value} cm", $eSeries['alleyWidth']);
				}

				switch($eSeries['use']) {

					case Series::BED;
						if($eSeries['lengthTarget']) {
							$h .= ' / '.$eSeries->quick('lengthTarget', s("Objectif de {lengthTarget} mL", $eSeries));
						}
						if($eSeries['length']) {
							$h .= ' / '.s("Actuellement {length} mL", $eSeries);
						}
						break;

					case Series::BLOCK;
						if($eSeries['areaTarget']) {
							$h .= ' / '.$eSeries->quick('areaTarget', s("Objectif de {areaTarget} m²", $eSeries));
						}
						if($eSeries['area']) {
							$h .= ' / '.s("Actuellement {area} m²", $eSeries);
						}
						break;

				}

				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="crop-item-body">';
				if($cPlace->empty()) {

					if(
						$eSeries->canWrite() and
						$eSeries['status'] === Series::OPEN
					) {
						$h .= '<div class="series-soil-empty">';
							$h .= '<a href="/series/place:update?series='.$eSeries['id'].($eSeries['mode'] === Series::GREENHOUSE ? '&mode='.Series::GREENHOUSE : '').'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Définir l'assolement pour cette série").'</a>';
						$h .= '</div>';
					} else {
						$h .= '<div class="series-soil-empty">';
							$h .= s("L'assolement n'a pas été défini.");
						$h .= '</div>';
					}

				} else {
					$h .= $this->getPlace('series', $eSeries, $cPlace);
				}
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function displayImport(\farm\Farm $eFarm, int $nSeries, \Collection $cSeriesPerennial, bool $firstSeries, int $currentSeason): string {

		if($eFarm->canManage() === FALSE) {
			return '';
		}

		$previousSeason = $currentSeason - 1;

		$h = '';

		if($nSeries === 0) {

			if($firstSeries) {

				$h .= '<br/>';
				$h .= '<h2>'.s("Ajouter une première série dans le plan de culture").'</h2>';

				$h .= '<p class="util-block-help">';
					$h .= s("Vous êtes sur la page qui permet de créer des séries pour votre plan de culture. Vous êtes sur le point de créer une première série pour la saison {value}. Si vous souhaitez créer une série pour une autre année, utilisez le menu déroulant ci-dessus pour changer de saison.", $currentSeason);
				$h .= '</p>';

				$h .= '<div class="util-block-gradient">';
					$h .= $this->createFrom($eFarm, $currentSeason)->body;
				$h .= '</div>';

			} else {

				$h .= '<div class="util-block-help">';
					$h .= '<h4>'.s("Vous n'avez pas encore créé de série sur la saison {current} !", ['current' => $currentSeason]).'</h4>';
					$h .= '<p>'.s("Pour démarrer, vous pouvez <link>créer une nouvelle série</link> dès maintenant pour cette saison.", ['link' => '<a data-get="/series/series:createFrom?farm='.$eFarm['id'].'&season='.$currentSeason.'" data-ajax-class="Ajax.Query">']).'</p>';

					if($eFarm['seasonFirst'] < $currentSeason) {
						$h .= '<p>'.s("Vous pouvez également créer facilement votre plan de culture {current} en dupliquant les séries de productions annuelles qui ont bien fonctionné lors des saisons précédentes. Pour cela, retournez simplement sur la planification de la saison de votre choix, puis cochez vos séries préférées et enfin dupliquez-les !", ['current' => $currentSeason]).'</p>';
						if($previousSeason === $eFarm['seasonFirst']) {
							$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::AREA, season: $previousSeason).'" class="btn btn-secondary">'.s("Revenir sur la planification {previous}",  ['previous' => $previousSeason]).'</a>';
						} else {
							$h .= '<a data-dropdown="bottom-start" class="dropdown-toggle btn btn-secondary">'.s("Revenir sur la planification d'une autre saison").'</a>';
							$h .= '<div class="dropdown-list">';
								for($season = $currentSeason - 1, $count = 1; $season >= $eFarm['seasonFirst'], $count <= 3; $season--, $count++) {
									$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::AREA, season: $season).'" class="btn btn-secondary">'.s("Saison {value}", $season).'</a>';
								}
							$h .= '</div>';
						}
					}
				$h .= '</div>';

			}

		}

		if($cSeriesPerennial->notEmpty()) {

			$h .= '<div class="util-block">';

				$h .= '<h3>'.s("Que voulez-vous faire des productions pérennes de la saison {previous} ?", ['previous' => $previousSeason]).'</h3>';

				if($cSeriesPerennial->notEmpty()) {

					$h .= '<div class="util-overflow-md">';
						$h .= '<table class="tr-even">';

							$h .= '<thead>';
								$h .= '<tr>';
									$h .= '<th colspan="2">'.s("Série").'</th>';
									$h .= '<th>'.s("Démarrée").'</th>';
									$h .= '<th colspan="2">'.s("Actions").'</th>';
								$h .= '</tr>';
							$h .= '</thead>';
							$h .= '<tbody>';

								foreach($cSeriesPerennial as $eSeries) {

									$h .= '<tr>';

										$h .= '<td>'.SeriesUi::link($eSeries).'</td>';
										$h .= '<td>';
											$h .= '<div class="series-import-cultivation">';

												foreach($eSeries['cCultivation'] as $eCultivation) {

													$h .= '<div>';
														$h .= \plant\PlantUi::getVignette($eCultivation['plant'], '2rem').' '.encode($eCultivation['plant']['name']);
													$h .= '</div>';

												}

											$h .= '</div>';
										$h .= '</td>';
										$h .= '<td>';
											$h .= s("Saison {value}", $eSeries['season'] - $eSeries['perennialSeason'] + 1);
											$h .= ' &bull; ';
											$h .= s("{value} saison", \util\TextUi::th($eSeries['perennialSeason'] + 1));
										$h .= '</td>';
										$h .= '<td class="td-min-content">';
											$h .= '<a data-ajax="/series/series:perennialContinued" post-id="'.$eSeries['id'].'" class="btn btn-success">'.s("Continuer cette saison").'</a>';
										$h .= '</td>';
										$h .= '<td>';
											$h .= '<a data-ajax="/series/series:perennialFinished" post-id="'.$eSeries['id'].'" class="btn btn-danger" data-confirm="'.s("Confirmez-vous que cette série ne sera pas cultivée pour la saison {value} ?", $eSeries['season'] + 1).'">'.s("Arrêter").'</a>';
										$h .= '</td>';

									$h .= '</tr>';

								}

							$h .= '</tbody>';

						$h .= '</table>';
					$h .= '</div>';

				}

			$h .= '</div>';

		}

		return $h;

	}

	public function getPlace(string $source, Series|Task $e, \Collection $cPlace): string {

		$use = ($source === 'task') ? Series::BED : $e['use'];

		$h = '<div class="series-soil-grid series-soil-grid-'.$source.'">';

			$h .= '<div class="util-grid-header">'.s("Parcelle").'</div>';
			$h .= '<div class="util-grid-header">'.s("Jardin").'</div>';
			$h .= '<div class="util-grid-header">';
				if($use === Series::BED) {
					$h .= s("Planche");
				}
			$h .= '</div>';
			if($source === 'series') {
				$h .= '<div class="util-grid-header text-end">'.s("Utilisation").'</div>';
				$h .= '<div class="util-grid-header text-end" style="grid-column: span 2">'.s("Surface").'</div>';
			}
			$h .= '<div class="util-grid-header">'.\Asset::icon('greenhouse').'</div>';

			foreach($cPlace as $ePlace) {

				$h .= '<div class="series-soil-grid-zone">'.encode($ePlace['zone']['name']).'</div>';
				$h .= '<div class="series-soil-grid-plot">'.encode($ePlace['plot']['name']).'</div>';
				$h .= '<div class="series-soil-grid-bed">';
					if($use === Series::BED) {

						if($ePlace['bed']['name'] !== NULL) {

							$h .= encode($ePlace['bed']['name']);

							if($ePlace['bed']['status'] === \map\Bed::DELETED) {
								$h .= '<span class="ml-1 color-danger">'.\Asset::icon('exclamation-triangle').' '.s("Planche supprimée").'</span>';
							}

						} else {
							$h .= s("Temporaire");
						}

						$h .= ' '.$ePlace['bed']->getGreenhouseIcon();

					}
				$h .= '</div>';

				if($source === 'series') {

					$h .= '<div class="series-soil-grid-use">';
						$h .= match($use) {
							Series::BED => ($ePlace['bed']['length'] !== NULL) ? s("{value} %", round($ePlace['length'] / $ePlace['bed']['length'] * 100)) : '',
							Series::BLOCK => s("{value} %", round($ePlace['area'] / $ePlace['bed']['area'] * 100))
						};
					$h .= '</div>';
					$h .= '<div class="series-soil-grid-area">';

						if($use === Series::BED) {
							$h .= '<b class="util-unit">'.s("{length} mL x {width} cm", $ePlace).'</b>';
						}

					$h .= '</div>';
					$h .= '<div class="series-soil-grid-size util-unit">';
						$h .= match($use) {
							Series::BED => s("({value} m²)", round($ePlace['area'])),
							Series::BLOCK => '<b>'.s("({value} m²)", round($ePlace['area'])).'</b>'
						};
					$h .= '</div>';

				}

				$h .= '<div class="series-soil-grid-greenhouse">';

					if($ePlace['bed']['greenhouse']->empty() or $ePlace['bed']['name'] === NULL) {
						$h .= '-';
					} else {
						$h .= encode($ePlace['bed']['greenhouse']['name']);
					}

				$h .= '</div>';

			}

			if(
				$source === 'series' and
				$cPlace->count() > 1
			) {

				$h .= '<div style="grid-column: span 4"></div>';

					if($use === Series::BED) {
						$h .= '<div class="series-soil-grid-area series-soil-grid-total">';
							$h .= '<b class="util-unit">'.s("{length} mL", $e).'</b>';
						$h .= '</div>';
					} else {
						$h .= '<div></div>';
					}

				$h .= '<div class="series-soil-grid-size util-unit series-soil-grid-total">';
					$h .= match($use) {
						Series::BED => s("({value} m²)", round($e['area'])),
						Series::BLOCK => '<b>'.s("({value} m²)", round($e['area'])).'</b>'
					};
				$h .= '</div>';

				switch($use) {

					case Series::BED;
						if($e['lengthTarget']) {
							$h .= '<div class="series-soil-grid-total">';
								$h .= s("{value} de l'objectif", \util\TextUi::pc($e['length'] / $e['lengthTarget'] * 100));
							$h .= '</div>';
						} else {
							$h .= '<div></div>';
						}
						break;

					case Series::BLOCK;
						if($e['areaTarget']) {
							$h .= '<div class="series-soil-grid-total">';
								$h .= s("{value} de l'objectif", \util\TextUi::pc($e['area'] / $e['areaTarget'] * 100));
							$h .= '</div>';
						} else {
							$h .= '<div></div>';
						}
						break;

				}

			}

		$h .= '</div>';

		return $h;

	}

	public function createFrom(\farm\Farm $eFarm, int $season): \Panel {

		$form = new \util\FormUi();

		$eCultivation = new Cultivation([
			'farm' => $eFarm
		]);

		$eSeries = new Series([
			'farm' => $eFarm,
			'season' => $season
		]);

		$h = '<div id="series-create-from">';

			$h .= $form->openAjax('/series/series:createFromPlant', ['method' => 'get', 'data-ajax-class' => 'Ajax.Query']);

				$h .= $form->hidden('farm', $eFarm['id']);

				$h .= $form->dynamicGroup($eSeries, 'season', function(\PropertyDescriber $d) use($eFarm, $season) {

					$d->label = s("Pour la saison");

					if($season < date('Y')) {

						if(date('m') >= \farm\FarmSetting::NEW_SEASON) {
							$nextSeason = s("{value1} ou {value2}", ['value1' => date('Y'), 'value2' => nextYear()]);
						} else {
							$nextSeason = date('Y');
						}

						$d->after = '<div class="util-danger mt-1">'.s("Vous vous apprêtez à créer une série pour une saison déjà passée. Vous pouvez corriger votre choix si vous souhaitez créer une série pour la saison {value}.", $nextSeason).'</div>';

					} else if($season === (int)date('Y') and date('m') >= \farm\FarmSetting::NEW_SEASON) {

						$nextSeason = $season + 1;

						$after = s("Vous vous apprêtez à créer une série pour la saison en cours alors que l'année est presque terminée.").' ';

						if($nextSeason > $eFarm['seasonLast']) {

							$after .= s("Vous pouvez créer la saison {value} dès maintenant pour ajouter des séries sur la saison à venir.", $nextSeason);

							$after .= '<br/><a data-ajax="/farm/farm:doSeasonLast" post-id="'.$eFarm['id'].'" post-increment="1" class="btn btn-warning mt-1">';
								$after .= s("Ajouter la saison {year}", ['year' => $nextSeason]);
							$after .= '</a> ';

						} else {
							$after .= s("Corrigez votre choix si vous souhaitez créer une série pour la saison {value}.", $nextSeason);
						}

						$d->after = '<div class="util-warning mt-1">'.$after.'</div>';

					}

					$d->attributes['onchange'] = 'Series.selectCreateSeason(this)';

				});
				$h .= $form->group(content: '<h3 class="mb-0 mt-1">'.s("Créer la série").'</h3>');

				$h .= $form->group(
					s("À partir d'une espèce"),
					$form->dynamicField($eCultivation, 'plant', function($d) use($eFarm) {
						$d->autocompleteDispatch = '#series-create-from-plant';
						$d->attributes = [
							'data-autocomplete-select' => 'submit'
						];
					})
				);

			$h .= $form->close();

			$h .= $form->openAjax('/series/series:createFromSequence', ['method' => 'get', 'data-ajax-class' => 'Ajax.Query']);

				$h .= $form->hidden('farm', $eFarm['id']);
				$h .= $form->hidden('season', $season);
				$h .= $form->group(
					s("À partir d'un itinéraire technique"),
					$form->dynamicField($eCultivation, 'sequence', function($d) use($eFarm) {
						$d->autocompleteBody = ['farm' => $eFarm['id']];
						$d->attributes = [
							'data-autocomplete-select' => 'submit'
						];
					})
				);

			$h .= $form->close();

		$h .= '</div>';

		return new \Panel(
			id: 'panel-series-create',
			title: s("Ajouter une série"),
			body: $h,
			attributes: ['class' => 'panel-series-create']
		);

	}

	public function createFromPlant(\farm\Farm $eFarm, int $season, Series $eSeries, Cultivation $eCultivation, \plant\Plant $ePlant, \Collection $cAction): \Panel {

		$form = new \util\FormUi([
			'firstColumnSize' => 40
		]);

		$index = 0;

		$h = $form->openAjax('/series/series:doCreate?season='.$season.'&farm='.$eFarm['id'], ['id' => 'series-create-plant', 'data-cycle' => $eSeries['cycle']]);

		$h .= $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('index', $index);

		$h .= '<div class="series-create-use">';

			$h .= $this->getSeasonField($form, $eSeries);
			$h .= $this->getNameField($form, $eSeries);

			$h .= $form->dynamicGroups($eSeries, ['cycle', 'perennialLifetime', 'mode', 'use']);
			$h .= $this->getBlockFields($form, $eSeries);
		$h .= '</div>';

		$h .= '<div id="series-create-plant-list">';
			$h .= $this->addFromPlant($eSeries, $eCultivation, $ePlant, $index, $cAction, $form);
		$h .= '</div>';

		$h .= '<div id="series-create-add-plant" class="util-block-gradient">';
			$h .= $form->group(
				s("Ajouter une autre production").
				'<div class="util-helper">'.s("Ajoutez une autre production à cette série si vous souhaitez associer plusieurs cultures ensemble.").'</div>',
				$form->dynamicField($eCultivation, 'plant', function($d) {
					$d->placeholder = s("Ajouter une autre plante");
					$d->name = 'newPlant';
					$d->autocompleteDispatch = '#series-create-add-plant';
				})
			);
		$h .= '</div>';

		$h .= '<div class="series-submit">';

		$h .= $form->group(
			content: $form->submit(s("Créer la série"))
		);

		$h .= '</div>';

		$h .= $form->close();

		return new \Panel(
			id: 'panel-series-create',
			title: s("Ajouter une série"),
			documentTitle: s("Ajouter une série pour {name}", ['name' => $eFarm['name']]),
			body: $h,
			attributes: ['class' => 'panel-series-create']
		);

	}

	protected function getBlockFields(\util\FormUi $form, Series $eSeries): string {

		$h = $form->dynamicGroup($eSeries, 'areaTarget', function($d) use($eSeries) {
			$d->group = ($eSeries['use'] !== Series::BLOCK ? ['class' => 'hide'] : []);
		});

		$h .= $form->dynamicGroup($eSeries, 'lengthTarget', function($d) use($eSeries) {
			$d->group = ($eSeries['use'] !== Series::BED ? ['class' => 'hide'] : []);
		});
		$h .= $form->dynamicGroup($eSeries, 'bedWidth', function($d) use($eSeries) {
			$d->group = ($eSeries['use'] !== Series::BED ? ['class' => 'hide'] : []);
		});
		$h .= $form->dynamicGroup($eSeries, 'alleyWidth', function($d) use($eSeries) {
			$d->group = ($eSeries['use'] !== Series::BED ? ['class' => 'hide'] : []);
		});

		return $h;

	}

	public function addFromPlant(Series $eSeries, Cultivation $eCultivation, \plant\Plant $ePlant, int $index, \Collection $cAction, ?\util\FormUi $form = NULL): string {

		$eSeries->expects(['use', 'cycle', 'season']);

		if($form === NULL) {

			$form = new \util\FormUi([
				'firstColumnSize' => 50
			]);

		}

		$suffix = '['.$index.']';

		$h = '<div class="series-create-plant series-write-plant">';

			$h .= $form->hidden('plant'.$suffix, $ePlant['id']);

			$h .= '<div class="util-title">';

				$h .= '<div class="series-create-plant-title" data-plant-name="'.encode($ePlant['name']).'">';
					$h .= \plant\PlantUi::getVignette($ePlant, '3rem');
					$h .= '<h4>'.\plant\PlantUi::link($ePlant).'</h4>';
				$h .= '</div>';

				$h .= '<div class="series-create-plant-delete hide">';
					$h .= '<a onclick="Series.deletePlant(this)" class="btn btn-outline-primary">'.\Asset::icon('trash').'</a>';
				$h .= '</div>';

			$h .= '</div>';

			$h .= new CultivationUi()->getFieldsCreate($form, $eSeries['use'], $eCultivation, $cAction, $suffix);

		$h .= '</div>';

		return $h;

	}

	public function createFromSequence(\farm\Farm $eFarm, int $season, \sequence\Sequence $eSequence, \Collection $cCultivation, \Collection $cFlow, array $events): \Panel {

		$eSeries = $cCultivation->first()['series']->merge([
			'name' => $eSequence['name'],
			'nameDefault' => $eSequence['name'],
			'nameAuto' => TRUE
		]);

		$form = new \util\FormUi([
			'firstColumnSize' => 40
		]);

		$h = '';

		$h .= $form->openAjax('/series/series:doCreate?farm='.$eFarm['id'], ['id' => 'series-create-sequence']);

		$h .= $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('sequence', $eSequence['id']);

		$h .= '<p class="util-info">'.s("Cette série a été initialisée à partir des données de l'itinéraire technique {name}.", ['name' => \sequence\SequenceUi::link($eSequence)]).'</p>';

		$h .= '<div class="series-create-use">';

		$h .= $this->getSeasonField($form, $eSeries);
		$h .= $this->getNameField($form, $eSeries);

		$h .= $form->group(
			SeriesUi::p('cycle')->label,
			$form->fake(SeriesUi::p('cycle')->values[$eSequence['cycle']])
		);
		$h .= $form->dynamicGroup($eSequence, 'perennialLifetime');
		$h .= $form->dynamicGroup($eSequence, 'mode');
		$h .= $form->dynamicGroup($eSequence, 'use');

		$h .= $this->getBlockFields($form, $eSeries);

		$h .= '</div>';

		$h .= '<h3>'.p("Espèce", "Espèces", $cCultivation->count()).'</h3>';

		$h .= '<div id="series-create-plant-list">';

		$index = 0;
		$indexes = [];

		foreach($cCultivation as $eCultivation) {

			$ePlant = $eCultivation['plant'];
			$eCrop = $eCultivation['crop'];

			$indexes[$eCrop['id']] = $index;
			$suffix = '['.$index.']';

			$h .= $form->hidden('plant'.$suffix, $ePlant);
			$h .= $form->hidden('crop'.$suffix, $eCrop);

			$h .= '<div class="series-create-plant series-write-plant">';
				$h .= new CultivationUi()->getCropTitle($eFarm, $ePlant);
				$h .= new CultivationUi()->getFieldsCreate($form, $eSequence['use'], $eCultivation, new \Collection(), $suffix);
			$h .= '</div>';

			$index++;

		}

		$h .= '</div>';

		if($cFlow->notEmpty()) {

			$h .= '<h3>'.s("Rappel des interventions").'</h3>';

			if($eSequence['cycle'] === \sequence\Sequence::ANNUAL) {

				$eFlowFirst = $cFlow->first();
				$startYear = ($eFlowFirst['yearOnly'] ?? $eFlowFirst['yearStart']);
				$startWeek = ($eFlowFirst['weekOnly'] ?? $eFlowFirst['weekStart']);

				$h .= '<div id="series-create-tasks" data-farm="'.$eFarm['id'].'" data-season="'.$season.'" data-sequence="'.$eSequence['id'].'">';
					$h .= $this->getTasksFromSequence($season, $eSequence, $events, $startYear, $startWeek);
				$h .= '</div>';

			} else {
				$h .= new \sequence\FlowUi()->getTimeline($eSequence, $events, FALSE);
			}

		}

		$h .= '<div class="series-submit">';
			$h .= $form->submit(s("Créer la série"));
		$h .= '</div>';

		$h .= $form->close();


		return new \Panel(
			id: 'panel-series-create',
			title: s("Ajouter une série"),
			documentTitle: s("Ajouter une série pour {name}", ['name' => $eFarm['name']]),
			body: $h,
			attributes: ['class' => 'panel-series-create']
		);

	}

	public function getTasksFromSequence(int $season, \sequence\Sequence $eSequence, array $events, int $startYear, int $startWeek): string {

		$form = new \util\FormUi();

		$h = '';

		if($eSequence['cycle'] === \sequence\Sequence::ANNUAL) {

			$h .= '<div class="task-select-start">';

				$h .= $form->group(
					s("Année de démarrage"),
					$form->select('startYear', [0 => $season, -1 => $season - 1], $startYear, ['mandatory' => TRUE])
				);

				$weeks = [];
				for($week = 1; $week <= 52; $week++) {
					$weeks[$week] = s("Semaine {value} → {interval}", ['value' => $week, 'interval' => \util\DateUi::weekToDays($season + $startYear.'-W'.sprintf('%02d', $week), withYear: FALSE)]);
				}

				$h .= $form->group(
					s("Semaine de démarrage"),
					$form->select('startWeek', $weeks, $startWeek, ['mandatory' => TRUE])
				);

			$h .= '</div>';

		}

		$h .= new \sequence\FlowUi()->getTimeline($eSequence, $events, FALSE, $startYear + $season);

		return $h;

	}

	public function update(Series $eSeries): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/series/series:doUpdate', ['id' => 'series-update']);

			$h .= $form->hidden('id', $eSeries['id']);

			if($eSeries['cycle'] === Series::ANNUAL) {
				$h .= $form->dynamicGroup($eSeries, 'season');
			}

			$h .= $form->dynamicGroup($eSeries, 'name');

			if($eSeries['cSequence']->notEmpty()) {

				$h .= $form->dynamicGroup(new Cultivation([
					'sequence' => $eSeries['sequence']
				]), 'sequence', function($d) use($eSeries) {

					$body = [
						'farm' => $eSeries['farm']['id']
					];

					foreach($eSeries['cSequence'] as $position => $eSequence) {
						$body['ids['.$position.']'] = $eSequence['id'];
					}

					$d->autocompleteBody = $body;

				});

			}

			$h .= $form->dynamicGroup($eSeries, 'mode');

			$h .= $form->dynamicGroup($eSeries, 'use', function(\PropertyDescriber $d) use($eSeries) {

				$infos = [];

				if($eSeries['cPlace']->notEmpty()) {
					$infos[] = s("l'assolement de la série sera remis à zéro");
				}

				$reset = $eSeries['cCultivation']->reduce(fn($eCultivation, $n) => (
					(
						($eSeries['use'] === Series::BED and $eCultivation['rows'] !== NULL) or
						($eSeries['use'] === Series::BLOCK and $eCultivation['rowSpacing'] !== NULL)
					)
						? 1 : 0) + $n, 0);

				if($reset) {
					$infos[] = p("les informations concernant la densité de la culture seront réinitialisées et vous devrez les saisir à nouveau", "les informations concernant la densité des cultures seront réinitialisées et vous devrez les saisir à nouveau", $eSeries['plants']);
				}

				if($infos) {

					$text = s("Si vous changez l'utilisation du sol, {value}.", implode(', ', $infos));
					$d->after = \util\FormUi::info($text);

				}

				$d->attributes['callbackRadioAttributes'] = function() {
					return [
						'onclick' => 'Series.refreshUpdateUse(this)'
					];
				};

			});

			if($eSeries['cycle'] === Series::PERENNIAL and $eSeries['perennialStatus'] !== Series::CONTINUED) {
				$h .= $form->dynamicGroup($eSeries, 'perennialLifetime');
			}

			$h .= '<div class="series-update-block '.($eSeries['use'] === Series::BLOCK ? '' : 'hide').'">';
				$h .= $form->dynamicGroup($eSeries, 'areaTarget');
			$h .= '</div>';

			$h .= '<div class="series-update-bed '.($eSeries['use'] === Series::BED ? '' : 'hide').'">';
				$h .= $form->dynamicGroups($eSeries, ['lengthTarget', 'bedWidth', 'alleyWidth']);
			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-series-update',
			title: s("Modifier une série"),
			body: $h,
		);

	}

	public function updateSeason(\farm\Farm $eFarm, \Collection $cSeries): \Panel {

		$form = new \util\FormUi();

		$eSeriesFirst = $cSeries->first();
		$eSeriesFirst['farm'] = $eFarm; // Pour avoir la première et la dernière saison de la ferme

		$h = '<div class="util-info">';
			$h .= s("Lorsque vous changez une série de saison, les dates des interventions de la série ne sont pas modifiées, c'est à vous de les décaler dans le temps si vous le souhaitez.");
			if($cSeries->count() > 10) {
				$h .= ' <b>'.s("Vous allez agir sur un grand nombre de séries simultanément, soyez vigilant avant de valider votre modification !").'</b>';
			}
		$h .= '</div>';

		$h .= $form->openAjax('/series/series:doUpdateSeasonCollection');

			$h .= $this->getSeriesField($form, $cSeries);
			$h .= $form->dynamicGroup($eSeriesFirst, 'season', function($d) {
				$d->label = s("Nouvelle saison");
			});

			$h .= $form->group(
				content: $form->submit(s("Modifier la saison"), ['data-waiter', 'data-confirm' => p("Vous allez changer une série de saison, voulez-vous continuer ?", "Vous allez changer {value} séries de saison, voulez-vous continuer ?", $cSeries->count())])
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-series-update',
			title: s("Modifier la saison"),
			body: $h,
		);

	}

	public function createSequence(Series $eSeries, \Collection $cCultivation, \Collection $cTaskMetadata): \Panel {

		$form = new \util\FormUi();

		$eSequence = new \sequence\Sequence([
			'name' => $eSeries['name']
		]);

		$h = '<div class="util-block-help">';
			$h .= '<p>'.s("Vous vous apprêtez à créer un itinéraire technique à partir de la série {name}.", ['name' => '<b>'.encode($eSeries['name']).'</b>']).'</p>';
		$h .= '</div>';

		$h .= $form->openAjax('/series/series:doCreateSequence');
			$h .= $form->hidden('id', $eSeries['id']);
			$h .= $form->dynamicGroup($eSequence, 'name');
			
			$cultivations = '';

			foreach($cCultivation as $eCultivation) {

				$ePlant = $eCultivation['plant'];

				$cultivations .= '<div class="crop-item mb-1" style="border: 1px solid var(--border)">';
					$cultivations .= '<div class="crop-item-header">';

						$cultivations .= '<div class="crop-item-title">';
							$cultivations .= \plant\PlantUi::getVignette($ePlant, '3rem').' ';
							$cultivations .= '<h2>';
								$cultivations .= \plant\PlantUi::link($ePlant);
								$cultivations .= \sequence\CropUi::start($eCultivation, \farm\FarmSetting::$mainActions, fontSize: '0.7em');
							$cultivations .= '</h2>';
						$cultivations .= '</div>';

						$cultivations .= new \sequence\CropUi()->getVarieties($eCultivation, $eCultivation['cSlice']);

					$cultivations .= '</div>';

					$cultivations .= '<div class="crop-item-presentation">';
						$cultivations .= new CultivationUi()->getPresentation($eSeries, $eCultivation, withYields: FALSE);
					$cultivations .= '</div>';

				$cultivations .= '</div>';

			}

			$h .= $form->group(
				p("Production", "Productions", $cCultivation->count()),
				$cultivations
			);

			if($cTaskMetadata->notEmpty()) {

				$cAction = $cTaskMetadata
					->getColumnCollection('action')
					->sort('name');

				$h .= $form->group(
					s("Interventions à conserver dans l'itinéraire technique").\util\FormUi::info(s("Les interventions de semis, plantation et récolte sont toujours conservées.")),
					$form->checkboxes('actions[]', $cAction, $cAction)
				);

			}

			$h .= $form->group(
				content: $form->submit(s("Créer un itinéraire technique"), ['data-waiter' => s("Création en cours..."), 'data-confirm' => s("Vous allez créer un itinéraire technique à partir d'une série, voulez-vous continuer ?")])
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-series-create-sequence',
			title: s("Créer un itinéraire technique à partir d'une série"),
			body: $h,
		);

	}

	public function duplicate(\farm\Farm $eFarm, \Collection $cSeries, \Collection $cTaskMetadata, bool $hasPlaces): \Panel {

		$form = new \util\FormUi();

		$eSeriesFirst = $cSeries->first();
		$eSeriesFirst['farm'] = $eFarm; // Pour avoir la première et la dernière saison de la ferme

		$h = '';

		if($cSeries->count() > 5) {
			$h = '<div class="util-info">';
				$h .= s("Vous allez agir sur un grand nombre de séries simultanément, soyez vigilant avant de valider votre modification !");
			$h .= '</div>';
		}

		$h .= $form->openAjax('/series/series:doDuplicate', ['id' => 'series-duplicate', 'data-season' => $eSeriesFirst['season']]);

			$input = '<div class="series-duplicate-copies" data-limit="'.SeriesSetting::DUPLICATE_LIMIT.'">';
				$input .= '<a onclick="Series.changeDuplicateCopies(this, -1)" class="series-duplicate-copies-minus series-duplicate-copies-disabled">'.\Asset::icon('dash-circle').'</a>';
				$input .= '<span class="series-duplicate-copies-label">'.s("{value}", '<span class="series-duplicate-copies-value">1</span>').'</span>';
				$input .= '<a onclick="Series.changeDuplicateCopies(this, 1)" class="series-duplicate-copies-plus">'.\Asset::icon('plus-circle').'</a>';
				$input .= $form->hidden('copies', 1);
			$input .= '</div>';

			$h .= $form->group(
				$cSeries->count() === 1 ? s("Nombre de copies") : s("Nombre de copies par série"),
				$input
			);

			$h .= $form->dynamicGroup($eSeriesFirst, 'season', function(\PropertyDescriber $d) use($eSeriesFirst) {
				$d->label = s("Dupliquer pour la saison");
				$d->attributes['onclick'] = 'Series.changeDuplicateSeason(this)';
				$d->after = \util\FormUi::info(s("Lorsque vous dupliquez une série sur une saison différente, les interventions <i>Fait</i> sont replacées en <i>À faire</i> et les récoltes sont remises à zéro."), class: 'series-duplicate-season hide');
			});

			$h .= '<br/>';

			$h .= '<div class="series-duplicate-list" data-interval="0" data-copies="1">';

				$title = '<div class="util-title">';
					$title .= '<h3>'.($cSeries->count() === 1 ? s("Série") : s("Séries")).'</h3>';
					$title .= '<a onclick="Series.toggleDuplicateInterval(this)" class="btn btn-outline-primary">'.\Asset::icon('chevron-expand').' '.s("Décaler les interventions").'</a>';
				$title .= '</div>';

				$h .= $form->group(content: $title);

				foreach($cSeries as $eSeries) {

					$name = str_contains($eSeries['name'], '@copy') ? $eSeries['name'] : s("{value} (@copy)", $eSeries['name']);

					$h .= '<div class="series-duplicate-one util-block bg-background-light" data-name="'.encode($name).'" data-series="'.$eSeries['id'].'">';
						$h .= $form->hidden('ids[]', $eSeries['id']);
						$h .= $this->getCopyField($form, $eSeries);
					$h .= '</div>';

				}

			$h .= '</div>';


			if($cTaskMetadata->notEmpty() or $hasPlaces) {
				$h .= $form->group(content: '<h3>'.s("Contenu").'</h3>');
			}

			$h .= '<div class="util-block bg-background-light">';

				if($cTaskMetadata->notEmpty()) {

					$cAction = $cTaskMetadata
						->getColumnCollection('action')
						->sort('name');

					$h .= $form->group(
						s("Dupliquer les interventions"),
						$form->checkboxes('copyActions[]', $cAction, $cAction->find(fn($eAction) => $eAction['fqn'] !== ACTION_RECOLTE), attributes: [
							'callbackCheckboxAttributes' => fn($eAction) => [
								'data-fqn' => $eAction['fqn'],
							],
							'callbackCheckboxContent' => function($eAction) {
								$action = encode($eAction['name']);
								if($eAction['fqn'] === ACTION_RECOLTE) {
									$action .= '<span class="color-secondary ml-1">'.\Asset::icon('exclamation-circle').' '.s("Les quantités récoltées seront également dupliquées").'</span>';
								}
								return $action;
							}
						])
					);

					if($cTaskMetadata
						->filter(fn($eTask) => $eTask['time'] > 0)
						->notEmpty()) {

						$h .= $form->group(
							s("Dupliquer le temps de travail"),
							$form->yesNo('copyTimesheet', FALSE)
						);

					}

				}

				if($hasPlaces) {

					$h .= $form->group(
						s("Dupliquer l'assolement"),
						$form->yesNo('copyPlaces', FALSE)
					);

				} else {
					$h .= $form->hidden('copyPlaces', FALSE);
				}

			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Dupliquer"), ['data-waiter' => s("Duplication en cours..."), 'data-confirm' => p("Vous allez dupliquer une série, voulez-vous continuer ?", "Vous allez dupliquer {value} séries, assurez-vous d'avoir bien vérifié votre formulaire. En dupliquant un grand nombre de séries par erreur, vous risquez de perdre le fil de votre plan de culture. Lancer la duplication ?", $cSeries->count())])
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-series-duplicate',
			title: $cSeries->count() === 1 ? s("Dupliquer une série") : s("Dupliquer des séries"),
			body: $h,
		);

	}

	protected function getCopyField(\util\FormUi $form, Series $eSeries, int $copy = 1): string {

		$field = '<div data-wrapper="series-'.$eSeries['id'].'-'.$copy.'" class="series-duplicate-copy">';

			$field .= '<h5>'.s("Copie n°{value}", '<span class="series-duplicate-copy-number">'.$copy.'</span>').'</h5>';

			$field .= '<div>';
				$field .= $form->inputGroup(
					$form->addon(s("Nom de la copie")).
					$form->text('name['.$eSeries['id'].']['.($copy - 1).']', str_replace('@copy', 1, $eSeries['name']), ['data-copy' => $copy])
				);
			$field .= '</div>';


			$field .= '<div class="series-duplicate-interval">';
				$field .= $form->inputGroup(
					$form->addon(s("Décaler les interventions de")).
					$form->number('taskInterval['.$eSeries['id'].']['.($copy - 1).']', attributes: SeriesSetting::DUPLICATE_INTERVAL).
					$form->addon(s("semaine(s)"))
				);
			$field .= '</div>';

		$field .= '</div>';

		return $form->group(
			SeriesUi::link($eSeries),
			$field
		);

	}

	protected function getSeriesField(\util\FormUi $form, \Collection $cSeries): string {

		if($cSeries->count() === 1) {

			$eSeries = $cSeries->first();

			$group = $form->hidden('ids[]', $eSeries['id']);
			$group .= SeriesUi::link($eSeries);

		} else {

			$group = '<ul>';

			foreach($cSeries as $eSeries) {

				$group .= '<li>';
					$group .= $form->hidden('ids[]', $eSeries['id']);
					$group .= SeriesUi::link($eSeries);
				$group .= '</li>';

			}

			$group .= '</ul>';

		}

		return $form->group(
			$cSeries->count() === 1 ? s("Série") : s("Séries"),
			$group
		);

	}

	protected function getSeasonField(\util\FormUi $form, Series $eSeries): string {

		$h = $form->hidden('season', $eSeries['season']);
		$h .= $form->group(
			self::p('season')->label,
			$form->fake($eSeries['season'])
		);

		return $h;

	}

	protected function getNameField(\util\FormUi $form, Series $eSeries): string {

		$eSeries->expects(['nameAuto', 'nameDefault']);

		return $form->group(
			self::p('name')->label,
			$form->inputGroup(
				$form->dynamicField($eSeries, 'name', function($d) use($eSeries) {
					$d->attributes['data-auto'] = 'true';
					$d->attributes['oninput'] = 'Series.changeNameAuto(this)';
				})
			),
			['wrapper' => 'name']
		);

	}

	public function deleteSeason(\farm\Farm $eFarm, int $season): string {

		$eFarm->expects(['createdAt', 'seasonFirst', 'seasonLast']);

		if(
			$eFarm['seasonFirst'] === $eFarm['seasonLast'] or
			(
				// Impossible de supprimer des saisons de l'année N et N + 1 si ferme récente
				$eFarm['createdAt'] > date('Y-m-d H:i:s', strtotime('1 year ago')) and
				($season === currentYear() or $season === currentYear() + 1)
			)
		) {
			return '';
		}

		// Il n'est possible de supprimer que la première ou la dernière saison
		if($eFarm['seasonFirst'] === $season) {
			$link = 'data-ajax="/farm/farm:doSeasonFirst" post-id="'.$eFarm['id'].'" post-increment="1"';
		} else if($eFarm['seasonLast'] === $season) {
			$link = 'data-ajax="/farm/farm:doSeasonLast" post-id="'.$eFarm['id'].'" post-increment="-1"';
		} else {
			return '';
		}

		$h = '<div class="util-block">';

			$h .= '<h4>'.s("Supprimer cette saison").'</h4>';

			$h .= '<p>'.s("Vous n'avez pas encore ajouté de série sur cette saison. Il est toujours possible de supprimer cette saison {value} si vous ne comptez pas travailler dessus pour le moment et de la recréer plus tard !", $season).'</p>';
			$h .= '<a '.$link.' class="btn btn-danger">'.s("Supprimer la saison {value}", $season).'</a>';

		$h .= '</div>';

		return $h;

	}

	public function getWorkingTime(Series $eSeries, \Collection $cCultivation, \Collection $ccTask, \Collection $ccTaskHarvested): string {

		if($ccTask->empty()) {
			return '';
		}

		$seriesTime = 0;

		$h = '<div id="series-timesheet-wrapper">';

			$h .= '<h3>'.s("Temps de travail").'</h3>';

			$h .= '<div class="series-timesheet">';

			foreach($ccTask as $cultivation => $cTask) {

				if($cultivation) {
					$eCultivation = $cCultivation[$cultivation];
				} else {
					$eCultivation = new Cultivation();
				}

				$cTaskHarvested = $ccTaskHarvested[$cultivation] ?? new \Collection();

				$cultivationTime = $cTask->sum('totalTime');
				$seriesTime += $cultivationTime;

				$h .= '<div class="series-timesheet-image">';
					if($cultivation) {
						$h .= \plant\PlantUi::getVignette($eCultivation['plant'], '3rem').' ';
					}
				$h .= '</div>';

				$h .= '<div class="series-timesheet-cultivation">';
					if($cultivation) {
						$ePlant = $eCultivation['plant'];
						$h .= '<h4>';
							$h .= encode($ePlant['name']);
						$h .= '</h4>';
					} else {
						$h .= '<h4>'.s("Partagé").'</h4>';
					}
				$h .= '</div>';

				$h .= '<div class="series-timesheet-total">';
					$h .= TaskUi::convertTime($cultivationTime);
				$h .= '</div>';

				$h .= '<div class="series-timesheet-content">';

					foreach($cTask as $eTask) {

						$eAction = $eTask['action'];

						$h .= $this->getWorkingTimeBox($eSeries, $eCultivation, $eAction, $eTask['totalTime'], $cTaskHarvested);

					}

				$h .= '</div>';

			}

			if($ccTask->count() > 1) {

				$h .= '<div class="series-timesheet-sum" style="grid-column: span 2">';
					$h .= '<span class="btn btn-readonly btn-outline-secondary">'.TaskUi::convertTime($seriesTime).'</span>';
				$h .= '</div>';

				$h .= '<div>';
				$h .= '</div>';

			}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getWorkingTimeBox(Series $eSeries, Cultivation $eCultivation, \farm\Action $eAction, float $time, \Collection $cTaskHarvested): string {

		$h = '<div class="series-item-working-time-task">';
			$h .= '<div class="series-item-working-time-task-content" style="background-color: '.$eAction['color'].'">';
				$h .= '<h5>'.encode($eAction['name']).'</h5>';
				$h .= '<span class="series-item-working-time-task-value">'.TaskUi::convertTime($time).'</span>';
			$h .= '</div>';

			if($eAction['pace'] and $time > 0) {

				$h .= '<div class="series-item-working-time-task-pace" style="color: '.$eAction['color'].'">';
					$h .= $this->getPace($eSeries['area'], $eCultivation->empty() ? NULL : $eCultivation['density'] * $eSeries['area'], $eAction, $time, $cTaskHarvested);
				$h .= '</div>';

			}
		$h .= '</div>';

		return $h;

	}

	public function getPace(?int $area, ?float $plants, \farm\Action $eAction, float $time, \Collection $cTaskHarvested): string {

		if($area === NULL) {
			return '';
		}

		$pace = '';

		switch($eAction['pace']) {

			case \farm\Action::BY_HARVEST :
				foreach($cTaskHarvested as $eTaskHarvested) {
					if($eTaskHarvested['totalTime'] > 0 and $eTaskHarvested['totalHarvested'] > 0) {
						$pace .= s("{value} / h", \selling\UnitUi::getValue(round($eTaskHarvested['totalHarvested'] / $eTaskHarvested['totalTime'], 1), $eTaskHarvested['harvestUnit'], short: TRUE)).'<br/>';
					}
				}
				break;

			case \farm\Action::BY_AREA :
				$pace .= s("{value} m² / h", round($area / $time));
				break;

			case \farm\Action::BY_PLANT :
				$pace .= s("{value} plants / h", round($plants / $time));
				break;

		}

		return $pace;

	}

	public function getPhotos(Series $eSeries, \Collection $cPhoto): string {

		$h = '';

		$h .= '<div class="util-title">';
			$h .= '<h3 id="scroll-photos">'.s("Photos").'</h3>';
			if($eSeries->canWrite()) {
				$h .= '<div data-media="gallery" post-series="'.$eSeries['id'].'">';
					$h .= new \media\GalleryUi()->getDropdownLinks(
						\Asset::icon('plus-circle').' <span>'.s("Ajouter une photo").'</span>',
						'btn-outline-primary',
						uploadInputAttributes: ['multiple' => 'multiple']
					);
				$h .= '</div>';
			}
		$h .= '</div>';

		if($cPhoto->notEmpty()) {
			$h .= new \gallery\PhotoUi()->getList($cPhoto, NULL, 4);
		}

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Series::model()->describer($property, [
			'season' => s("Saison"),
			'sequence' => s("Itinéraire technique"),
			'name' => s("Nom de la série"),
			'use' => s("Utilisation du sol"),
			'mode' => s("Mode de culture"),
			'cycle' => s("Cycle de culture"),
			'areaTarget' => s("Objectif de surface"),
			'lengthTarget' => s("Objectif de surface"),
			'bedWidth' => s("Largeur travaillée de planche"),
			'alleyWidth' => s("Largeur de passe-pieds")
		]);

		switch($property) {

			case 'season' :
				$d->field = function(\util\FormUi $form, Series $e, string $property, \PropertyDescriber $d) {

					$e->expects([
						'farm' => ['seasonFirst', 'seasonLast']
					]);

					return $form->rangeSelect('season', $e['farm']['seasonLast'], $e['farm']['seasonFirst'], -1, $e['season'], ['mandatory' => TRUE] + $d->attributes);

				};
				break;

			case 'use' :
				$d->values = [
					Series::BED => s("Culture sur planches"),
					Series::BLOCK => s("Culture sur surface libre"),
				];
				$d->attributes = [
					'columns' => 2,
					'mandatory' => TRUE,
					'onchange' => 'Series.updateArea(this)'
				];
				break;

			case 'mode' :
				$d->values = [
					Series::GREENHOUSE => s("Sous abri"),
					Series::OPEN_FIELD => s("Plein champ"),
					Series::MIX => s("Mixte"),
				];
				$d->attributes = [
					'columns' => 3,
					'mandatory' => TRUE
				];
				break;

			case 'cycle' :
				$d->values = [
					Series::ANNUAL => s("Culture annuelle"),
					Series::PERENNIAL => s("Culture pérenne"),
				];
				$d->attributes = [
					'data-action' => 'series-cycle-change',
					'columns' => 2,
					'mandatory' => TRUE
				];
				break;

			case 'perennialLifetime' :
				$d->groupLabel = FALSE;
				$d->prepend = s("Durée de vie de la culture").'&nbsp;&nbsp;'.\Asset::icon('arrow-right');
				$d->append = s("saison(s)");
				$d->after = '<small>'.s("Vous pouvez laisser vide si la durée de vie n'est pas connue à ce jour.").'</small>';
				$d->group = function(Series $e) {

					$e->expects(['cycle']);

					return [
						'id' => 'series-write-perennial-lifetime',
						'style' => ($e['cycle'] === \sequence\Sequence::PERENNIAL) ? '' : 'display: none'
					];

				};
				break;

			case 'lengthTarget' :
				$d->append = s("mL de planches");
				$d->attributes['oninput'] = 'Series.updateArea(this)';
				break;

			case 'bedWidth' :
				$d->append = s("cm");
				$d->attributes['oninput'] = 'Series.updateArea(this)';
				break;

			case 'alleyWidth' :
				$d->labelAfter = \farm\FarmUi::getAlleyWarning();
				$d->append = s("cm");
				$d->attributes['oninput'] = 'Series.updateArea(this)';
				break;

			case 'areaTarget' :
				$d->append = s("m²");
				$d->attributes['oninput'] = 'Series.updateArea(this)';
				break;
		}

		return $d;

	}

}
?>
