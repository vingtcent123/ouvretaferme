<?php
namespace farm;

class ActionLib extends ActionCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'categories', 'color'];
	}

	public static function getPropertiesUpdate(): \Closure {
		return function(Action $e) {
			$e->expects(['fqn']);
			if($e['fqn'] === NULL) {
				return ['name', 'categories', 'color', 'pace'];
			} else {
				return ['color', 'pace'];
			}
		};
	}

	public static function duplicateForFarm(\farm\Farm $eFarm): \Collection {

		$cCategory = CategoryLib::duplicateForFarm($eFarm);

		$cAction = Action::model()
			->select(Action::getSelection())
			->whereFarm(NULL)
			->getCollection(index: 'id');

		$cAction->map(function(Action $eAction) use($eFarm, $cCategory) {

			$eAction['id'] = NULL;
			$eAction['farm'] = $eFarm;

			foreach($eAction['categories'] as $key => $category) {
				$eAction['categories'][$key] = $cCategory[$category]['id'];
			}

		});

		Action::model()->insert($cAction);

		return $cAction;

	}

	public static function getByFarm(\farm\Farm $eFarm, mixed $id = NULL, array|string|null $fqn = NULL, Category|string $category = new Category(), ?string $index = NULL): \Collection|Action {

		$expects = 'collection';

		if(is_string($category)) {
			$eCategory = CategoryLib::getByFarm($eFarm, fqn: $category);
		} else {
			$eCategory = $category;
		}

		if($eCategory->notEmpty()) {
			Action::model()->where('JSON_CONTAINS(categories, \''.$eCategory['id'].'\')');
		}

		if($id !== NULL) {
			if(is_array($id)) {
				Action::model()->whereId('IN', $id);
			} else {
				$expects = 'element';
				Action::model()->whereId($id);
			}
		}

		if($fqn !== NULL) {
			if(is_string($fqn)) {
				$expects = 'element';
				Action::model()->whereFqn($fqn);
			} else {
				Action::model()->whereFqn('IN', $fqn);
			}
		}

		Action::model()
			->select(Action::getSelection())
			->whereFarm($eFarm)
			->sort(['name' => SORT_ASC]);

		if($expects === 'element') {
			return Action::model()->get();
		} else {
			return Action::model()->getCollection(NULL, NULL, $index);
		}

	}

	public static function getMainByFarm(Farm $eFarm): \Collection {

		if(FarmSetting::$mainActions !== NULL) {
			return FarmSetting::$mainActions;
		}

		$cAction = Action::model()
			->select(['id', 'fqn', 'color', 'categories'])
			->whereFarm($eFarm)
			->whereFqn('IN', [ACTION_SEMIS_PEPINIERE, ACTION_SEMIS_DIRECT, ACTION_PLANTATION, ACTION_RECOLTE])
			->getCollection(index: 'fqn');

		FarmSetting::$mainActions = $cAction;

		return $cAction;

	}

	public static function getForManage(\farm\Farm $eFarm): \Collection|Action {

		Action::model()
			->select([
				'cMethod' => Method::model()
					->select(['id', 'name'])
					->delegateCollection('action'),
				'tasks' => \series\Task::model()
					->group('action')
					->delegateProperty('action', new \Sql('COUNT(*)', 'int'))

			]);

		return self::getByFarm($eFarm);

	}

	public static function getByFarmWithPace(\farm\Farm $eFarm): \Collection|Action {

		return Action::model()
			->select(Action::getSelection())
			->whereFarm($eFarm)
			->wherePace('!=', NULL)
			->sort(['name' => SORT_ASC])
			->getCollection();

	}

	public static function canUse(Action $eAction, \farm\Farm $eFarm): bool {

		return (
			$eAction->empty() === FALSE and
			Action::model()
				->select(Action::getSelection()) // Requis pour traitements futurs
				->whereFarm($eFarm)
				->get($eAction)
		);

	}

	public static function delete(Action $e): void {

		$e->expects(['id', 'fqn', 'farm']);

		if($e['fqn'] !== NULL) {
			Action::fail('deleteMandatory');
			return;
		}

		if(\series\Task::model()
				->whereAction($e)
				->exists() or \sequence\Flow::model()
				->whereAction($e)
				->exists()) {
			Action::fail('deleteUsed');
			return;
		}


		Tool::model()
			->whereFarm($e['farm'])
			->whereAction($e)
			->update([
				'action' => new Action()
			]);

		parent::delete($e);

	}

}
?>
