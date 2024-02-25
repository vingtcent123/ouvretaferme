<?php
namespace production;

class SequenceUi {

	public function __construct() {
		\Asset::css('production', 'sequence.css');
		\Asset::js('production', 'sequence.js');
	}

	public static function link(Sequence $eSequence, bool $newTab = FALSE): string {

		$eSequence->expects(['id', 'name', 'status']);
		return '<a href="'.self::url($eSequence).'" '.($newTab ? 'target="_blank"' : '').' class="plant-link">'.self::name($eSequence).'</a>';

	}

	public function getDuplicateName(Sequence $eSequence): string {
		return $eSequence['name'].' '.s("(copie)");
	}

	public static function name(Sequence $eSequence): string {

		$eSequence->expects(['id', 'name', 'mode']);

		$name = encode($eSequence['name']);

		if($eSequence['mode'] === Sequence::GREENHOUSE) {
			$name .= \Asset::icon('greenhouse', ['style' => 'margin-left: 0.5rem']);
		} else if($eSequence['mode'] === Sequence::MIX) {
			$name .= \Asset::icon('mix', ['style' => 'margin-left: 0.5rem']);
		}

		return $name;

	}

	public static function url(Sequence $eSequence): string {

		$eSequence->expects(['id']);

		return '/itineraire/'.$eSequence['id'];

	}

	public function getSearch(\farm\Farm $eFarm, \Search $search, bool $emptySearch): string {

		$h = '<div id="sequence-search" class="util-block-search '.($emptySearch ? 'hide' : '').'">';

			$form = new \util\FormUi();
			$url = \farm\FarmUi::urlCultivationSequences($eFarm);

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);
				$h .= '<div>';
					$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom de l'itinéraire")]);
					$h .= $form->dynamicField(new \series\Cultivation(['farm' => $eFarm]), 'plant', function($d) use ($search) {
						$d->autocompleteDefault = $search->get('plant');
						$d->attributes = [
							'data-autocomplete-select' => 'submit',
							'style' => 'width: 20rem'
						];
					});
					$h .= $form->select('use', SequenceUi::p('use')->values, $search->get('use'), ['placeholder' => s("Utilisation du sol")]);

					$h .= $form->dynamicField(new \farm\Tool(['farm' => $eFarm]), 'id', function($d) use ($search) {

						$d->name = 'tool';

						if($search->get('tool')->notEmpty()) {
							$d->autocompleteDefault = $search->get('tool');
						}

						$d->attributes = [
							'data-autocomplete-select' => 'submit',
							'style' => 'width: 20rem'
						];
					});
				$h .= '</div>';
				$h .= '<div>';
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function getTabs(\farm\Farm $eFarm, \Search $search, array $sequences): string {

		$h = '';

		if($sequences[Sequence::CLOSED] > 0) {

			$h .= '<br/>';

			$h .= '<div class="tabs-item">';
				$h .= '<a href="'.\farm\FarmUi::urlCultivationSequences($eFarm).'" class="tab-item '.($search->get('status') === Sequence::ACTIVE ? 'selected' : '').'"><span>'.s("Itinéraires actifs").' <small>('.$sequences[Sequence::ACTIVE].')</small></span></a>';
				$h .= '<a href="'.\farm\FarmUi::urlCultivationSequences($eFarm).'/'.Sequence::CLOSED.'" class="tab-item '.($search->get('status') === Sequence::CLOSED ? 'selected' : '').'"><span>'.s("Itinéraires archivés").' <small>('.$sequences[Sequence::CLOSED].')</small></span></a>';
			$h .= '</div>';

		}

		return $h;

	}

	public function getListByPlants(\farm\Farm $eFarm, \Collection $ccCrop, \Collection $cActionMain, \Search $search = NULL) {

		return $this->getList($eFarm, $ccCrop, $cActionMain, $search, function(\Collection $ccCrop, \Closure $display) use ($search) {

			$h = '';

			foreach($ccCrop as $cCrop) {

				$ePlant = $cCrop->first()['plant'];

				$h .= '<tr class="crop-list-title-plant">';
					$h .= '<td colspan="'.($search?->get('status') === Sequence::ACTIVE ? 5 : 4).'">';
						$h .= \plant\PlantUi::getVignette($ePlant, '2rem').' ';
						$h .= encode($ePlant['name']);
					$h .= '</td>';
				$h .= '</tr>';

				foreach($cCrop as $eCrop) {
					$h .= $display($eCrop);
				}

			}

			return $h;

		});

	}

	public function getList(\farm\Farm $eFarm, \Collection $ccCrop, \Collection $cActionMain, \Search $search = NULL, ?\Closure $browse = NULL) {

		$display = function(Crop $eCrop) use ($eFarm, $cActionMain, $search) {

			$eSequence = $eCrop['sequence'];

			$h = '<tr>';
				$h .= '<td class="sequence-item-presentation">';
					$h .= SequenceUi::link($eSequence);
				$h .= '</td>';
				$h .= '<td>';
					$h .= CropUi::start($eCrop, $cActionMain, displayYear: TRUE, displayPrefix: FALSE);
				$h .= '</td>';
				$h .= '<td>';

					$cFlow = $eCrop['cFlow'][$cActionMain[ACTION_RECOLTE]['id']] ?? new \Collection();

					foreach($cFlow as $eFlow) {
						$h .= $this->getTextualWeek($eFlow, $cActionMain[ACTION_RECOLTE]);
					}

				$h .= '</td>';
				$h .= '<td class="sequence-item-use">';
					$h .= match($eSequence['use']) {
						Sequence::BED => s("Planche de {bedWidth} cm", $eSequence),
						Sequence::BLOCK => s("Bloc", $eSequence),
					};
				$h .= '</td>';

				if($search?->get('status') === Sequence::ACTIVE) {

					$h .= '<td class="text-end">';

						if($eFarm->canManage()) {

							$seasons = $eFarm->getSeasons();

							if(count($seasons) === 1) {

								$season = first($seasons);

								$h .= '<a href="/series/series:createFromSequence?farm='.$eFarm['id'].'&season='.$season.'&sequence='.$eSequence['id'].'" class="btn btn-sm btn-outline-secondary">';
									$h .= s("Créer une série");
								$h .= '</a>';


							} else {

								$h .= '<a class="dropdown-toggle btn btn-sm btn-outline-secondary" data-dropdown="bottom-start">'.s("Créer une série").'</a>';
								$h .= '<div class="dropdown-list">';

									foreach($eFarm->getSeasons() as $season) {

										$h .= '<a href="/series/series:createFromSequence?farm='.$eFarm['id'].'&season='.$season.'&sequence='.$eSequence['id'].'" class="dropdown-item">';
											$h .= s("Saison {value}", $season);
										$h .= '</a>';

									}

								$h .= '</div>';

							}
						}

					$h .= '</td>';

				}

			$h .= '</tr>';

			return $h;

		};

		$h = '<div class="util-overflow-sm stick-xs">';

			$h .= '<table class="sequence-item tr-bordered">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th class="util-grid-header">'.s("Nom").'</th>';
						$h .= '<th class="util-grid-header">'.s("Semaine<br/>d'implantation").'</th>';
						$h .= '<th class="util-grid-header">'.s("Semaines<br/>de récolte").'</th>';
						$h .= '<th class="util-grid-header sequence-item-use">'.s("Utilisation du sol").'</th>';
						if($search?->get('status') === Sequence::ACTIVE) {
							$h .= '<th></th>';
						}
					$h .= '</tr>';

				$h .= '</thead>';
				$h .= '<tbody>';

				if($browse) {
					$h .= $browse($ccCrop, $display);
				} else {

					foreach($ccCrop as $eCrop) {
						$h .= $display($eCrop);
					}

				}

				$h .= '</tbody>';

			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public static function getPanelHeader(Sequence $eSequence): string {

		$eSequence->expects(['name']);

		return s("Itinéraire technique {name}", ['name' => self::link($eSequence)]);

	}

	public function getTextualWeek(Flow $eFlow, \farm\Action $eAction): string {

		$year = self::getTextualYear();

		$h = '<div class="sequence-item-period">';

			if($eFlow['seasonOnly'] !== NULL) {
				$h .= '<div class="sequence-item-season">'.s("en saison {seasonOnly}", $eFlow).'</div>';
			} else if($eFlow['seasonStart'] !== NULL and $eFlow['seasonStop'] !== NULL) {
				$h .= '<div class="sequence-item-season">'.s("en saison {seasonStart} à {seasonStop}", $eFlow).'</div>';
			} else if($eFlow['seasonStart'] !== NULL) {
				$h .= '<div class="sequence-item-season">'.s("dès saison {seasonStart}", $eFlow).'</div>';
			} else if($eFlow['seasonStop'] !== NULL) {
				$h .= '<div class="sequence-item-season">'.s("jusqu'à saison {seasonStop}", $eFlow).'</div>';
			}
	

			if($eFlow['weekOnly'] !== NULL) {
				if($eFlow['yearOnly'] === 0 or $eFlow['yearOnly'] === NULL) {
					$label = s("{week}", ['week' => $eFlow['weekOnly']]);
				} else {
					$label = s("{week} {year}", ['week' => $eFlow['weekOnly'], 'year' => $year[$eFlow['yearOnly']]]);
				}
				$title = $this->getIntervalWeek($eFlow['weekOnly']);
			} else if($eFlow['weekStart'] !== NULL) {

				if($eFlow['yearStart'] === $eFlow['yearStop']) {

					$week = s("{weekStart} à {weekStop}", $eFlow);

					if($eFlow['yearStart'] === 0 or $eFlow['yearStart'] === NULL) {
						$label = $week;
					} else {
						$label = s("{week} {year}", ['week' => $week, 'year' => $year[$eFlow['yearStart']]]);
					}

				} else {

					if($eFlow['yearStart'] === 0 or $eFlow['yearStart'] === NULL) {
						$from = s("{week}", ['week' => $eFlow['weekStart']]);
					} else {
						$from = s("{week} {year}", ['week' => $eFlow['weekStart'], 'year' => $year[$eFlow['yearStart']]]);
					}

					if($eFlow['yearStop'] === 0 or $eFlow['yearStop'] === NULL) {
						$to = s("{week}", ['week' => $eFlow['weekStop']]);
					} else {
						$to = s("{week} {year}", ['week' => $eFlow['weekStop'], 'year' => $year[$eFlow['yearStop']]]);
					}

					$label = s("{from} à {to}", ['from' => $from, 'to' => $to]);

				}

				$title = s("{from} au {to}", ['from' => $this->getBeginWeek($eFlow['weekStart']), 'to' => $this->getEndWeek($eFlow['weekStop'])]);

			}

	
			$h .= '<span class="plant-start plant-start-background" style="background-color: '.$eAction['color'].'; color: white" title="'.$title.'">';
				$h .= $label;
			$h .= '</span>';

		$h .= '</div>';

		return $h;

	}

	public static function getTextualYear(): array {

		return [
			-1 => '<span class="sequence-item-season" title="'.s("Année précédente").'">'.s("n-1").'</span>',
			1 => '<span class="sequence-item-season" title="'.s("Année suivante").'">'.s("n+1").'</span>'
		];

	}

	protected function getIntervalWeek(string $week): string {
		return \util\DateUi::weekToDays(date('Y').'-W'.sprintf('%02d', $week), withYear: FALSE);
	}

	protected function getBeginWeek(string $week): string {
		$from = date('Y-m-d', strtotime(date('Y').'-W'.sprintf('%02d', $week)));
		return \util\DateUi::textual($from, \util\DateUi::DAY_MONTH);
	}

	protected function getEndWeek(string $week): string {
		$from = date('Y-m-d', strtotime(date('Y').'-W'.sprintf('%02d', $week).' + 6 DAYS'));
		return \util\DateUi::textual($from, \util\DateUi::DAY_MONTH);
	}

	public function query(\PropertyDescriber $d) {

		$d->prepend = \Asset::icon('list-task');
		$d->field = 'autocomplete';

		$d->placeholder = s("Tapez un nom d'itinéraire technique...");

		$d->autocompleteUrl = '/production/sequence:query';
		$d->autocompleteResults = function(Sequence $e) {
			return [
				'value' => $e['id'],
				'itemText' => $e['name'],
				'itemHtml' => $e['name']
			];
		};

	}

	public static function getAutocomplete(\Collection $ccCrop, \Collection $cActionMain = new \Collection()): array {

		\Asset::css('media', 'media.css');

		$results = [];

		foreach($ccCrop as $cCrop) {

			$ePlant = $cCrop->first()['plant'];

			$item = \plant\PlantUi::getVignette($ePlant, '1.5rem');
			$item .= '<div>'.encode($ePlant['name']).'</div>';

			$results[] = [
				'value' => NULL,
				'itemHtml' => $item
			];

			foreach($cCrop as $eCrop) {

				$eSequence = $eCrop['sequence'];

				$label = '<div>';
					$label .= self::name($eSequence).'<br/>';
					$label .= '<small class="color-muted">';
						$label .= implode(' / ', $eCrop['cCrop']->makeArray(fn($eCrop) => $eCrop['plant']['name'].' '.CropUi::start($eCrop, $cActionMain)));
					$label .= '</small>';
				$label .= '</div>';

				$results[] = [
					'value' => $eSequence['id'],
					'itemText' => $eSequence['name'],
					'itemHtml' => $label
				];

			}

		}


		return $results;

	}

	public function getHeader(Sequence $eSequence): string {

		$h = '<div class="sequence-header">';

			$h .= '<div class="sequence-header-title">';
				$h .= '<h1>';
					$h .= $eSequence->quick('name', SequenceUi::name($eSequence));
				$h .= '</h1>';
				if($eSequence->canWrite()) {
					$h .= '<div>';
						$h .= '<a data-dropdown="bottom-end" class="btn btn-primary dropdown-toggle">'.\Asset::icon('gear-fill').'</a>';
						$h .= '<div class="dropdown-list">';
							$h .= '<div class="dropdown-title">'.encode($eSequence['name']).'</div>';
							$h .= '<a href="/production/sequence:update?id='.$eSequence['id'].'" class="dropdown-item">'.s("Modifier l'itinéraire").'</a>';
							$h .= '<a data-ajax="/production/sequence:updateComment" post-id="'.$eSequence['id'].'" class="dropdown-item">'.s("Ajouter des notes sur cet itinéraire").'</a>';
							$h .= '<a href="/production/crop:create?sequence='.$eSequence['id'].'" class="dropdown-item">'.s("Ajouter une autre production").'</a>';
							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<a data-ajax="/production/sequence:doDuplicate" post-id="'.$eSequence['id'].'" data-confirm="'.s("Voulez-vous vraiment dupliquer tel quel cet itinéraire technique ?").'" class="dropdown-item">'.s("Dupliquer l'itinéraire").'</a>';
							$h .= '<div class="dropdown-divider"></div>';

							$h .= match($eSequence['status']) {

								Sequence::ACTIVE => '<a data-ajax="/production/sequence:doUpdateStatus" post-id="'.$eSequence['id'].'" post-status="'.Sequence::CLOSED.'" class="dropdown-item">'.s("Archiver l'itinéraire").'</a>',
								Sequence::CLOSED => '<a data-ajax="/production/sequence:doUpdateStatus" post-id="'.$eSequence['id'].'" post-status="'.Sequence::ACTIVE.'" class="dropdown-item">'.s("Désarchiver l'itinéraire").'</a>'

							};

							$h .= '<a data-ajax="/production/sequence:doDelete" post-id="'.$eSequence['id'].'" data-confirm="'.s("Voulez-vous réellement supprimer cet itinéraire technique ?").'" class="dropdown-item">'.s("Supprimer l'itinéraire").'</a>';
						$h .= '</div>';
					$h .= '</div>';
				}
			$h .= '</div>';

			$infos = [];

			if($eSequence['cycle'] === Sequence::PERENNIAL) {
				if($eSequence['perennialLifetime'] !== NULL) {
					$infos[] = s("Culture pérenne programmée pour {value} saisons", $eSequence['perennialLifetime']);
				} else {
					$infos[] = s("Culture pérenne");
				}
			} else {
				$infos[] = s("Culture annuelle");
			}

			switch($eSequence['use']) {

				case Sequence::BLOCK :
					$infos[] = s("Culture sur surface libre");
					break;

				case Sequence::BED :
					$infos[] = $eSequence->quick('bedWidth', s("Culture sur planche de {value} cm", ['value' => $eSequence['bedWidth']]));
					break;

			}

			$h .= '<div class="sequence-header-infos">';
				$h .= implode(' | ', $infos);
			$h .= '</div>';

			if($eSequence['status'] === Sequence::CLOSED) {

				$h .= '<div class="util-block mt-1">';

					$h .= \Asset::icon('lock-fill');
					$h .= ' '.s("Cet itinéraire technique est archivé, il ne peut plus être utilisé sur de nouvelles séries.");

				$h .= '</div>';

			}

			if($eSequence['description'] !== NULL) {

				$h .= '<div class="sequence-header-description util-block">';

					$description = (new \editor\EditorUi())->value($eSequence['description']);

					$h .= '<span class="sequence-header-description-icon">'.\Asset::icon('chat-right-text').'</span>';
					$h .= '<span>'.$description.'</span>';

				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	public function getPhotos(Sequence $eSequence, \Collection $cPhoto): string {

		if($cPhoto->empty() and $eSequence->canWrite() === FALSE) {
			return '';
		}

		$h = '';

		$h .= '<h3 id="scroll-photos">'.s("Photos").'</h3>';

		if($eSequence->canWrite()) {

			$h .= '<p data-media="gallery" post-sequence="'.$eSequence['id'].'">';
				$h .= \Asset::icon('plus-circle').' '.(new \media\GalleryUi())->getBothLinks();
			$h .= '</p>';

		}

		if($cPhoto->notEmpty()) {
			$h .= (new \gallery\PhotoUi())->getList($cPhoto, NULL, 4);
		}

		return $h;

	}

	public function getSeries(Sequence $eSequence, \Collection $ccSeries): string {

		if($eSequence->canWrite() === FALSE or $ccSeries->empty()) {
			return '';
		}

		$template = 'grid-template-columns: 50px minmax(200px, 1fr) 75px'.str_repeat(' 75px 100px', $eSequence['cCrop']->count());

		$h = '';

		$h .= '<h3>'.s("Séries créées").'</h3>';

		$h .= '<div class="sequence-series-wrapper sequence-series-wrapper-'.$eSequence['cCrop']->count().'">';

			$h .= '<div class="sequence-series-header">';

				$h .= '<div class="sequence-series-item" style="'.$template.'">';

					$h .= '<div></div>';
					$h .= '<div></div>';
					$h .= '<div></div>';

					foreach($eSequence['cCrop'] as $eCrop) {

						$h .= '<div class="util-grid-header text-center sequence-series-item-crop" style="grid-column: span 2">';
							$h .= \plant\PlantUi::getVignette($eCrop['plant'], '2rem').' ';
							$h .= encode($eCrop['plant']['name']);
						$h .= '</div>';

					}

				$h .= '</div>';

				$h .= '<div class="sequence-series-item" style="'.$template.'">';

					$h .= '<div class="util-grid-header">'.s("Saison").'</div>';
					$h .= '<div class="util-grid-header">'.s("Série").'</div>';
					$h .= '<div class="util-grid-header">'.s("Surface").'</div>';

					foreach($eSequence['cCrop'] as $eCrop) {

						$h .= '<div class="util-grid-header text-end">';
							$h .= s("Récolte");
						$h .= '</div>';

						$h .= '<div class="util-grid-header text-end sequence-series-item-crop">';
							$h .= s("Rendement");
						$h .= '</div>';

					}

				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="sequence-series-body">';

			foreach($ccSeries as $season => $cSeries) {

				foreach($cSeries as $eSeries) {

					$h .= '<div class="sequence-series-item" style="'.$template.'">';

						$h .= '<div class="sequence-series-item-season">';
							$h .= $season;
						$h .= '</div>';

						$h .= \series\SeriesUi::link($eSeries);

						$h .= '<div>';
							$h .= s("{area} m²", ['area' => $eSeries['area'] ?: '?']);
						$h .= '</div>';

						foreach($eSequence['cCrop'] as $eCrop) {

							if($eSeries['ccCultivation']->offsetExists($eCrop['plant']['id'])) {

								$cCultivation = $eSeries['ccCultivation'][$eCrop['plant']['id']];

								$h .= '<div class="text-end">';
									foreach($cCultivation as $eCultivation) {
										$h .= (new \series\CultivationUi())->getHarvestedByUnits($eCultivation);
									}
								$h .= '</div>';

								$h .= '<div class="text-end sequence-series-item-crop">';
									foreach($cCultivation as $eCultivation) {
										$h .= '<span class="annotation">'.(new \series\CultivationUi())->getYieldByUnits($eSeries, $eCultivation).'</span><br/>';
									}
								$h .= '</div>';

							} else {

								$h .= '<div class="text-end">';
									$h .= '-';
								$h .= '</div>';

								$h .= '<div class="text-end sequence-series-item-crop">';
									$h .= '-';
								$h .= '</div>';

							}

						}

					$h .= '</div>';

				}

			}

			$h .= '</div>';

		$h .= '</div>';


		return $h;

	}

	public function getComment(Sequence $eSequence): string {

		$h = '<div id="sequence-comment" class="util-block">';

		if($eSequence['comment'] !== NULL) {

			$h .= '<div class="sequence-comment-title">';
				$h .= '<h4>'.s("Notes").'</h4>';
				$h .= '<div>';
					$h .= '<a data-ajax="/production/sequence:updateComment" post-id="'.$eSequence['id'].'">'.\Asset::icon('pencil-fill').'</a>';
				$h .= '</div>';
			$h .= '</div>';

			$h .= (new \editor\EditorUi())->value($eSequence['comment']);

		}

		$h .= '</div>';

		return $h;

	}

	public function getCommentField(Sequence $eSequence): string {

		$form = new \util\FormUi();

		$h = '<div id="sequence-comment" class="util-block">';

			$h .= '<h4>'.s("Notes").'</h4>';

			$h .= $form->openAjax('/production/sequence:doUpdateComment');

				$h .= $form->hidden('id', $eSequence['id']);

				$h .= $form->dynamicField($eSequence, 'comment');

				$h .= '<div class="sequence-comment-submit">';
					$h .= $form->submit(s("Valider"), ['class' => 'btn btn-secondary']);
					$h .= $form->button(s("Annuler"), ['class' => 'btn', 'data-ajax' => '/production/sequence:restoreComment', 'post-id' => $eSequence['id']]);
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function create(Sequence $eSequence): \Panel {

		$eSequence->expects(['farm']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/production/sequence:doCreate', ['id' => 'sequence-create', 'autocomplete' => 'off']);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eSequence['farm']['id']);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eSequence['farm'], TRUE)
			);

			$h .= $form->dynamicGroup($eSequence, 'name*');

			$h .= $form->dynamicGroups($eSequence, ['plantsList*', 'cycle', 'perennialLifetime', 'summary', 'description', 'use*', 'bedWidth*', 'alleyWidth']);

			$h .= $form->group(
				content: $form->submit(s("Créer l'itinéraire technique"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Créer un itinéraire technique"),
			body: $h
		);

	}

	public function update(Sequence $eSequence): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/production/sequence:doUpdate', ['id' => 'sequence-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eSequence['id']);

			$h .= $form->dynamicGroup($eSequence, 'name');

			$h .= $form->group(
				self::p('cycle')->label,
				'<u>'.self::p('cycle')->values[$eSequence['cycle']].'</u>'
			);

			if($eSequence['cycle'] === Sequence::PERENNIAL) {
				$h .= $form->dynamicGroup($eSequence, 'perennialLifetime');
			}

			$h .= $form->dynamicGroups($eSequence, ['summary', 'description', 'use', 'bedWidth', 'alleyWidth']);

			$h .= $form->group(
				'',
				'<h4>'.s("Informations optionnelles et complémentaires").'</h4>'
			);

			$h .= $form->dynamicGroups(
				$eSequence,
				['mode']
			);

			$h .= $form->group(
				s("Visibilité"),
				$form->radio('visibility', Sequence::PRIVATE, s("<b>Privé</b> - uniquement accessible pour les utilisateurs de la ferme {farm}", ['farm' => \farm\FarmUi::link($eSequence['farm'])]), $eSequence['visibility']).
				$form->radio('visibility', Sequence::PUBLIC, s("<b>Partagé</b> - accessible librement sur {siteName}"), $eSequence['visibility'])
			);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un itinéraire technique"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Sequence::model()->describer($property, [
			'name' => s("Nom de l'itinéraire"),
			'summary' => s("Courte description"),
			'description' => s("Description complète"),
			'author' => s("Auteur"),
			'farm' => s("Ferme"),
			'plantsList' => s("Espèces"),
			'duplicateOf' => s("Crée à partir de"),
			'use' => s("Utilisation du sol"),
			'bedWidth' => s("Largeur de planche"),
			'alleyWidth' => s("Largeur de passe-pieds entre les planches"),
			'mode' => s("Mode de culture"),
			'visibility' => s("Visibilité"),
			'cycle' => s("Cycle de culture"),
			'createdAt' => s("Créé le"),
		]);

		switch($property) {

			case 'name' :
				$d->attributes = [
					'placeholder' => s("Ex. : Épinard de printemps")
				];
				break;

			case 'cycle' :
				$d->values = [
					Sequence::ANNUAL => s("Culture annuelle"),
					Sequence::PERENNIAL => s("Culture pérenne"),
				];
				$d->attributes = [
					'data-action' => 'sequence-cycle-change',
					'columns' => 2,
					'mandatory' => TRUE
				];
				break;

			case 'perennialLifetime' :
				$d->groupLabel = FALSE;
				$d->prepend = s("Durée de vie de la culture").'&nbsp;&nbsp;'.\Asset::icon('arrow-right');
				$d->append = s("saison(s)");
				$d->after = '<small>'.s("Vous pouvez laisser vide si la durée de vie n'est pas connue à ce jour.").'</small>';
				$d->group = function(Sequence $e) {

					$e->expects(['cycle']);

					return [
						'id' => 'sequence-write-perennial-lifetime',
						'style' => ($e['cycle'] === \production\Sequence::PERENNIAL) ? '' : 'display: none'
					];

				};
				break;

			case 'summary' :
				$d->attributes = [
					'placeholder' => s("Ex. : Culture sur bâche tissée avec production de plants"),
					'data-limit' => Sequence::model()->getPropertyRange('summary')[1]
				];
				break;

			case 'use' :
				$d->values = [
					Sequence::BED => s("Culture sur planches"),
					Sequence::BLOCK => s("Culture sur surface libre"),
				];
				$d->attributes = [
					'data-action' => 'sequence-use-change',
					'columns' => 2,
					'mandatory' => TRUE
				];
				break;

			case 'bedWidth' :
				$d->append = s("cm");
				$d->after = \util\FormUi::info(s("La largeur travaillée de planche est obligatoirement à renseigner pour définir le périmètre de l'itinéraire technique."));
				$d->group = function(Sequence $e) {

					$use = $e['use'] ?? NULL;

					return [
						'style' => ($use === Sequence::BED) ? '' : 'display: none'
					];

				};
				break;

			case 'alleyWidth' :
				$d->append = s("cm");
				$d->after = \util\FormUi::info(s("Les rendements sont calculés en intégrant la largeur du passe-pied."));
				$d->group = function(Sequence $e) {

					$use = $e['use'] ?? NULL;

					return [
						'style' => ($use === Sequence::BED) ? '' : 'display: none'
					];

				};
				break;

			case 'mode' :
				$d->values = [
					Sequence::GREENHOUSE => s("Sous abri"),
					Sequence::OUTDOOR => s("Plein champ"),
					Sequence::MIX => s("Mixte"),
				];
				$d->attributes = [
					'columns' => 3,
					'mandatory' => TRUE
				];
				break;

			case 'plantsList' :
				$d->after = \util\FormUi::info(s("Il est possible d'intégrer plusieurs espèces pour réaliser des associations de culture."));
				$d->autocompleteBody = function(\util\FormUi $form, Sequence $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id']
					];
				};
				(new \plant\PlantUi())->query($d, TRUE);
				$d->group = ['wrapper' => 'plantsList'];
				break;

			case 'visibility' :
				$d->values = [
					Sequence::PRIVATE => s("Privé"),
					Sequence::PUBLIC => s("Public"),
				];
				break;

		}

		return $d;

	}

}
?>
