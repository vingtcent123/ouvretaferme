<?php
namespace farm;

class ToolLib extends ToolCrud {

	use \ModuleDeferred;

	public static function getPropertiesCreate(): array {
		return ['name', 'action', 'stock', 'comment', 'routineName', 'routineValue'];
	}

	public static function getPropertiesUpdate(): \Closure {
		return function(Tool $e) {
			if($e->isStandaloneRoutine()) {
				return ['name', 'stock', 'comment', 'routineName', 'routineValue'];
			} else {
				return ToolLib::getPropertiesCreate();
			}
		};
	}

	public static function deferred(\farm\Farm $eFarm): \Collection {

		$callback = fn() => Tool::model()
			->select([
				'id', 'name', 'vignette',
				'routineName', 'routineValue'
			])
			->whereFarm($eFarm)
			->getCollection(index: 'id');

		return self::getCache($eFarm['id'], $callback);

	}

	public static function countByFarm(\farm\Farm $eFarm, ?string $routineName): array {

		self::applyWhereRoutineName($routineName);

		return Tool::model()
			->select([
				Tool::ACTIVE => new \Sql('SUM(status = "'.Tool::ACTIVE.'")', 'int'),
				Tool::INACTIVE => new \Sql('SUM(status = "'.Tool::INACTIVE.'")', 'int')
			])
			->whereFarm($eFarm)
			->get()
			->getArrayCopy() ?: [Tool::ACTIVE => 0, Tool::INACTIVE => 0];

	}

	public static function getNewTool(Farm $eFarm, ?string $routineName): Tool {

		$eTool = new \farm\Tool([
			'farm' => $eFarm,
			'routineName' => $routineName,
			'routineValue' => []
		]);

		if($eTool->isStandaloneRoutine()) {
			$eTool['action'] = $eTool->getActionFromRoutine($eFarm);
		}
		return $eTool;

	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, Action $eAction, ?array $properties = []): \Collection {

		if($query !== '') {
			Tool::model()->whereName('LIKE', '%'.$query.'%');
		}

		if($eAction->notEmpty()) {
			Tool::model()->where('action IS NULL or action = '.$eAction['id']);
		}

		return Tool::model()
			->select($properties ?: Tool::getSelection())
			->whereFarm($eFarm)
			->whereStatus(Tool::ACTIVE)
			->getCollection()
			->sort('name', natural: TRUE);

	}

	public static function getOneByFarm(\farm\Farm $eFarm, mixed $id): Tool {

		return Tool::model()
			->select(Tool::getSelection())
			->whereFarm($eFarm)
			->whereId($id)
			->get();

	}

	public static function getTraysByFarm(\farm\Farm $eFarm): \Collection|\Element {

		return \farm\ToolLib::getByFarm($eFarm, routineName: 'tray');

	}

	public static function getByFarm(\farm\Farm $eFarm, mixed $id = NULL, ?string $routineName = NULL, \Search $search = new \Search()): \Collection|\Element {

		$expects = 'collection';

		if($id !== NULL) {
			$expects = 'element';
			Tool::model()->whereId($id);
		}

		if($search->get('name')) {
			Tool::model()->whereName('LIKE', '%'.$search->get('name').'%');
		}

		self::applyWhereRoutineName($routineName);

		if($search->get('action') and $search->get('action')->notEmpty()) {
			Tool::model()->whereAction($search->get('action'));
		}

		if($search->get('status')) {
			Tool::model()->whereStatus($search->get('status'));
		} else {
			Tool::model()->whereStatus(Tool::ACTIVE);
		}

		Tool::model()
			->select(Tool::getSelection())
			->whereFarm($eFarm);

		if($expects === 'element') {
			return Tool::model()->get();
		} else {
			return Tool::model()
				->getCollection(NULL, NULL, 'id')
				->sort('name', natural: TRUE);
		}

	}

	private static function applyWhereRoutineName(?string $routineName): void {

		if($routineName) {
			Tool::model()->whereRoutineName($routineName);
		} else {
			Tool::model()->or(
				fn() => $this->whereRoutineName(NULL),
				fn() => $this->whereRoutineName('NOT IN', array_keys(RoutineLib::getByStandalone(TRUE)))
			);
		}

	}

	public static function getForWork(\farm\Farm $eFarm, Action $eAction): \Collection {

		if($eAction->empty()) {
			return new \Collection();
		}

		return Tool::model()
			->select([
				'id', 'name'
			])
			->whereFarm($eFarm)
			->whereStatus(Tool::ACTIVE)
			->where('action IS NULL or action = '.$eAction['id'])
			->getCollection()
			->sort('name', natural: TRUE);

	}

	public static function getActionsByFarm(\farm\Farm $eFarm): \Collection {

		return Tool::model()
			->select([
				'action' => ['name']
			])
			->whereFarm($eFarm)
			->whereAction('!=', NULL)
			->group('action')
			->getColumn('action')
			->sort('name');

	}

	public static function create(Tool $e): void {

		try {
			parent::create($e);
		} catch(\DuplicateException) {
			Tool::fail('name.duplicate');
		}

	}

	public static function update(Tool $e, array $properties): void {

		try {
			parent::update($e, $properties);
		} catch(\DuplicateException) {
			Tool::fail('name.duplicate');
		}

	}

	public static function delete(Tool $e): void {

		$e->expects(['id', 'farm', 'routineName']);

		if(
			\series\Task::model()
				->whereFarm($e['farm'])
				->where('JSON_CONTAINS(tools, \''.$e['id'].'\')')
				->exists() or
			\sequence\Flow::model()
				->whereFarm($e['farm'])
				->where('JSON_CONTAINS(tools, \''.$e['id'].'\')')
				->exists() or
			(
				$e['routineName'] === 'tray' and
				\series\Cultivation::model()
					->whereFarm($e['farm'])
					->whereSliceTool($e)
					->exists()
			)
		) {

			Tool::model()->update($e, [
				'status' => Tool::DELETED,
				'deleted' => NULL /* Pour la gestion de l'unicité du nom */
			]);

		} else {
			parent::delete($e);
		}

	}

}
?>
