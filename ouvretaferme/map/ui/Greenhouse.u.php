<?php
namespace map;

class GreenhouseUi {

	public function __construct() {

		\Asset::css('map', 'greenhouse.css');

	}

	public static function defaultName(): string {
		return s("Bloc sous abri");
	}

	public function getList(\farm\Farm $eFarm, \Collection $cGreenhouse, string $buttonStyle, string $backgroundDropdown): string {

		if($cGreenhouse->empty()) {
			return '';
		}

		$h = '<div>';

			$h .= \Asset::icon('greenhouse').'&nbsp;&nbsp;';

			foreach($cGreenhouse as $eGreenhouse) {

				if($eFarm->canManage()) {

					$h .= ' <a class="dropdown-toggle btn '.$buttonStyle.'" data-dropdown="bottom-start">'.encode($eGreenhouse['name']).'</a>';
					$h .= '<div class="dropdown-list '.$backgroundDropdown.'">';
						$h .= '<div class="dropdown-title">'.encode($eGreenhouse['name']).'<br/><span style="font-weight: normal">'.s("{length} mL x {width} m", $eGreenhouse).' '.\Asset::icon('arrow-right').' '.s("{area} m²", $eGreenhouse).'</span></div>';
						$h .= '<a href="/map/greenhouse:update?id='.$eGreenhouse['id'].'" class="dropdown-item">'.s("Modifier l'abri").'</a>';
						$h .= '<a data-ajax="/map/greenhouse:doDelete" post-id="'.$eGreenhouse['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de l'abri ?").'">'.s("Supprimer l'abri").'</a>';
					$h .= '</div>';

				} else {
					$h .= ' <span class="btn '.$buttonStyle.'">'.encode($eGreenhouse['name']).'</span>';
				}

			}

		$h .= '</div>';

		return $h;

	}

	public function create(Greenhouse $eGreenhouse): \Panel {

		$eGreenhouse->expects(['farm', 'plot', 'zone']);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/map/greenhouse:doCreate', ['id' => 'greenhouse-create']);

			$h .= $form->hidden('farm', $eGreenhouse['farm']['id']);
			$h .= $form->hidden('plot', $eGreenhouse['plot']);

			if($eGreenhouse['plot']['zoneFill']) {

				$h .= $form->group(
					s("Parcelle"),
					'<div class="form-control disabled">'.encode($eGreenhouse['zone']['name']).'</div>'
				);

			} else {

				$h .= $form->group(
					s("Bloc"),
					'<div class="form-control disabled">'.encode($eGreenhouse['plot']['name']).'</div>'
				);

			}

			$h .= $this->write($form, $eGreenhouse);

			$h .= $form->group(
				content: $form->submit(s("Créer l'abri"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter un abri"),
			body: $h
		);

	}

	public function update(Greenhouse $eGreenhouse): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/map/greenhouse:doUpdate', ['id' => 'greenhouse-update']);

			$h .= $form->hidden('id', $eGreenhouse['id']);

			$h .= $this->write($form, $eGreenhouse);
			$h .= $form->dynamicGroup($eGreenhouse, 'seasonLast');

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier un abri"),
			body: $h
		);

	}

	public function write(\util\FormUi $form, Greenhouse $eGreenhouse): string {

		$h = '';

		if($eGreenhouse['plot']['zoneFill']) {
			$h .= $form->dynamicGroup($eGreenhouse, 'name');
		} else {
			$h .= $form->hidden('name', self::defaultName());
		}

		$h .= $form->dynamicGroups($eGreenhouse, ['length', 'width', 'seasonFirst']);

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Greenhouse::model()->describer($property, [
			'name' => s("Nom de l'abri"),
			'farm' => s("Ferme"),
			'length' => s("Longueur de l'abri"),
			'width' => s("Largeur de l'abri"),
			'seasonFirst' => s("Abri exploité depuis"),
			'seasonLast' => s("Abri exploité jusqu'à"),
			'createdAt' => s("Créé le"),
		]);

		switch($property) {

			case 'name' :
				$d->attributes = [
					'placeholder' => s("Ex. : Serre 1")
				];
				break;

			case 'length' :
				$d->append = s("m");
				break;

			case 'width' :
				$d->append = s("m");
				break;

			case 'seasonFirst' :
			case 'seasonLast' :
				$d->field = function(\util\FormUi $form, \Element $e, $property) {

					$e->expects(['farm']);

					$placeholder = [
						'seasonFirst' => s("la création de la ferme"),
						'seasonLast' => s("la disparition de la ferme")
					][$property];

					return (new SeasonUi())->getDescriberField($form, $e, $e['farm'], NULL, NULL, $property, $placeholder);
				};
				break;

		}

		return $d;

	}

}
?>
