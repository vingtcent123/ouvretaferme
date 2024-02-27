<?php
namespace farm;

class ActionUi {

	public function __construct() {
		\Asset::css('farm', 'action.css');
	}

	public static function getColorCircle(Action $eAction): string {

		$eAction->expects(['color']);

		return '<div class="action-color-circle" style="background-color: '.$eAction['color'].'"></div>';

	}

	public static function getIcon(Action $eAction): string {

		$eAction->expects(['color']);

		return '<div class="action-icon" style="background-color: '.$eAction['color'].'">'.self::getShort($eAction).'</div>';

	}

	public static function getShort(Action $eAction): string {

		$eAction->expects(['short', 'name']);

		return encode($eAction['short'] ?? strtoupper(mb_substr($eAction['name'], 0, 1)));

	}

	public static function text(\production\Flow|\series\Task $e, \Collection $cActionMain = new \Collection()): string {

		if($e instanceof \production\Flow) {

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
				($e instanceof \production\Flow and $e['sequence']->notEmpty())
			) {
				$h .= ' <span class="action-name">'.s("PARTAGÉ").'</span>';
			}

		} else {

			$ePlant->expects(['name']);

			$plant = '<span class="action-name">'.encode($ePlant['name']).'</span>';

			if($cActionMain->notEmpty()) {
				$plant .= ' '.\production\CropUi::start($e instanceof \series\Task ? $e['cultivation'] : $e['crop'], $cActionMain).' ';
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

	public function manage(\farm\Farm $eFarm, \Collection $cAction, \Collection $cCategory): string {

		$h = '<div class="util-action">';

			$h .= '<h1>'.s("Les interventions").'</h1>';

			$h .= '<div>';
				$h .= '<a href="/farm/action:create?farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle action").'</a>';
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<div class="util-block-gradient">';
			$h .= '<p>';
				$h .= s("Plusieurs interventions sont activées par défaut et ne peuvent pas être modifiées ou supprimées, mais vous pouvez en ajouter d'autres afin de refléter fidèlement votre contexte de production.").' ';
				if($cCategory->count() === 1) {
					$h .= s("Vous avez classé toutes les interventions dans une seule catégorie, nommée {value}.", '<u>'.$cCategory->first()['name'].'</u>');
				} else {
					$h .= s("Les interventions sont classées en plusieurs catégories distinctes : {list}.", ['list' => implode(', ', array_map(fn($value) => '<u>'.encode($value).'</u>', $cCategory->getColumn('name')))]);
				}
				$h .= '</p>';
				$h .= '<a href="/farm/category:manage?farm='.$eFarm['id'].'" class="btn btn-outline-secondary">'.s("Personnaliser les catégories ").'</a>';
		$h .= '</div>';

		$h .= '<table class="tr-bordered">';
			$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th>'.self::p('name')->label.'</th>';
					$h .= '<th>'.s("Catégories").'</th>';
					$h .= '<th class="text-center hide-xs-down">'.s("Interventions").'</th>';
					$h .= '<th></th>';
				$h .= '</tr>';
			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cAction as $eAction) {

				$categories = array_map(function($category) use ($cCategory) {
					return $cCategory[$category]['name'];
				}, $eAction['categories']);

				$h .= '<tr>';
					$h .= '<td>';
						$h .= \farm\ActionUi::getColorCircle($eAction);
						$h .= encode($eAction['name']);
					$h .= '</td>';
					$h .= '<td>';
						$h .= implode('<br/>', $categories);
					$h .= '</td>';
					$h .= '<td class="text-center hide-xs-down">';
						if($eAction['tasks'] !== NULL) {
							$h .= '<a href="/series/analyze:tasks?id='.$eFarm['id'].'&action='.$eAction['id'].'">'.$eAction['tasks'].'</a>';
						} else {
							$h .= '/';
						}
					$h .= '</td>';
					$h .= '<td class="text-end" style="white-space: nowrap">';

						$h .= '<a href="/farm/action:update?id='.$eAction['id'].'" class="btn btn-outline-secondary">';
							$h .= \Asset::icon('gear-fill');
						$h .= '</a> ';

						if($eAction['fqn'] === NULL) {
							$h .= '<a data-ajax="/farm/action:doDelete" data-confirm="'.s("Supprimer cette intervention ?").'" post-id="'.$eAction['id'].'" class="btn btn-outline-secondary">';
								$h .= \Asset::icon('trash-fill');
							$h .= '</a>';
						} else {
							$h .= '<div class="btn btn-outline-secondary" title="'.s("Action indispensable au bon fonctionnement de {siteName}").'" disabled>';
								$h .= \Asset::icon('trash-fill');
							$h .= '</div>';
						}

					$h .= '</td>';
				$h .= '</tr>';
			}
			$h .= '</tbody>';
		$h .= '</table>';

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
			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eFarm)
			);
			$h .= $form->dynamicGroups($eAction, ['name*', 'categories*', 'color']);
			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
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

			if($eAction['fqn'] === NULL) {
				$properties = ['name', 'categories', 'color', 'pace'];
			} else {
				$properties = ['color', 'pace'];
			}

			$h .= $form->dynamicGroups($eAction, $properties);
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une intervention"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Action::model()->describer($property, [
			'name' => s("Nom de l'intervention"),
			'short' => s("Raccourci du nom"),
			'fqn' => s("Nom qualifié"),
			'color' => s("Couleur"),
			'categories' => s("Catégories"),
			'pace' => s("Calcul de la productivité de l'intervention").\util\FormUi::info(s("La productivité n'est calculée que pour les interventions réalisées au sein d'une série.")),
			'series' => s("Activer cette intervention dans les séries")
		]);

		switch($property) {

			case 'categories' :
				$d->field = function(\util\FormUi $form, Action $e) {

					$cCategory = $e['cCategory'] ?? $e->expects(['cCategory']);

					$h = $form->checkboxes('categories[]', $cCategory, $e['categories'] ?? [], ['all' => FALSE]);

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
				break;

			case 'color' :
				$d->attributes['emptyColor'] = (new ActionModel())->getDefaultValue('color');
				break;

			case 'short' :
				$d->after = '<small>'.s("Laisser vide si la première lettre du nom convient").'</small>';
				break;

		}

		return $d;

	}


}
?>
