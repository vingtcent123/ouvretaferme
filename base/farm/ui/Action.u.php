<?php
namespace farm;

class ActionUi {

	public function __construct() {
		\Asset::css('farm', 'action.css');
	}

	public static function getColorCircle(Action $eAction): string {

		$eAction->expects(['color']);

		\Asset::css('farm', 'action.css');

		return '<div class="action-color-circle" style="background-color: '.$eAction['color'].'"></div>';

	}

	public static function getShort(Action $eAction): string {

		$eAction->expects(['short', 'name']);

		return encode($eAction['short'] ?? strtoupper(mb_substr($eAction['name'], 0, 1)));

	}

	public static function getPanelHeader(Action $eAction): string {

		return '<div class="panel-header-subtitle">'.self::getColorCircle($eAction, '2rem').'  '.encode($eAction['name']).'</div>';

	}

	public static function text(\sequence\Flow|\series\Task $e, \Collection $cActionMain = new \Collection()): string {

		if($e instanceof \sequence\Flow) {

			$e['variety'] = new \plant\Variety();

			$e->expects(['sequence']);

		} else {
			$e->expects(['series']);
		}

		$e->expects([
			'plant',
			'action' => ['name'],
			'variety',
			'description'
		]);

		$ePlant = $e['plant'];

		$eAction = $e['action'];
		$eAction->expects(['name']);

		\Asset::css('farm', 'action.css');

		$h = '<span class="action-text">';

		if($ePlant->empty()) {

			$h .= encode($eAction['name']);

			if(
				($e instanceof \series\Task and $e['series']->notEmpty()) or
				($e instanceof \sequence\Flow and $e['sequence']->notEmpty())
			) {
				$h .= ' <span class="action-name">'.s("PARTAGÉ").'</span>';
			}

		} else {

			$ePlant->expects(['name']);

			$plant = '<span class="action-name">'.encode($ePlant['name']).'</span>';

			if($cActionMain->notEmpty()) {
				$plant .= ' '.\sequence\CropUi::start($e instanceof \series\Task ? $e['cultivation'] : $e['crop'], $cActionMain).' ';
			}

			$arguments = [
				'action' => encode($eAction['name']),
				'plant' => $plant
			];

			if($eAction['fqn'] === 'other') {

				if($e['description'] === NULL) {
					$h .= s("{action} de {plant}", $arguments);
				} else {
					$h .= $arguments['plant'];
				}

			} else {
				$h .= s("{action} de {plant}", $arguments);
			}

		}

		$h .= '</span>';

		return $h;

	}

	public function getManageTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.FarmUi::urlSettingsProduction($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Interventions");
			$h .= '</h1>';

			$h .= '<div>';
				$h .= '<a href="/farm/action:create?farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle action").'</a>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getManage(\farm\Farm $eFarm, \Collection $cAction, \Collection $cCategory): string {

		$h = '<div class="util-block-gradient">';
			$h .= '<p>';
				$h .= s("Plusieurs interventions sont activées par défaut et ne peuvent pas être modifiées ou supprimées, mais vous pouvez en ajouter d'autres afin de refléter fidèlement votre contexte de production.").' ';
				if($cCategory->count() === 1) {
					$h .= s("Vous avez classé toutes les interventions dans une seule catégorie, nommée {value}.", '<u>'.encode($cCategory->first()['name']).'</u>');
				} else {
					$h .= s("Les interventions sont classées en plusieurs catégories distinctes : {list}.", ['list' => implode(', ', array_map(fn($value) => '<u>'.encode($value).'</u>', $cCategory->getColumn('name')))]);
				}
				$h .= '</p>';
				$h .= '<a href="/farm/category:manage?farm='.$eFarm['id'].'" class="btn btn-outline-secondary">'.s("Personnaliser les catégories ").'</a>';
		$h .= '</div>';

		$methodHelp = $cAction->contains(fn($eAction) => $eAction['cMethod']->notEmpty()) ? '' : '&help';

		$h .= '<div class="stick-xs">';

		$h .= '<table class="tbody-even">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>'.s("Nom").'</th>';
					$h .= '<th class="hide-xs-down">'.s("Catégories").'</th>';
					$h .= '<th>'.s("Méthodes de travail").'</th>';
					$h .= '<th class="text-center hide-xs-down">'.s("Interventions").'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			foreach($cAction as $eAction) {

				$categories = array_map(function($category) use($cCategory) {
					return encode($cCategory[$category]['name']);
				}, $eAction['categories']);

				$h .= '<tbody>';

					$h .= '<tr>';
						$h .= '<td>';
							$h .= \farm\ActionUi::getColorCircle($eAction);
							$h .= encode($eAction['name']);
							$h .= '<div class="action-manage-categories hide-sm-up">'.implode(' / ', $categories).'</div>';
						$h .= '</td>';
						$h .= '<td class="hide-xs-down">';
							$h .= implode('<br/>', $categories);
						$h .= '</td>';
						$h .= '<td>';
							$h .= '<div class="action-manage-methods">';
								foreach($eAction['cMethod'] as $eMethod) {
									$h .= '<a data-dropdown="bottom-start" class="dropdown-toggle btn btn-sm btn-primary">'.encode($eMethod['name']).'</a> ';
									$h .= '<div class="dropdown-list">';
										$h .= '<div class="dropdown-title">'.encode($eMethod['name']).'</div>';

											$h .= '<a class="dropdown-item" '.$eMethod->getQuickAttributes('name').'>'.s("Renommer la méthode").'</a>';

											$h .= '<div class="dropdown-divider"></div>';

											$h .= '<a data-ajax="/farm/method:doDelete" data-confirm="'.s("Supprimer définitivement cette méthode ?").'" post-id="'.$eMethod['id'].'" class="dropdown-item">'.s("Supprimer la méthode").'</a>';

									$h .= '</div>';
								}
								$h .= '<a href="/farm/method:create?action='.$eAction['id'].$methodHelp.'" class="btn btn-sm btn-outline-primary">'.\Asset::icon('plus').'</a>';
							$h .= '</div>';
						$h .= '</td>';
						$h .= '<td class="text-center hide-xs-down">';
							if($eAction['tasks'] !== NULL) {
								$h .= '<a href="/series/analyze:tasks?id='.$eFarm['id'].'&action='.$eAction['id'].'">'.$eAction['tasks'].'</a>';
							} else {
								$h .= '/';
							}
						$h .= '</td>';
						$h .= '<td class="text-end" style="white-space: nowrap">';

							$h .= '<a class="btn btn-outline-secondary dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<div class="dropdown-title">'.encode($eAction['name']).'</div>';

									$h .= '<a href="/farm/action:update?id='.$eAction['id'].'" class="dropdown-item">'.s("Modifier l'intervention").'</a>';
									$h .= '<a href="/farm/method:create?action='.$eAction['id'].$methodHelp.'" class="dropdown-item">'.s("Ajouter une méthode de travail").'</a>';

									$h .= '<div class="dropdown-divider"></div>';

									if($eAction->isProtected() === FALSE) {
										$h .= '<a data-ajax="/farm/action:doDelete" data-confirm="'.s("Supprimer cette intervention ?").'" post-id="'.$eAction['id'].'" class="dropdown-item">';
											$h .= s("Supprimer l'intervention");
										$h .= '</a>';
									} else {
										$h .= '<div class="dropdown-item disabled">';
											$h .= \Asset::icon('lock-fill').' '.s("Supprimer l'intervention");
										$h .= '</div>';
									}

							$h .= '</div>';

						$h .= '</td>';
					$h .= '</tr>';
				$h .= '</tbody>';

			}

		$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function create(\farm\Farm $eFarm, \Collection $cCategory): \Panel {

		$eAction = new Action([
			'cCategory' => $cCategory
		]);

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/action:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->dynamicGroups($eAction, ['name*', 'categories*', 'color']);
			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-action-create',
			title: s("Ajouter un nouveau type d'intervention"),
			body: $h,
			close: 'reload'
		);

	}

	public function update(Action $eAction): \Panel {

		$eAction->expects(['id', 'fqn', 'cCategory']);

		$form = new \util\FormUi();

		$h = $form->openAjax('/farm/action:doUpdate');

			$h .= $form->hidden('id', $eAction['id']);

			if($eAction->isProtected() === FALSE) {
				$properties = ['name', 'categories', 'color'];
			} else {
				$properties = ['color'];
			}

			$h .= $form->dynamicGroups($eAction, $properties);
			$h .= '<div class="action-update-cultivation">';
				$h .= $form->dynamicGroup($eAction, 'pace');
				if($eAction->isProtected() === FALSE) {
					$h .= $form->dynamicGroup($eAction, 'soil');
				}
			$h .= '</div>';
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-action-update',
			title: s("Modifier une intervention"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Action::model()->describer($property, [
			'name' => s("Nom de l'intervention"),
			'short' => s("Raccourci du nom de l'intervention"),
			'fqn' => s("Nom qualifié"),
			'color' => s("Couleur"),
			'categories' => s("Catégories"),
			'pace' => s("Calcul de la productivité de l'intervention"),
			'soil' => s("Tenir compte de cette intervention pour calculer le début ou la fin de l'assolement des séries"),
			'series' => s("Activer cette intervention dans les séries")
		]);

		switch($property) {

			case 'categories' :
				$d->field = function(\util\FormUi $form, Action $e) {

					$cCategory = $e['cCategory'] ?? $e->expects(['cCategory']);

					$h = $form->checkboxes('categories[]', $cCategory, $e['categories'] ?? [], attributes: [
							'all' => TRUE,
							'callbackCheckboxAttributes' => fn($eCategory) => [
								'data-fqn' => $eCategory['fqn'],
							]
						]);

					return $h;

				};
				$d->attributes['mandatory'] = TRUE;
				break;

			case 'pace' :
				$d->values = [
					Action::BY_AREA => s("En fonction de la surface cultivée"),
					Action::BY_HARVEST => s("En fonction de la quantité récoltée"),
					Action::BY_PLANT => s("En fonction du nombre de plants"),
				];
				$d->placeholder = s("Non pertinent");
				$d->labelAfter = \util\FormUi::info(s("La productivité n'est calculée que pour les interventions réalisées au sein d'une série."));
				break;

			case 'soil' :
				$d->field = 'yesNo';
				$d->after = \util\FormUi::info(s("Si vous modifiez ce paramètre, seul les plans d'assolement des saisons {value} et suivantes de votre ferme seront mis à jour.", currentYear()));
				break;

			case 'short' :
				$d->after = \util\FormUi::info(s("Une seule lettre affichée dans le plan d'assolement, réfléchissez bien !"));
				$d->default = fn($e) => $e['short'] ?? mb_substr($e['name'], 0, 1);
				break;

			case 'color' :
				$d->attributes['emptyColor'] = new ActionModel()->getDefaultValue('color');
				break;

		}

		return $d;

	}


}
?>
