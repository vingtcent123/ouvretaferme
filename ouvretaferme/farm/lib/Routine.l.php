<?php
namespace farm;

class RoutineLib extends ToolCrud {

	public static function list(): array {

		return [
			'fertilizer' => [ // Apports en éléments nutritifs pour les intrant
				'action' => ACTION_FERTILISATION,
				'standalone' => TRUE,
				'check' => fn(array $values) => self::checkFertilizer($values),
			],
			'tray' => [ // Dimensionnement des plaques de semis
				'action' => ACTION_SEMIS_PEPINIERE,
				'standalone' => FALSE,
				'check' => fn(array $values) => self::checkTray($values),
			]
		];

	}

	public static function exists(string $name): bool {
		return array_key_exists($name, self::list());
	}

	public static function get(string $name): array {
		return self::list()[$name] ?? throw new \Exception('Unknown routine "'.$name.'"');
	}

	public static function getByStandalone(bool $standalone): array {
		return array_filter(self::list(), fn($properties) => ($properties['standalone'] === $standalone));
	}

	public static function getByAction(Action $eAction, ?bool $standalone = NULL): array {

		if($eAction->empty()) {
			return [];
		}

		$eAction->expects(['fqn']);

		return array_filter(self::list(), fn($properties) => (
			$eAction['fqn'] === $properties['action'] and
			($standalone === NULL or $properties['standalone'] === $standalone)
		));

	}

	public static function checkFertilizer(array $values): array {

		$mask = ['float32', 'min' => 0.1, 'max' => NULL, 'null' => TRUE];

		$check = function($value) use ($mask) {
			if($value === NULL) {
				return NULL;
			} else {
				return \Filter::check($mask, $value) ? (float)$value : NULL;
			}
		};

		return [
			'n' => $check($values['n'] ?? NULL),
			'p' => $check($values['p'] ?? NULL),
			'k' => $check($values['k'] ?? NULL),
			'mgo' => $check($values['mgo'] ?? NULL)
		];

	}

	public static function checkTray(array $values): array {

		$mask = ['int', 'min' => 1, 'max' => NULL, 'null' => TRUE];
		$value = $values['value'] ?? NULL;

		return [
			'value' => ($value and \Filter::check($mask, $value)) ? (int)$value : NULL,
		];

	}

}
?>
