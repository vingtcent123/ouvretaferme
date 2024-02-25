<?php
namespace production;

class FlowUi {

	public function __construct() {
		\Asset::css('production', 'flow.css');
		\Asset::js('production', 'flow.js');
	}

	public function getTimeline(Sequence $eSequence, array $events, bool $write, ?int $referenceYear = NULL): string {

		if($events === []) {
			return $this->getEmptyTimeline($eSequence, $write);
		}

		$cCrop = $eSequence['cCrop'];

		$form = new \util\FormUi();

		$h = '<div id="flow-wrapper" data-sequence="'.$eSequence['id'].'">';

			$h .= '<div class="flow-timeline-wrapper stick-xs">';

				$h .= $this->getTimelineHeader($form, $write);

				$h .= '<div class="flow-timeline-body">';

				$lasting = [];
				$lastingTabs = [];

				foreach($events as $season => $years) {

					$endless = (function($years) {

						foreach($years as $weeks) {
							foreach($weeks as $flows) {
								foreach($flows as $flow) {
									if($flow['endless'] === FALSE) {
										return FALSE;
									}
								}
							}
						}

						return TRUE;

					})($years);

					if($eSequence['cycle'] === Sequence::PERENNIAL) {

						$h .= '<div class="flow-timeline flow-timeline-new-season">';

							$h .= '<h5>';
								if($endless) {
									$h .= s("Saison {value} et suivantes", $season);
								} else {
									$h .= s("Saison {value}", $season);
								}
							$h .= '</h5>';

							$h .= '<div class="flow-timeline-action">';
								$h .= $this->drawLasting($lasting, new Flow());
							$h .= '</div>';

						$h .= '</div>';

					}

					$firstYear = array_key_first($years);

					foreach($years as $year => $weeks) {

						if($referenceYear !== NULL) {
							$currentYear = $referenceYear + $year - $firstYear;
						} else {
							$currentYear = (int)date('Y') + $year;
						}

						if(
							count($years) > 1 or // Plusieurs années..
							$year !== 0 // Ou uniquement une année qui n'est pas l'année N
						) {

							$h .= '<div class="flow-timeline flow-timeline-new-year">';

								if($referenceYear !== NULL) {
									$h .= '<div class="flow-timeline-reference-year">';
										$h .= $currentYear;
									$h .= '</div>';
									$h .= '<div></div>';
								} else {
									$h .= '<div class="flow-timeline-anonymous-year">';
										$h .= self::p('yearOnly')->values[$year];
									$h .= '</div>';
								}

								$h .= '<div class="flow-timeline-update">';
								$h .= '</div>';

								$h .= '<div class="flow-timeline-action">';
									$h .= $this->drawLasting($lasting, new Flow());
								$h .= '</div>';

							$h .= '</div>';

						}

						$lastWeek = NULL;

						foreach($weeks as $week => $flows) {

							$newWeek = TRUE;
							$weekPosition = 0;

							foreach($flows as ['field' => $field, 'flow' => $eFlow]) {

								if($eFlow['crop']->empty() === FALSE) {
									$eFlow['crop'] = $cCrop[$eFlow['crop']['id']];
									$eFlow['plant'] = $eFlow['crop']['plant'];
								}

								switch($field) {

									case 'start' :

										$lasting[] = $eFlow;
										$lastingTabs[$eFlow['id']] = count($lasting);

								}

								$firstOfWeek = ($weekPosition === 0);
								$lastOfWeek = ($weekPosition === count($flows) - 1);

								if($newWeek and $lastWeek !== NULL and $week - $lastWeek > 1) {

									$h .= '<div class="flow-timeline flow-timeline-inter">';

										$h .= '<div class="flow-timeline-item">';
										$h .= '</div>';

										$h .= '<div class="flow-timeline-week">';
										$h .= '</div>';

										$h .= '<div class="flow-timeline-update">';
										$h .= '</div>';

										$h .= '<div class="flow-timeline-action">';
											$h .= $this->drawLasting($lasting, $eFlow, $field);
										$h .= '</div>';
									$h .= '</div>';
								}

								$h .= '<div class="flow-timeline flow-timeline-'.$field.' '.($newWeek ? 'flow-timeline-new-week' : '').'">';

									$h .= '<div class="flow-timeline-item">';

										if($newWeek) {
											$h .= '<div class="flow-timeline-circle" data-dropdown="bottom-start">'.s("s{value}", $week).'</div>';
										}

									$h .= '</div>';

									$h .= '<div class="flow-timeline-week">';

										if($newWeek) {
											$h .= \util\DateUi::weekToDays($currentYear.'-W'.sprintf('%02d', $week), TRUE, FALSE);
										}

									$h .= '</div>';


									$h .= '<div class="flow-timeline-update">';

										if($write) {
											if($field !== 'stop') {
												$h .= '<label class="flow-timeline-select batch-item">';
													$h .= $this->getBatchCheckbox($form, $eFlow);
												$h .= '</label>';
											} else {
												$h .= '<label></label>';
											}
										}

									$h .= '</div>';

									$h .= '<div class="flow-timeline-action">';

										$margin = 0;

										foreach($lasting as $eFlowLasting) {

											if($eFlowLasting->empty() === FALSE) {

												$classes = 'flow-timeline-lasting';

												if($field === 'start' and $eFlowLasting['id'] === $eFlow['id']) {
													$classes .= ' flow-timeline-lasting-start';
												} else if($field === 'stop' and $eFlowLasting['id'] === $eFlow['id']) {
													$classes .= ' flow-timeline-lasting-stop';
												} else {
													$classes .= ' flow-timeline-lasting-running';
												}

												$h .= '<div class="'.$classes.'" style="left: '.$margin.'rem; background-color: '.$eFlowLasting['action']['color'].'"></div>';

											}

											$margin++;

										}

										if($field === 'only') {

											$h .= '<div class="flow-timeline-lasting-only" style="left: '.$margin.'rem; background-color: '.$eFlow['action']['color'].'"></div>';
											$margin++;

										}

										if($field === 'stop') {
											$margin = $lastingTabs[$eFlow['id']];
										}

										$h .= '<div class="flow-timeline-label" style="margin-left: '.$margin.'rem">';

										if($field !== 'stop') {

											$h .= '<div class="flow-timeline-move">';

												$h .= '<a href="/production/flow:update?id='.$eFlow['id'].'" class="flow-timeline-text">';

													$h .= \farm\ActionUi::text($eFlow).' ';
													$h .= (new \production\FlowUi())->getMore($eFlow);

													if($eFlow['frequency'] !== NULL) {
														$h .= ' '.\Asset::icon('arrow-right').' '.$this->p('frequency')->values[$eFlow['frequency']];
													}

												$h .= '</a>';

												$h .= '<div>';
													if($write and $eFlow->canWrite()) {
														if($firstOfWeek === FALSE) {
															$h .= '<a data-ajax="/production/flow:doPosition" post-id="'.$eFlow['id'].'" post-positions="'.encode(json_encode($this->changeDirection($flows, $eFlow, 'up'))).'" class="flow-timeline-update-up">'.\Asset::icon('arrow-up-circle').'</a> ';
														}
														if($lastOfWeek === FALSE) {
															$h .= '<a data-ajax="/production/flow:doPosition" post-id="'.$eFlow['id'].'" post-positions="'.encode(json_encode($this->changeDirection($flows, $eFlow, 'down'))).'" class="flow-timeline-update-down">'.\Asset::icon('arrow-down-circle').'</a>';
														}
													}
												$h .= '</div>';

											$h .= '</div>';

											$h .= $this->getComment($eFlow);
											$h .= $this->getTools($eFlow);

										} else {

											$h .= '<span class="flow-timeline-text">';
												$h .= s("Fin de {action}", ['action' => lcfirst($eFlow['action']['name'])]);
											$h .= '</span>';

										}

										$h .= '</div>';

									$h .= '</div>';

								$h .= '</div>';

								if($field === 'stop') {

									$position = 0;
									$length = count($lasting);

									foreach($lasting as $key => $eFlowLasting) {

										if($eFlowLasting->empty() === FALSE and $eFlow['id'] === $eFlowLasting['id']) {

											if($position === $length - 1) {
												unset($lasting[$key]);
											} else {
												$lasting[$key] = new Flow();
											}

										}

										$position++;

									}

								}

								$newWeek = FALSE;
								$weekPosition++;

							}


							$lastWeek = $week;

						}

					}

					if($endless) {
						break;
					}

				}

				$h .= '</div>';

			$h .= '</div>';

			if($write) {
				$h .= $this->getBatch();
			}

		$h .= '</div>';

		return $h;

	}

	protected function getBatchCheckbox(\util\FormUi $form, Flow $eFlow): string {

		return $form->inputCheckbox('batch[]', $eFlow['id'], [
			'oninput' => 'Flow.changeSelection()'
		]);

	}

	public function getBatch(): string {

		$form = new \util\FormUi();

		$h = '<div id="batch-one" class="util-bar-inline hide">';

			$h .= $form->open('batch-one-form');

				$h .= '<div class="batch-ids hide"></div>';
				$h .= '<div class="util-bar-inline-menu">';

					$h .= '<div class="batch-menu-planned">';
						$h .= '<a data-dropdown="top-start" class="util-bar-inline-item">';
							$h .= \Asset::icon('watch');
							$h .= '<span>'.s("Planifier").'</span>';
						$h .= '</a>';
						$h .= $this->getBatchPlanned('batch-one-form');
					$h .= '</div>';

					$h .= '<a class="util-bar-inline-item batch-menu-update">'.\Asset::icon('gear-fill').'<span>'.s("Modifier").'</span></a>';

					$h .= '<a data-ajax-submit="/production/flow:doDeleteCollection" class="util-bar-inline-item" data-confirm="'.s("Confirmer la suppression de cette intervention ?").'">'.\Asset::icon('trash').'<span>'.s("Supprimer").'</span></a>';

				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		$h .= '<div id="batch-several" class="util-bar hide">';

			$h .= $form->open('batch-several-form');

			$h .= '<div class="batch-ids hide"></div>';

			$h .= '<div class="batch-title">';
				$h .= '<h4>'.s("Pour la sélection").' (<span id="batch-menu-count"></span>)</h4>';
				$h .= '<a onclick="Flow.hideSelection()" class="btn btn-transparent">'.s("Annuler").'</a>';
			$h .= '</div>';

			$h .= '<div class="batch-menu">';
				$h .= '<div class="util-bar-menu">';

					$h .= '<a data-dropdown="top-start" class="util-bar-menu-item">';
						$h .= \Asset::icon('watch');
						$h .= '<span>'.s("Planifier").'</span>';
					$h .= '</a>';
					$h .= $this->getBatchPlanned('batch-several-form');

					$h .= '<a data-ajax-submit="/production/flow:doDeleteCollection" data-confirm="'.s("Confirmer la suppression de ces interventions ?").'" class="util-bar-menu-item">';
						$h .= \Asset::icon('trash');
						$h .= '<span>'.s("Supprimer").'</span>';
					$h .= '</a>';

				$h .= '</div>';
			$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	protected function getBatchPlanned(string $formId) {

		$h = '<div class="dropdown-list bg-secondary">';
			$h .= '<a data-ajax-submit="/production/flow:doIncrementWeekCollection" data-ajax-target="#'.$formId.'" class="dropdown-item" post-increment="-1">'.s("Une semaine plus tôt").'</a>';
			$h .= '<a data-ajax-submit="/production/flow:doIncrementWeekCollection" data-ajax-target="#'.$formId.'" class="dropdown-item" post-increment="1">'.s("Une semaine plus tard").'</a>';
			$h .= '<a data-ajax-submit="/production/flow:incrementWeekCollection" data-ajax-method="get" data-ajax-target="#'.$formId.'" class="dropdown-item">'.s("Décaler davantage").'</a>';
		$h .= '</div>';

		return $h;

	}

	protected function getTimelineHeader(\util\FormUi $form, bool $write): string {

		$h = '<div class="flow-timeline flow-timeline-header">';

			$h .= '<div class="util-grid-header util-grid-icon text-center">';
				$h .= \Asset::icon('calendar-week');
			$h .= '</div>';
			$h .= '<div></div>';
			$h .= '<div class="flow-timeline-update">';
				if($write) {
					$h .= '<label class="flow-timeline-select" title="'.s("Tout cocher / Tout décocher").'">';
						$h .= $form->inputCheckbox(attributes: ['onclick' => 'Flow.changeAllSelection(this)', 'id' => 'batch-all']);
					$h .= '</label>';
				}
			$h .= '</div>';
			$h .= '<div class="flow-timeline-action util-grid-header">';
				$h .= s("Intervention");
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function drawLasting(array $lasting, Flow $eFlow, ?string $field = NULL): string {

		$h = '';

		$margin = 0;

		foreach($lasting as $eFlowLasting) {

			if($eFlowLasting->empty() === FALSE) {

				if($eFlow->empty() === FALSE) {

					if($field === 'start' and $eFlowLasting['id'] === $eFlow['id']) {
						continue;
					}

				}

				$h .= '<div class="flow-timeline-lasting flow-timeline-lasting-running" style="left: '.$margin.'rem; background-color: '.$eFlowLasting['action']['color'].'"></div>';

			}

			$margin++;

		}

		return $h;

	}

	protected function getEmptyTimeline(Sequence $eSequence, bool $write): string {

		$h = '<div id="flow-wrapper" data-sequence="'.$eSequence['id'].'">';

			$h .= '<p class="util-info">';
				if($write) {
					$h .= s("Vous n'avez pas encore saisi d'intervention pour cet itinéraire technique.");
				} else {
					$h .= s("Il n'y a pas encore d'intervention pour cet itinéraire technique.");
				}
			$h .= '</p>';

		$h .= '</div>';

		return $h;

	}

	public function planTask(Sequence $eSequence): string {

		if($eSequence->canWrite() === FALSE) {
			return '';
		}

		$h = '<div>';
			$h .= '<a class="btn btn-outline-primary" href="/production/flow:create?sequence='.$eSequence['id'].'">'.\Asset::icon('plus-circle').' '.s("Nouvelle intervention").'</a>';
		$h .= '</div>';

		return $h;

	}

	public function getMore(\series\Task|Flow $eFlow): string {

		switch($eFlow['action']['fqn']) {

			case ACTION_RECOLTE :
				if($eFlow instanceof \series\Task and $eFlow['harvest'] > 0.0) {

					$h = '<span class="annotation" style="color: '.$eFlow['action']['color'].'">';

					if(isset($eFlow['harvestPeriod'])) {

						$h .= $eFlow->format('harvestPeriod');

						$eFlow['harvestOutPeriod'] = round($eFlow['harvest'] - $eFlow['harvestPeriod'], 1);

						if($eFlow['harvestOutPeriod'] > 0.0) {

							$h .= '<span class="flow-harvest-out-period" title="'.s("Quantité récoltée sur une autre période").'">';
								if($eFlow['harvestPeriod'] > 0.0) {
									$h .= '&nbsp;';
								}
								$h .= \Asset::icon('plus').'&nbsp;';
								$h .= \production\CropUi::getYield($eFlow, 'harvestOutPeriod', 'harvestUnit', []);
							$h .= '</span>';

						}

					} else {
						$h .= $eFlow->format('harvest');
					}

					$h .= '</span>';

					return $h;

				} else {
					return '';
				}

			case ACTION_FERTILISATION :
				if($eFlow['fertilizer'] !== NULL) {

					[$major, $minor] = (new \production\FlowUi())->getFertilizer($eFlow);

					$major = array_map(fn($value) => '<span class="annotation">'.$value.'</span>', $major);
					$minor = array_map(fn($value) => '<span class="annotation">'.$value.'</span>', $minor);

					$h = '<span class="flow-timeline-fertilizer">';
						$h .= implode('&nbsp;&nbsp;', $major);
						if($major and $minor) {
							$h .= ' + ';
						}
						$h .= implode('&nbsp;&nbsp;', $minor);
					$h .= '</span>';

					return $h;

				} else {
					return '';
				}

			default :
				return '';

		}

	}

	public function getComment(\series\Task|Flow $eFlow): string {

		$h = '';

		if($eFlow['description'] !== NULL) {
			$h .= '<div class="flow-timeline-description">';
				$h .= nl2br(encode($eFlow['description']));
			$h .= '</div>';
		}

		return $h;

	}

	public function getFertilizer(\series\Task|Flow $eFlow): array {

		return (new \farm\RoutineUi())->getSeparateFertilizer($eFlow['fertilizer']);

	}

	public function getTools(\series\Task|Flow $eFlow): string {

		$eFlow->expects(['cRequirement']);

		$h = '';

		if($eFlow['cRequirement']->notEmpty()) {

			$h = '<div class="flow-timeline-tools">';
				$h .= (new \farm\ToolUi())->getList($eFlow['cRequirement']->getColumnCollection('tool'));
			$h .= '</div>';

		}

		return $h;

	}

	protected function changeDirection(array $flows, Flow $eFlow, string $direction): array {

		$positions = [];
		$currentPosition = NULL;

		foreach($flows as ['field' => $field, 'flow' => $eFlowCheck]) {

			if($eFlow['id'] === $eFlowCheck['id']) {
				$currentPosition = count($positions);
			}

			$positions[] = [$eFlowCheck['id'], $field];

		}

		switch($direction) {

			case 'up' :

				if($currentPosition === 0) {
					return $positions;
				}

				return array_merge(
					array_slice($positions, 0, $currentPosition - 1),
					[$positions[$currentPosition]],
					[$positions[$currentPosition - 1]],
					array_slice($positions, $currentPosition + 1),
				);

			case 'down' :

				if($currentPosition === count($positions) - 1) {
					return $positions;
				}

				return array_merge(
					array_slice($positions, 0, $currentPosition),
					[$positions[$currentPosition + 1]],
					[$positions[$currentPosition]],
					array_slice($positions, $currentPosition + 2),
				);

		}

	}

	public function create(Sequence $eSequence, \Collection $cAction): \Panel {

		$form = new \util\FormUi();

		$eFlow = new Flow([
			'sequence' => $eSequence,
			'crop' => new Crop(),
			'yearOnly' => 0,
			'yearStart' => 0,
			'yearStop' => 0,
			'frequency' => Flow::W1,
			'action' => new \farm\Action()
		]);

		if($eSequence['cCrop']->count() > 1) {
			$cAction->map(fn($eAction) => $eAction['disabled'] = ($eAction['fqn'] === ACTION_RECOLTE));
		}

		$h = '';

		$h .= $form->openAjax('/production/flow:doCreate', ['id' => 'flow-create', 'autocomplete' => 'off']);

			$h .= $form->hidden('sequence', $eSequence['id']);

			$h .= $this->getCropField($form, $eSequence, $eFlow);

			$h .= $form->dynamicGroup($eFlow, 'action', function($d) use ($cAction) {
				$d->values = $cAction;
			});

			$h .= '<div id="flow-create-fertilizer">';
				$h .= $this->getFertilizerField($form, $eFlow);
			$h .= '</div>';

			if($eFlow['sequence']['cycle'] === Sequence::PERENNIAL) {
				$h .= $this->getSeasonField($form, $eFlow);
			}

			if($eFlow['sequence']['cycle'] === Sequence::PERENNIAL) {
				$h .= $this->getSeasonField($form, $eFlow);
			}

			$h .= $this->getPeriodField($form, $eFlow);

			$h .= $form->dynamicGroup($eFlow, 'description');

			$h .= '<div data-ref="tools" data-farm="'.$eFlow['sequence']['farm']['id'].'"></div>';

			$h .= $form->group(
				content: $form->submit(s("Ajouter l'intervention"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter une intervention"),
			subTitle: $this->getWriteHeader($eSequence),
			body: $h
		);

	}

	public function update(Sequence $eSequence, Flow $eFlow, \Collection $cAction, \Collection $cToolAvailable): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/production/flow:doUpdate', ['id' => 'flow-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eFlow['id']);

			$h .= $this->getCropField($form, $eSequence, $eFlow);

			$h .= $form->dynamicGroup($eFlow, 'action', function($d) use ($cAction) {
				$d->values = $cAction;
			});

			if($eFlow['action']['fqn'] === ACTION_FERTILISATION) {
				$h .= $this->getFertilizerField($form, $eFlow);
			}

			if($eFlow['sequence']['cycle'] === Sequence::PERENNIAL) {
				$h .= $this->getSeasonField($form, $eFlow);
			}

			$h .= $this->getPeriodField($form, $eFlow);

			$h .= $form->dynamicGroup($eFlow, 'description');

			$h .= '<div data-ref="tools" data-farm="'.$eFlow['sequence']['farm']['id'].'">';
				if($cToolAvailable->notEmpty()) {
					$h .= $this->getToolsField($form, $eFlow);
				}
			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une intervention"),
			subTitle: $this->getWriteHeader($eSequence),
			body: $h,
		);

	}

	public function updateIncrementWeekCollection(\Collection $cTask): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/production/flow:doIncrementWeekCollection', ['autocomplete' => 'off']);

			$h .= $form->group(
				p("Intervention", "Interventions", $cTask->count()),
				$this->getFlowsField($form, $cTask),
				['wrapper' => 'tasks']
			);

			$h .= $form->group(
				s("Décaler de..."),
				$form->inputGroup(
					$form->number('increment', attributes: [
						'onrender' => 'this.focus();',
						'min' => -26,
						'max' => 26
					]).
					$form->addon(s("semaine(s)"))
				).
				\util\FormUi::info(s("Utilisez un nombre négatif pour décaler plus tôt et positif pour décaler plus tard"))
			);

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Décaler"),
			body: $h
		);

	}

	public function getFlowsField(\util\FormUi $form, \Collection $cFlow): string {

		$display = ($cFlow->count() > 1) ? 'checkbox' : 'hidden';

		$h = '<table class="tr-bordered stick-xs">';

			$h .= '<thead>';
				$h .= '<tr>';
					if($display === 'checkbox') {
						$h .= '<th></th>';
					}
					$h .= '<th>'.s("Action").'</th>';
					$h .= '<th>'.s("Semaine").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

				foreach($cFlow as $eFlow) {
					$h .= '<tr>';
						if($display === 'checkbox') {
							$h .= '<td class="td-checkbox">';
								$h .= '<label>'.$form->inputCheckbox('ids[]', $eFlow['id'], ['checked']).'</label>';
							$h .= '</td>';
						}
						$h .= '<td>'.\farm\ActionUi::text($eFlow).'</td>';
						$h .= '<td>'.(new SequenceUi())->getTextualWeek($eFlow, $eFlow['action']).'</td>';
					$h .= '</tr>';
				}

			$h .= '</body>';

		$h .= '</table>';

		if($display === 'hidden') {
			foreach($cFlow as $eFlow) {
				$h .= $form->hidden('ids[]', $eFlow['id']);
			}
		}

		return $h;

	}

	protected function getWriteHeader(Sequence $eSequence): string {
		return s("Itinéraire technique {value}",  SequenceUi::link($eSequence));
	}

	public function getFertilizerField(\util\FormUi $form, Flow $eFlow): string {

		$eFlow->expects(['action']);

		return $form->group(
			self::p('fertilizer')->label,
			(new \farm\RoutineUi())->getFieldFertilizer($form, 'fertilizer', $eFlow['fertilizer'] ?? NULL),
			['class' => ($eFlow['action']->notEmpty() and $eFlow['action']['fqn'] === ACTION_FERTILISATION) ? '' : 'hide', 'wrapper' => 'fertilizer']
		);

	}

	public function getCropField(\util\FormUi $form, Sequence $eSequence, Flow $eFlow): string {

		$eSequence->expects(['cCrop']);

		if($eSequence['cCrop']->count() > 1) {

			$values = [
				NULL => s("Partagé")
			];

			foreach($eSequence['cCrop'] as $eCrop) {
				$values[$eCrop['id']] = \plant\PlantUi::getVignette($eCrop['plant'], '2rem').' '.encode($eCrop['plant']['name']);
			}

			return $form->group(
				$this->p('crop'),
				$form->radios('crop', $values, $eFlow->empty() ? NULL : $eFlow['crop'], attributes: [
					'mandatory' => TRUE,
					'callbackRadioAttributes' => function() {
						return [
							'onclick' => 'Flow.createSelectCultivation(this)'
						];
					}
				])
			);

		} else {
			return $form->hidden('crop', $eSequence['cCrop']->first()['id']);
		}

	}

	public function getToolsField(\util\FormUi $form, Flow $eFlow): string {

		return $form->dynamicGroup($eFlow, 'toolsList');

	}

	protected function getPeriodField(\util\FormUi $form, Flow $eFlow): string {

		if(($eFlow['weekStart'] ?? NULL) === NULL) {
			$styleOnly = '';
			$styleInterval = 'display: none';
			$period = 'only';
		} else {
			$styleOnly = 'display: none';
			$styleInterval = '';
			$period = 'interval';
		}

		$h = $form->hidden('period', $period, ['id' => 'flow-period']);

		$h .= '<div id="flow-period-only" style="'.$styleOnly.'">';
			$h .= $form->group(
				self::p('weekOnly')->label,
				$this->getWeekField($form, $eFlow, 'only').
				'<div class="field-followup"><a data-action="flow-period-interval">'.s("Répéter l'intervention plusieurs fois dans la saison").'</a></div>',
				['wrapper' => 'weekOnly yearOnly']
			);
		$h .= '</div>';

		$h .= '<div id="flow-period-interval" style="'.$styleInterval.'">';

			$h .= $form->group(
				self::p('weekStart')->label,
				$this->getWeekField($form, $eFlow, 'start'),
				['wrapper' => 'weekStart yearStart']
			);

			$h .= $form->group(
				self::p('weekStop')->label,
				$this->getWeekField($form, $eFlow, 'stop').
				'<div class="field-followup"><a data-action="flow-period-only">'.s("Ne pas répéter l'intervention dans la saison").'</a></div>',
				['wrapper' => 'weekStop yearStop']
			);

			$h .= $form->dynamicGroup($eFlow, 'frequency');

		$h .= '</div>';

		return $h;

	}

	protected function getSeasonField(\util\FormUi $form, Flow $eFlow): string {

		if(($eFlow['seasonStart'] ?? NULL) === NULL) {
			$styleOnly = '';
			$styleInterval = 'display: none';
			$season = 'only';
		} else {
			$styleOnly = 'display: none';
			$styleInterval = '';
			$season = 'interval';
		}

		$values = function($d) use ($eFlow) {

			$d->values = [];

			$lastSeason = $eFlow['sequence']['perennialLifetime'] ?? \Setting::get('maxSeasonStop');

			for($season = 1; $season <= $lastSeason; $season++) {
				$d->values[$season] = s("Saison {value}", $season);
			}

		};

		$h = $form->hidden('season', $season, ['id' => 'flow-season']);

		$h .= '<div id="flow-season-only" style="'.$styleOnly.'">';
			$h .= $form->group(
				self::p('seasonOnly')->label,
				$form->dynamicField($eFlow, 'seasonOnly', $values).
				'<div class="field-followup"><a data-action="flow-season-interval">'.s("Répéter l'intervention sur plusieurs saisons").'</a></div>'
			);
		$h .= '</div>';

		$h .= '<div id="flow-season-interval" style="'.$styleInterval.'">';

			$h .= $form->group(
				self::p('seasonStart')->label,
				$form->dynamicField($eFlow, 'seasonStart', $values)
			);

			$h .= $form->group(
				self::p('seasonStop')->label,
				$form->dynamicField($eFlow, 'seasonStop', $values).
				'<div class="field-followup"><a data-action="flow-season-only">'.s("Ne pas répéter l'intervention sur plusieurs saisons").'</a></div>'
			);

		$h .= '</div>';

		return $h;

	}

	public function getWeekField(\util\FormUi $form, Flow $eFlow, string $name): string {

		str_is($name, ['only', 'start', 'stop']);

		$h = '<div class="flow-write-week">';
			$h .= $form->dynamicField($eFlow, 'week'.ucfirst($name));
			if($eFlow['sequence']['cycle'] === Sequence::ANNUAL) {
				$h .= $form->dynamicField($eFlow, 'year'.ucfirst($name));
			}
		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Flow::model()->describer($property, [
			'crop' => s("Production"),
			'action' => s("Intervention"),
			'fertilizer' => s("Apports"),
			'description' => s("Observation sur l'intervention"),
			'seasonOnly' => s("Quelle saison pour cette intervention ?"),
			'seasonStart' => s("Première saison de l'intervention"),
			'seasonStop' => s("Dernière saison de l'intervention"),
			'weekOnly' => s("Semaine de l'intervention"),
			'weekStart' => s("Début de l'intervention"),
			'weekStop' => s("Fin de l'intervention"),
			'frequency' => s("Fréquence de l'intervention"),
			'toolsList' => s("Matériel nécessaire")
		]);

		switch($property) {

			case 'description' :
				$d->after = '<small>'.s("Le commentaire est facultatif et permet d'apporter des précisions sur l'intervention à réaliser.").'</small>';
				break;

			case 'action' :
				$d->field = 'radio';
				$d->attributes = [
					'columns' => 3,
					'mandatory' => TRUE,
					'callbackRadioAttributes' => function(\farm\Action $eAction) {
						return [
							'disabled' => ($eAction['disabled'] ?? FALSE) ? 'disabled' : NULL,
							'data-action' => 'flow-write-action-change',
							'data-fqn' => $eAction['fqn']
						];
					}
				];
				break;

			case 'weekOnly' :
			case 'weekStart' :
			case 'weekStop' :
				$d->field = fn(\util\FormUi $form, Flow $e) => $form->week($property, isset($e[$property]) ? date('Y').'-W'.sprintf('%02d', $e[$property]) : NULL, ['withYear' => FALSE]);
				break;

			case 'yearOnly' :
			case 'yearStart' :
			case 'yearStop' :
				$d->field = 'select';
				$d->values = [
					-1 => s("Année « N-1 »"),
					0 => s("Année « N »"),
					1 => s("Année « N+1 »")
				];
				$d->attributes = ['mandatory' => TRUE];
				break;

			case 'seasonOnly' :
			case 'seasonStart' :
			case 'seasonStop' :
				$d->field = 'select';
				break;

			case 'frequency' :
				$d->values = [
					Flow::W1 => s("Chaque semaine"),
					Flow::W2 => s("Toutes les 2 semaines"),
					Flow::W3 => s("Toutes les 3 semaines"),
					Flow::W4 => s("Toutes les 4 semaines"),
					Flow::M1 => s("1 fois par mois"),
				];
				$d->field = 'select';
				break;

			case 'toolsList' :
				$d->autocompleteDefault = fn(Flow $e) => $e['cTool'] ?? $e->expects(['cTool']);
				$d->autocompleteBody = function(\util\FormUi $form, Flow $e) {
					$e->expects([
						'action',
						'sequence' => ['farm']
					]);
					return [
						'action' => $e['action']['id'],
						'farm' => $e['sequence']['farm']['id']
					];
				};
				(new \farm\ToolUi())->query($d, TRUE);
				$d->group = ['wrapper' => 'toolsList'];
				break;

		}

		return $d;

	}
	
}
?>
