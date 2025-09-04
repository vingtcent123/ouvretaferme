<?php
namespace sequence;

class FlowUi {

	public function __construct() {
		\Asset::css('sequence', 'flow.css');
		\Asset::js('sequence', 'flow.js');
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

								$h .= '<div class="flow-timeline flow-timeline-'.$field.' '.($newWeek ? 'flow-timeline-new-week' : '').'" data-checked="0" data-week="'.$week.'">';

									if($write) {
										$h .= '<a class="flow-timeline-item" onclick="Flow.changeWeekSelection(this)">';
									} else {
										$h .= '<div class="flow-timeline-item">';
									}

										if($newWeek) {
											$h .= '<div class="flow-timeline-circle" data-dropdown="bottom-start">'.s("s{value}", $week).'</div>';
										}

									if($write) {
										$h .= '</a>';
									} else {
										$h .= '</div>';
									}

									if($write) {
										$h .= '<a class="flow-timeline-week" onclick="Flow.changeWeekSelection(this)">';
									} else {
										$h .= '<div class="flow-timeline-week">';
									}

										if($newWeek) {
											$h .= \util\DateUi::weekToDays($currentYear.'-W'.sprintf('%02d', $week), TRUE, FALSE);
										}

									if($write) {
										$h .= '</a>';
									} else {
										$h .= '</div>';
									}


									$h .= '<div class="flow-timeline-update">';

										if($write) {
											if($field !== 'stop') {
												$h .= '<label class="flow-timeline-select batch-item">';
													$h .= $this->getBatchCheckbox($form, $eFlow, $week);
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

												$h .= '<a href="/sequence/flow:update?id='.$eFlow['id'].'" class="flow-timeline-text">';

													$h .= \farm\ActionUi::text($eFlow).' ';
													$h .= self::getFlowComplement($eFlow);
													$h .= new \sequence\FlowUi()->getMore($eFlow);

													if($eFlow['frequency'] !== NULL) {
														$h .= ' '.\Asset::icon('arrow-right').' '.$this->p('frequency')->values[$eFlow['frequency']];
													}

												$h .= '</a>';

												$h .= '<div>';
													if($write and $eFlow->canWrite()) {
														if($firstOfWeek === FALSE) {
															$h .= '<a data-ajax="/sequence/flow:doPosition" post-id="'.$eFlow['id'].'" post-positions="'.encode(json_encode($this->changeDirection($flows, $eFlow, 'up'))).'" class="flow-timeline-update-up">'.\Asset::icon('arrow-up-circle').'</a> ';
														}
														if($lastOfWeek === FALSE) {
															$h .= '<a data-ajax="/sequence/flow:doPosition" post-id="'.$eFlow['id'].'" post-positions="'.encode(json_encode($this->changeDirection($flows, $eFlow, 'down'))).'" class="flow-timeline-update-down">'.\Asset::icon('arrow-down-circle').'</a>';
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

	public function getFlowComplement(Flow $eFlow): string {

		$h = '';

		foreach($eFlow['cMethod?']() as $eMethod) {
			$h .= ' <span class="flow-method-name">'.encode($eMethod['name']).'</span> ';
		}

		return $h;

	}

	protected function getBatchCheckbox(\util\FormUi $form, Flow $eFlow, string $week): string {

		return $form->inputCheckbox('batch[]', $eFlow['id'], [
			'data-week' => $week,
			'oninput' => 'Flow.changeSelection()'
		]);

	}

	public function getBatch(): string {

		$menu = '<div class="batch-menu-planned">';
			$menu .= '<a data-dropdown="top-start" class="batch-one-item">';
				$menu .= \Asset::icon('watch');
				$menu .= '<span>'.s("Planifier").'</span>';
			$menu .= '</a>';
			$menu .= $this->getBatchPlanned('batch-one-form');
		$menu .= '</div>';

		$menu .= '<a class="batch-one-item batch-menu-update">'.\Asset::icon('gear-fill').'<span>'.s("Modifier").'</span></a>';

		$menu .= '<a data-ajax-submit="/sequence/flow:doDeleteCollection" class="batch-one-item" data-confirm="'.s("Confirmer la suppression de cette intervention ?").'">'.\Asset::icon('trash').'<span>'.s("Supprimer").'</span></a>';

		$h = \util\BatchUi::one($menu);

		$menu = '<a data-dropdown="top-start" class="batch-menu-item">';
			$menu .= \Asset::icon('watch');
			$menu .= '<span>'.s("Planifier").'</span>';
		$menu .= '</a>';
		$menu .= $this->getBatchPlanned('batch-group-form');

		$danger = '<a data-ajax-submit="/sequence/flow:doDeleteCollection" data-confirm="'.s("Confirmer la suppression de ces interventions ?").'" class="batch-menu-item batch-menu-item-danger">';
			$danger .= \Asset::icon('trash');
			$danger .= '<span>'.s("Supprimer").'</span>';
		$danger .= '</a>';

		$h .= \util\BatchUi::group($menu, $danger, title: s("Pour les interventions sélectionnées"));

		return $h;

	}

	protected function getBatchPlanned(string $formId) {

		$h = '<div class="dropdown-list bg-secondary">';
			$h .= '<a data-ajax-submit="/sequence/flow:doIncrementWeekCollection" data-ajax-target="#'.$formId.'" class="dropdown-item" post-increment="-1">'.s("Une semaine plus tôt").'</a>';
			$h .= '<a data-ajax-submit="/sequence/flow:doIncrementWeekCollection" data-ajax-target="#'.$formId.'" class="dropdown-item" post-increment="1">'.s("Une semaine plus tard").'</a>';
			$h .= '<a data-ajax-submit="/sequence/flow:incrementWeekCollection" data-ajax-method="get" data-ajax-target="#'.$formId.'" class="dropdown-item">'.s("Décaler davantage").'</a>';
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
						$h .= $form->inputCheckbox(attributes: ['onclick' => 'Flow.changeAllSelection(this)', 'class' => 'batch-all']);
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

			$h .= '<p class="util-empty">';
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
			$h .= '<a class="btn btn-outline-primary" href="/sequence/flow:create?sequence='.$eSequence['id'].'">'.\Asset::icon('plus-circle').' '.s("Nouvelle intervention").'</a>';
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
								$h .= \sequence\CropUi::getYield($eFlow, 'harvestOutPeriod', 'harvestUnit', []);
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

					[$major, $minor] = new \sequence\FlowUi()->getFertilizer($eFlow);

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

		return new \farm\RoutineUi()->getSeparateFertilizer($eFlow['fertilizer']);

	}

	public function getTools(\series\Task|Flow $eFlow): string {

		$eFlow->expects(['cTool?']);

		$cTool = $eFlow['cTool?']();

		$h = '';

		if($cTool->notEmpty()) {

			$h = '<div class="flow-timeline-tools">';
				$h .= new \farm\ToolUi()->getList($cTool);
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

	public function create(Flow $eFlow, \Collection $cAction): \Panel {

		$form = new \util\FormUi();

		$eFlow->merge([
			'crop' => new Crop(),
			'yearOnly' => 0,
			'yearStart' => 0,
			'yearStop' => 0,
			'frequency' => Flow::W1,
			'action' => new \farm\Action(),
			'cTool?' => fn() => new \Collection(),
			'hasTools' => new \Collection(),
			'cMethod?' => fn() => new \Collection(),
			'hasMethods' => new \Collection(),
		]);

		$eSequence = $eFlow['sequence'];

		if($eSequence['cCrop']->count() > 1) {
			$cAction->map(fn($eAction) => $eAction['disabled'] = ($eAction['fqn'] === ACTION_RECOLTE));
		}

		$h = '';

		$h .= $form->openAjax('/sequence/flow:doCreate', ['id' => 'flow-create', 'autocomplete' => 'off']);

			$h .= $form->hidden('sequence', $eSequence['id']);

			$h .= $this->getCropField($form, $eSequence, $eFlow);

			$h .= $form->dynamicGroup($eFlow, 'action', function($d) use($cAction) {
				$d->values = $cAction;
			});

			$h .= '<div id="flow-create-fertilizer">';
				$h .= $this->getFertilizerField($form, $eFlow);
			$h .= '</div>';

			if($eFlow['sequence']['cycle'] === Sequence::PERENNIAL) {
				$h .= $this->getSeasonField($form, $eFlow);
			}

			$h .= $this->getPeriodField($form, $eFlow);

			$h .= $this->getMethodsGroup($form, $eFlow);
			$h .= $this->getToolsGroup($form, $eFlow);

			$h .= $form->dynamicGroup($eFlow, 'description');


			$h .= $form->group(
				content: $form->submit(s("Ajouter l'intervention"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-flow-create',
			title: s("Ajouter une intervention"),
			subTitle: SequenceUi::getPanelHeader($eSequence),
			body: $h
		);

	}

	public function update(Sequence $eSequence, Flow $eFlow, \Collection $cAction): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/sequence/flow:doUpdate', ['id' => 'flow-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eFlow['id']);

			$h .= $this->getCropField($form, $eSequence, $eFlow);

			$h .= $form->dynamicGroup($eFlow, 'action', function($d) use($cAction) {
				$d->values = $cAction;
			});

			if($eFlow['action']['fqn'] === ACTION_FERTILISATION) {
				$h .= $this->getFertilizerField($form, $eFlow);
			}

			if($eFlow['sequence']['cycle'] === Sequence::PERENNIAL) {
				$h .= $this->getSeasonField($form, $eFlow);
			}

			$h .= $this->getPeriodField($form, $eFlow);

			$h .= $this->getMethodsGroup($form, $eFlow);
			$h .= $this->getToolsGroup($form, $eFlow);

			$h .= $form->dynamicGroup($eFlow, 'description');


			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-flow-update',
			title: s("Modifier une intervention"),
			subTitle: SequenceUi::getPanelHeader($eSequence),
			body: $h,
		);

	}

	public function getMethodsGroup(\util\FormUi $form, Flow $eFlow): string {

		$h = '<div data-ref="methods" data-farm="'.$eFlow['sequence']['farm']['id'].'">';
			if($eFlow['hasMethods']->notEmpty()) {
				$h .= $form->dynamicGroup($eFlow, 'methods');
			}
		$h .= '</div>';

		return $h;

	}

	public function getToolsGroup(\util\FormUi $form, Flow $eFlow): string {

		$h = '<div data-ref="tools" data-farm="'.$eFlow['sequence']['farm']['id'].'">';
			if($eFlow['hasTools']->notEmpty()) {
				$h .= $form->dynamicGroup($eFlow, 'tools');
			}
		$h .= '</div>';

		return $h;

	}

	public function updateIncrementWeekCollection(\Collection $cTask): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/sequence/flow:doIncrementWeekCollection', ['autocomplete' => 'off']);

			$h .= $form->group(
				p("Intervention", "Interventions", $cTask->count()),
				$this->getFlowsField($form, $cTask),
				['wrapper' => 'tasks']
			);

			$h .= $form->group(
				s("Décaler de ..."),
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
			id: 'panel-flow-increment',
			title: s("Décaler"),
			body: $h
		);

	}

	public function getFlowsField(\util\FormUi $form, \Collection $cFlow): string {

		$display = ($cFlow->count() > 1) ? 'checkbox' : 'hidden';

		$h = '<table class="stick-xs">';

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
						$h .= '<td>'.new SequenceUi()->getTextualWeek($eFlow, $eFlow['action']).'</td>';
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

	public function getFertilizerField(\util\FormUi $form, Flow $eFlow): string {

		$eFlow->expects(['action']);

		return $form->group(
			self::p('fertilizer')->label,
			new \farm\RoutineUi()->getFieldFertilizer($form, 'fertilizer', $eFlow['fertilizer'] ?? NULL),
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
				$this->getWeekField($form, $eFlow, fn($name) => $name.'Only').
				'<div class="field-action"><a data-action="flow-period-interval">'.s("Répéter l'intervention plusieurs fois dans la saison").'</a></div>',
				['wrapper' => 'weekOnly yearOnly']
			);
		$h .= '</div>';

		$h .= '<div id="flow-period-interval" style="'.$styleInterval.'">';

			$h .= $form->group(
				self::p('weekStart')->label,
				$this->getWeekField($form, $eFlow, fn($name) => $name.'Start'),
				['wrapper' => 'weekStart yearStart']
			);

			$h .= $form->group(
				self::p('weekStop')->label,
				$this->getWeekField($form, $eFlow, fn($name) => $name.'Stop').
				'<div class="field-action"><a data-action="flow-period-only">'.s("Ne pas répéter l'intervention dans la saison").'</a></div>',
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

		$values = function($d) use($eFlow) {

			$d->values = [];

			$lastSeason = $eFlow['sequence']['perennialLifetime'] ?? SequenceSetting::MAX_SEASON_STOP;

			for($season = 1; $season <= $lastSeason; $season++) {
				$d->values[$season] = s("Saison {value}", $season);
			}

		};

		$h = $form->hidden('season', $season, ['id' => 'flow-season']);

		$h .= '<div id="flow-season-only" style="'.$styleOnly.'">';
			$h .= $form->group(
				self::p('seasonOnly')->label,
				$form->dynamicField($eFlow, 'seasonOnly', $values).
				'<div class="field-action"><a data-action="flow-season-interval">'.s("Répéter l'intervention sur plusieurs saisons").'</a></div>'
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
				'<div class="field-action"><a data-action="flow-season-only">'.s("Ne pas répéter l'intervention sur plusieurs saisons").'</a></div>'
			);

		$h .= '</div>';

		return $h;

	}

	public function getWeekField(\util\FormUi $form, Flow $eFlow, \Closure $name): string {

		$h = '<div class="flow-write-week">';
			$h .= $form->dynamicField($eFlow, $name('week'));
			if($eFlow['sequence']['cycle'] === Sequence::ANNUAL) {
				$h .= $form->dynamicField($eFlow, $name('year'));
			}
		$h .= '</div>';

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Flow::model()->describer($property, [
			'crop' => s("Production"),
			'action' => s("Intervention"),
			'method' => s("Méthode de travail"),
			'fertilizer' => s("Apports"),
			'description' => s("Observation sur l'intervention"),
			'seasonOnly' => s("Quelle saison pour cette intervention ?"),
			'seasonStart' => s("Première saison de l'intervention"),
			'seasonStop' => s("Dernière saison de l'intervention"),
			'weekOnly' => s("Semaine de l'intervention"),
			'weekStart' => s("Début de l'intervention"),
			'weekStop' => s("Fin de l'intervention"),
			'frequency' => s("Fréquence de l'intervention"),
			'methods' => s("Méthodes de travail"),
			'tools' => s("Matériel nécessaire")
		]);

		switch($property) {

			case 'description' :
				$d->after = \util\FormUi::info(s("Le commentaire est facultatif et permet d'apporter des précisions sur l'intervention à réaliser."));
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
							'data-fqn' => $eAction['fqn'],
							'data-farm' => $eAction['farm']['id'],
						];
					}
				];
				break;

			case 'weekOnly' :
			case 'weekStart' :
			case 'weekStop' :
				$d->field = fn(\util\FormUi $form, Flow $e, string $field) => $form->week($field, isset($e[$property]) ? date('Y').'-W'.sprintf('%02d', $e[$property]) : NULL, ['withYear' => FALSE]);
				break;

			case 'yearOnly' :
			case 'yearStart' :
			case 'yearStop' :
				$d->field = 'select';
				$d->values = self::getYearsSelect();
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

			case 'methods' :
				$d->autocompleteDefault = fn(Flow $e) => ($e['cMethod?'] ?? $e->expects(['cMethod?']))();
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
				new \farm\MethodUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'methods'];
				break;

			case 'tools' :
				$d->autocompleteDefault = fn(Flow $e) => ($e['cTool?'] ?? $e->expects(['cTool?']))();
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
				new \farm\ToolUi()->query($d, TRUE);
				$d->group = ['wrapper' => 'tools'];
				break;

		}

		return $d;

	}

	public static function getYearsSelect(): array {
		return [
			-1 => s("Année « N-1 »"),
			0 => s("Année « N »"),
			1 => s("Année « N+1 »")
		];
	}
	
}
?>
