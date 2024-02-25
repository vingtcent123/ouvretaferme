<?php
namespace farm;

class RoutineUi {

	public function __construct() {

		\Asset::css('farm', 'routine.css');
		\Asset::js('farm', 'routine.js');

	}

	public static function list(): array {

		\Asset::css('farm', 'routine.css');

		return [
			'fertilizer' => [
				'name' => s("Intrant"),
				'label' => s("Composition"),
				'summary' => fn() => '',
				'td' => fn(Tool $eTool) => self::getTdFertilizer($eTool),
				'th' => fn() => self::getThFertilizer(),
				'field' => function($form, Tool $eTool) {
					$default = $eTool['routineName'] === 'fertilizer' ? $eTool['routineValue'] : NULL;
					return (new RoutineUi())->getFieldFertilizer($form, 'routineValue', $default);
				},
				'nameField' => s("Nom de l'intrant"),
				'pageTitle' => fn(Farm $eFarm) => s("Les intrants de {value}", $eFarm['name']),
				'title' => s("Intrant"),
				'nothing' => s("Vous n'avez pas encore ajouté d'intrant à votre ferme. Ajouter votre gamme d'intrants peut être très utile pour que {siteName} puisse vous indiquer les quantités à épandre sur vos planches !"),
				'createButton' => s("Nouvel intrant"),
				'createTitle' => s("Ajouter un nouvel intrant"),
				'updateTitle' => s("Modifier l'intrant"),
				'deleteConfirm' => s("Supprimer cet intrant ?"),
				'dropdownUpdate' => s("Modifier l'intrant"),
				'dropdownDisable' => s("Ne plus utiliser l'intrant"),
				'dropdownEnable' => s("Réutiliser l'intrant"),
				'dropdownDelete' => s("Supprimer l'intrant"),
				'tabActive' => s("Intrant utilisés"),
				'tabInactive' => s("Intrant inutilisés"),
				'empty' => s("Vous n'avez pas encore ajouté d'intrant."),
			],
			'tray' => [
				'name' => s("Plateau de semis"),
				'label' => s("Nombre de mottes par plateau"),
				'summary' => fn(Tool $eTool) => (new RoutineUi())->getSummaryTray($eTool),
				'field' => function($form, Tool $eTool) {
					$default = $eTool['routineName'] === 'tray' ? $eTool['routineValue'] : NULL;
					return (new RoutineUi())->getFieldTray($form, 'routineValue', $default);
				},
			]
		];

	}

	public static function get(string $name): array {

		return self::list()[$name] ?? throw new \Exception('Unknown routine "'.$name.'"');

	}

	public static function getProperty(string $name, string $property): mixed {

		return self::get($name)[$property] ?? throw new \Exception('Unknown property "'.$name.'" for routine "'.$name.'"');

	}

	public function getRoutinesGroup(\util\FormUi $form, Tool $eTool, array $routines): string {

		$h = '<div data-ref="routines" data-farm="'.$eTool['farm']['id'].'">';
			if($eTool['routineName'] !== NULL) {
				$h .= $this->getFields($routines, $eTool);
			}
		$h .= '</div>';

		return $h;

	}

	public function getFields(array $routines, Tool $eTool): string {

		$eTool->expects(['routineName', 'routineValue']);

		$isStandalone = (array_intersect(array_keys(RoutineLib::getByStandalone(TRUE)), $routines) !== []);

		if($isStandalone) {

			if(count($routines) > 1) {
				throw new \Exception('Only one standalone routine expected');
			} else {
				return $this->getField(new \util\FormUi(), first($routines), $eTool);
			}

		} else if($routines) {

			$form = new \util\FormUi();

			$values = [];
			foreach($routines as $routineName) {
				$values[$routineName] = self::getProperty($routineName, 'name');
			}

			$h = $form->group(
				s("Type de matériel"),
				$form->select('routineName', $values, $eTool['routineName'], [
					'onchange' => 'Routine.select(this)'
				]).
				\util\FormUi::info(s("Laissez vide si aucun type ne correspond à votre saisie"))
			);

			foreach($routines as $routineName) {
				$h .= '<div data-ref="routine-'.$routineName.'" class="'.($eTool['routineName'] === $routineName ? '' : 'hide').'">';
					$h .= $this->getField($form, $routineName, $eTool);
				$h .= '</div>';
			}

			return $h;

		} else {
			return '';
		}

	}

	protected function getField(\util\FormUi $form, string $routineName, Tool $eTool): string {

		$properties = self::get($routineName);
		$field = $properties['field']($form, $eTool);

		$h = '';

		if($field) {

			$h .= $form->inputRadio('routineName', $routineName, selectedValue: $eTool['routineName'], attributes: ['class' => 'hide']);

			$h .= $form->group(
				$properties['label'],
				$field
			);

		}

		return $h;

	}

	public function getSummaryTray(Tool $eTool): string {

		if($eTool['routineValue']['value']) {
			return p("{value} motte par plateau", "{value} mottes par plateau", $eTool['routineValue']['value']);
		} else {
			return '';
		}

	}

	public function getFieldTray(\util\FormUi $form, string $field, ?array $default): string {

		$default ??= [];

		$h = $form->inputGroup(
			$form->number($field.'[value]', $default['value'] ?? '', ['min' => 1]).
			$form->addon('mottes')
		);

		return $h;

	}

	public function getFieldFertilizer(\util\FormUi $form, string $field, ?array $default): string {

		$default ??= [];

		$h = '<div class="routine-fertilizer-wrapper routine-fertilizer-wrapper-field">';

			foreach([
				'n' => 'N',
				'p' => 'P',
				'k' => 'K',
				s("+"),
				'mgo' => 'MgO',
        ] as $key => $value) {

				if(is_int($key)) {

					$h .= $value;

				} else {

					$h .= '<div class="routine-fertilizer-element">';
						$h .= '<h5>'.$value.'</h5>';
						$h .= '<div class="routine-fertilizer-value">';
							$h .= $form->number($field.'['.$key.']', $default[$key] ?? '', ['min' => 0.0, 'step' => 0.1]);
						$h .= '</div>';
					$h .= '</div>';

				}

			}

		$h .= '</div>';

		return $h;

	}

	public static function getTdFertilizer(Tool $eTool): string {

		$h = '<td>';

			$h .= '<div class="routine-fertilizer-wrapper routine-fertilizer-wrapper-field">';

				foreach([
					'n' => 'N',
					'p' => 'P',
					'k' => 'K',
					s("+"),
					'mgo' => 'MgO',
	        ] as $key => $value) {

					if(is_int($key)) {

						$h .= $value;

					} else {

						$h .= '<div class="routine-fertilizer-element">';
							$h .= '<h5>'.$value.'</h5>';
							$h .= '<div class="routine-fertilizer-value">';
								$h .= $eTool['routineValue'][$key] ?? '-';
							$h .= '</div>';
						$h .= '</div>';

					}

				}

			$h .= '</div>';

		$h .= '</td>';

		return $h;

	}

	public static function getThFertilizer(): string {

		$h = '<th>'.s("Composition").'</th>';

		return $h;

	}
	
	
	public function getSeparateFertilizer(array $elements, string $separator = '&nbsp;'): array {

		$major = [];
		$minor = [];

		if(array_filter($elements)) {

			foreach(['n' => 'N', 'p' => 'P', 'k' => 'K'] as $key => $label) {

				if($elements[$key]) {
					$major[] = $elements[$key].$separator.$label;
				}

			}

			foreach(['mgo' => 'MgO'] as $key => $label) {

				if($elements[$key]) {
					$minor[] = $elements[$key].$separator.$label;
				}

			}

		}

		return [$major, $minor];

	}

	public static function getListFertilizer(): array {

		return [
			'n' => 'N',
			'p' => 'P',
			'k' => 'K',
			'mgo' => 'MgO',
		];

	}

}
?>
