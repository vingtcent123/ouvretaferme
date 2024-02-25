<?php
namespace farm;

class Tool extends ToolElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'action' => ['fqn', 'name'],
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canWrite();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function isStandaloneRoutine(): bool {

		$this->expects(['routineName']);

		if($this['routineName'] !== NULL) {
			return RoutineLib::get($this['routineName'])['standalone'];
		} else {
			return FALSE;
		}

	}

	public function getStandaloneRoutine(string $key): mixed {

		$this->expects(['routineName']);

		if($this->isStandaloneRoutine()) {
			return $this->getRoutine($key);
		} else {
			return NULL;
		}

	}

	public function getRoutine(string $key): mixed {

		$this->expects(['routineName']);

		return RoutineUi::get($this['routineName'])[$key] ?? throw new \Exception('Invalid key "'.$key.'"');

	}

	public function getActionFromRoutine(Farm $eFarm): mixed {

		$this->expects(['routineName']);

		if($this['routineName'] === NULL) {
			return new Action();
		} else {
			return Action::model()
				->select(Action::getSelection())
				->whereFarm($eFarm)
				->whereFqn(RoutineLib::get($this['routineName'])['action'])
				->get();
		}


	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'action.check' => function(\farm\Action $eAction): bool {
				$this->expects(['farm']);
				return (
					$eAction->empty() or
					\farm\ActionLib::canUse($eAction, $this['farm'])
				);
			},

			'routineName.check' => function(?string $routineName): bool {

				if($routineName === NULL) {
					return TRUE;
				}

				if($this['action']->empty()) {
					return FALSE;
				}

				$this->expects([
					'action' => ['fqn']
				]);

				return (
					RoutineLib::exists($routineName) and
					$this['action']['fqn'] === RoutineLib::get($routineName)['action']
				);

			},

			'routineValue.check' => function(?array &$routineValue): bool {

				$this->expects([
					'routineName'
				]);

				if($this['routineName'] === NULL) {
					$routineValue = NULL;
					return TRUE;
				}

				if($routineValue === NULL) {
					return FALSE;
				}

				$routineValue = RoutineLib::get($this['routineName'])['check']($routineValue);

				return TRUE;

			},

		]);

	}

}
?>