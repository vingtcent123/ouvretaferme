<?php
namespace farm;

class ToolUi {

	public function __construct() {

		\Asset::css('farm', 'tool.css');

	}

	public static function link(Tool $eTool, bool $newTab = FALSE): string {

		$eTool->expects(['id', 'name']);

		return '<a href="'.self::url($eTool).'" '.($newTab ? 'target="_blank"' : '').'>'.encode($eTool['name']).'</a>';

	}

	public static function url(Tool $eTool): string {

		$eTool->expects(['id']);

		return '/outil/'.$eTool['id'];

	}

	public static function manageUrl(Farm $eFarm, ?string $routineName = NULL): string {

		return '/farm/tool:manage?farm='.$eFarm['id'].''.($routineName ? '&routineName='.$routineName : '');

	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE) {

		$d->prepend = \Asset::icon('tools');
		$d->field = 'autocomplete';

		$d->placeholder ??= s("Tapez le nom du matériel...");
		$d->multiple = $multiple;
		$d->group += ['wrapper' => 'tool'];

		$d->autocompleteUrl = '/farm/tool:query';
		$d->autocompleteResults = function(Tool|\Collection $e) {
			return self::getAutocomplete($e);
		};

		$d->attributes = [
			'data-autocomplete-id' => 'tool'
		];

	}

	public static function getAutocomplete(Tool $eTool): array {

		\Asset::css('media', 'media.css');

		$item = self::getVignette($eTool, '3.5rem', '2.5rem');
		$item .= '<div>'.encode($eTool['name']).'</div>';

		return [
			'value' => $eTool['id'],
			'itemHtml' => $item,
			'itemText' => $eTool['name']
		];

	}

	public static function getVignette(Tool $eTool, string $width, string $height): string {

		$eTool->expects(['id', 'vignette']);

		$ui = new \media\ToolVignetteUi();

		$style = 'border-radius: var(--radius); ';

		if($eTool['vignette'] === NULL) {
			$style .= 'background-color: var(--background-light); color: var(--muted); font-size: '.(new \media\ToolVignetteUi())->getFactorSize(str_ends_with($width, '%') ? $height : $width, 0.45).'';
			$content = \Asset::icon('tools');
		} else {

			$format = $ui->convertToFormat(str_ends_with($width, '%') ? $height : $width);
			$style .= 'background-color: white; background-image: url('.$ui->getUrlByElement($eTool, $format).');';
			$content = '';

		}

		return '<div class="media-rectangle-view" style="'.$ui->getRectangleCss($width, $height).'; '.$style.'">'.$content.'</div>';

	}

	public function getList(\Collection $cTool): string {

		$h = '<div class="tool-list">';
			foreach($cTool as $eTool) {
				$h .= '<a href="'.\farm\ToolUi::url($eTool).'" class="tool-list-item">';
					$h .= \farm\ToolUi::getVignette($eTool, '2.7rem', '1.8rem');
					$h .= encode($eTool['name']);
				$h .= '</a>';
			}
		$h .= '</div>';

		return $h;

	}

	public function display(Farm $eFarm, Tool $eTool): \Panel {

		$h = '<div class="util-vignette">';

			$h .= self::getVignette($eTool, width: '12rem', height: '9rem');

			$h .= '<div class="util-action">';
				$h .= '<dl class="util-presentation util-presentation-1">';
		
					if($eTool['action']->notEmpty()) {
						$h .= '<dt>'.s("Intervention").'</dt>';
						$h .= '<dd>';
							$h .= encode($eTool['action']['name']);
						$h .= '</dd>';
					}
		
					if($eTool['stock']) {
						$h .= '<dt>'.s("En stock").'</dt>';
						$h .= '<dd>';
							$h .= $eTool['stock'];
						$h .= '</dd>';
					}
		
					if($eTool['comment'] !== NULL) {
						$h .= '<dt>'.s("Observations").'</dt>';
						$h .= '<dd>';
							$h .= encode($eTool['comment']);
						$h .= '</dd>';
					}
		
				$h .= '</dl>';
			$h .= '</div>';

		$h .= '</div>';

		return new \Panel(
			id: 'panel-tool-display',
			title: encode($eTool['name']),
			body: $h
		);

	}

	public function manage(\farm\Farm $eFarm, ?string $routineName, array $tools, \Collection $cTool, Tool $eToolNew, \Collection $cActionUsed, \Search $search): string {

		if(
			$cTool->empty() and
			$search->empty(['status']) and
			$tools[Tool::INACTIVE] === 0
		) {

			$h = '<h1>'.($routineName ? RoutineUi::getProperty($routineName, 'title') : s("Petit matériel")).'</h1>';
			$h .= '<div class="util-block-help">';
				$h .= ($routineName ? RoutineUi::getProperty($routineName, 'nothing') : s("Vous n'avez pas encore ajouté de petit matériel à votre ferme. Ajouter du petit matériel peut être très utile pour suivre les stocks et indiquer le matériel à utiliser pour les interventions !"));
			$h .= '</div>';

			$h .= '<h4>'.($routineName ? RoutineUi::getProperty($routineName, 'createTitle') : s("Ajouter un petit matériel")).'</h4>';

			$h .= $this->createForm($eToolNew, 'inline');

		} else {

			$h = '<div class="util-action">';
				$h .= '<h1>'.($routineName ? RoutineUi::getProperty($routineName, 'title') : s("Petit matériel")).'</h1>';
				$h .= '<div>';
					if($routineName === NULL) {
						$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#tool-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
					}
					$h .= '<a href="/farm/tool:create?farm='.$eFarm['id'].''.($routineName ? '&routineName='.$routineName : '').'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.($routineName ? RoutineUi::getProperty($routineName, 'createButton') : s("Nouveau matériel")).'</a>';
				$h .= '</div>';
			$h .= '</div>';

			if($routineName === NULL) {
				$h .= $this->getSearch($eFarm, $cActionUsed, $search);
			}

			if($tools[Tool::INACTIVE] > 0) {

				$h .= '<br/>';

				$h .= '<div class="tabs-item">';
					$h .= '<a href="'.self::manageUrl($eFarm, $routineName).$search->toQuery(['status'], '&').'" class="tab-item '.($search->get('status') === Tool::ACTIVE ? 'selected' : '').'"><span>'.($routineName ? RoutineUi::getProperty($routineName, 'tabActive') : s("Matériel activé")).' <small>('.$tools[Tool::ACTIVE].')</small></span></a>';
					$h .= '<a href="'.self::manageUrl($eFarm, $routineName).$search->toQuery(['status'], '&').'&status='.Tool::INACTIVE.'" class="tab-item '.($search->get('status') === Tool::INACTIVE ? 'selected' : '').'"><span>'.($routineName ? RoutineUi::getProperty($routineName, 'tabInactive') : s("Matériel désactivé")).' <small>('.$tools[Tool::INACTIVE].')</small></span></a>';
				$h .= '</div>';

			}

			if($cTool->empty()) {

				$h .= '<div class="util-info">';
					$h .= ($routineName ? RoutineUi::getProperty($routineName, 'empty') : s("Vous n'avez pas encore ajouté de matériel."));
				$h .= '</div>';

			} else {

				$h .= '<div class="util-overflow-sm">';

					$h .= '<table class="tr-even">';
						$h .= '<thead>';
							$h .= '<tr>';
								$h .= '<th></th>';
								$h .= '<th>'.s("Nom").'</th>';

								if($routineName) {
									$h .= RoutineUi::getProperty($routineName, 'th')();
								} else {
									$h .= '<th>'.s("Intervention").'</th>';
								}

								$h .= '<th class="text-center">'.s("En stock").'</th>';
								$h .= '<th></th>';
							$h .= '</tr>';
						$h .= '</thead>';

						$h .= '<tbody>';

						foreach($cTool as $eTool) {

							$h .= '<tr>';
								$h .= '<td class="util-manage-vignette">';
									$h .= (new \media\ToolVignetteUi())->getCamera($eTool, width: '4.8rem', height: '3.6rem');
								$h .= '</td>';
								$h .= '<td>';
									$h .= ToolUi::link($eTool);
									if($eTool['routineName'] !== NULL) {
										$summary = RoutineUi::getProperty($eTool['routineName'], 'summary')($eTool);
										if($summary) {
											$h .= '<div class="color-muted">';
												$h .= $summary;
											$h .= '</div>';
										}
									}
								$h .= '</td>';

								if($routineName) {
									$h .= RoutineUi::getProperty($routineName, 'td')($eTool);
								} else {

									$h .= '<td>';
										if($eTool['action']->empty()) {
										   $action = '<i>'.s("Matériel polyvalent").'</i>';
										} else {
											$action = encode($eTool['action']['name']);
										}
										$h .= $eTool->quick('action', $action);
									$h .= '</td>';

								}
								$h .= '<td class="text-center">';
									$h .= $eTool->quick('stock', $eTool['stock'] ?? '-');
								$h .= '</td>';
								$h .= '<td class="text-end">';

									$h .= '<a class="btn btn-outline-secondary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
									$h .= '<div class="dropdown-list">';
										$h .= '<div class="dropdown-title">'.encode($eTool['name']).'</div>';

											$h .= '<a href="/farm/tool:update?id='.$eTool['id'].'" class="dropdown-item">';
												$h .= ($routineName ? RoutineUi::getProperty($routineName, 'dropdownUpdate') : s("Modifier le matériel"));
											$h .= '</a> ';

											$h .= match($eTool['status']) {

												Tool::ACTIVE => '<a data-ajax="/farm/tool:doUpdateStatus" post-id="'.$eTool['id'].'" post-status="'.Tool::INACTIVE.'" class="dropdown-item">'.($routineName ? RoutineUi::getProperty($routineName, 'dropdownDisable') : s("Désactiver le matériel")).'</a>',
												Tool::INACTIVE => '<a data-ajax="/farm/tool:doUpdateStatus" post-id="'.$eTool['id'].'" post-status="'.Tool::ACTIVE.'" class="dropdown-item">'.($routineName ? RoutineUi::getProperty($routineName, 'dropdownEnable') : s("Réactiver le matériel")).'</a>'

											};

											$h .= '<div class="dropdown-divider"></div>';

											$h .= '<a data-ajax="/farm/tool:doDelete" data-confirm="'.($routineName ? RoutineUi::getProperty($routineName, 'deleteConfirm') : s("Supprimer ce matériel ?")).'" post-id="'.$eTool['id'].'" class="dropdown-item">';
												$h .= ($routineName ? RoutineUi::getProperty($routineName, 'dropdownDelete') : s("Supprimer le matériel"));
											$h .= '</a>';

									$h .= '</div>';

								$h .= '</td>';
							$h .= '</tr>';
						}
						$h .= '</tbody>';
					$h .= '</table>';

				$h .= '</div>';

			}

		}

		return $h;

	}

	public function getSearch(\farm\Farm $eFarm, \Collection $cActionUsed, \Search $search): string {

		$h = '<div id="tool-search" class="util-block-search '.($search->empty(['status']) ? 'hide' : '').'">';

			$form = new \util\FormUi();

			$h .= $form->openAjax('/farm/tool:manage', ['method' => 'get', 'id' => 'form-search']);

				$h .= '<div>';

					$h .= $form->hidden('farm', $eFarm['id']);
					$h .= $form->text('name', $search->get('name'), ['placeholder' => s("Nom")]);
					$h .= $form->select('action', $cActionUsed, $search->get('action'), ['placeholder' => s("Intervention")]);

					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.self::manageUrl($eFarm).'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';

				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function create(Tool $eTool): \Panel {

		return new \Panel(
			title: $eTool->getStandaloneRoutine('createTitle') ?? s("Ajouter un nouveau matériel"),
			body: $this->createForm($eTool, 'panel'),
			close: 'reload'
		);

	}

	public function createForm(Tool $eTool, string $origin): string {

		$eTool->expects([
			'farm',
			'routineName'
		]);

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/tool:doCreate', ['data-ajax-origin' => $origin]);

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eTool['farm']['id']);
			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eTool['farm'])
			);

			$h .= $form->dynamicGroup($eTool, 'name*', function(\PropertyDescriber $d) use ($eTool) {
				$d->label = $eTool->getStandaloneRoutine('nameField') ?? $d->label;
			});

			if($eTool->isStandaloneRoutine()) {
				$h .= $form->hidden('action', $eTool['action']);
			} else {
				$h .= $form->dynamicGroup($eTool, 'action');
			}

			$h .= (new RoutineUi())->getRoutinesGroup($form, $eTool, [$eTool['routineName']]);
			$h .= $form->dynamicGroups($eTool, ['stock', 'comment']);

			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return $h;

	}

	public function update(Tool $eTool, array $routines): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/tool:doUpdate');

			$h .= $form->hidden('id', $eTool['id']);

			$h .= $form->dynamicGroup($eTool, 'name', function(\PropertyDescriber $d) use ($eTool) {
				$d->label = $eTool->getStandaloneRoutine('nameField') ?? $d->label;
			});

			if($eTool->isStandaloneRoutine() === FALSE) {
				$h .= $form->dynamicGroup($eTool, 'action');
			}

			$h .= (new RoutineUi())->getRoutinesGroup($form, $eTool, array_keys($routines));
			$h .= $form->dynamicGroups($eTool, ['stock', 'comment']);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: $eTool->getStandaloneRoutine('updateTitle') ?? s("Modifier le matériel"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Tool::model()->describer($property, [
			'name' => s("Nom du matériel"),
			'action' => s("Lier ce matériel à une intervention"),
			'stock' => s("Quantité en stock à la ferme"),
			'comment' => s("Observations"),
		]);

		switch($property) {

			case 'id' :
				$d->autocompleteBody = function(\util\FormUi $form, Tool $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']->empty() ? NULL : $e['farm']['id']
					];
				};
				(new ToolUi())->query($d);
				break;

			case 'action' :
				\Asset::js('farm', 'routine.js');
				$d->placeholder = s("Non, matériel polyvalent");
				$d->values = fn(Tool $e) => $e['cAction'] ?? $e->expects(['cAction']);
				$d->attributes = [
					'onchange' => 'Routine.refresh(this)'
				];
				break;

			case 'stock' :
				$d->append = s("unité(s)");
				break;

		}

		return $d;

	}


}
?>
