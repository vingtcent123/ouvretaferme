<?php
namespace selling;

class GroupLib extends GroupCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'color', 'type'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'color'];
	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, ?string $type, ?array $properties = []): \Collection {

		if($query !== '') {
			Group::model()->whereName('LIKE', '%'.$query.'%');
		}

		return Group::model()
			->select($properties ?: Group::getSelection())
			->whereFarm($eFarm)
			->whereType($type, if: $type !== NULL)
			->getCollection()
			->sort('name', natural: TRUE);

	}

	public static function askByFarm(\farm\Farm $eFarm, array $ids): \Collection {

		$callback = fn() => Group::model()
			->select([
				'id', 'name', 'color'
			])
			->whereFarm($eFarm)
			->getCollection(index: 'id');

		return self::getCache($eFarm['id'], $callback)->findByKeys($ids);

	}

	public static function getByFarm(\farm\Farm $eFarm, mixed $id = NULL, ?string $index = 'id'): \Collection|Group {

		$expects = 'collection';

		if($id !== NULL) {
			$expects = 'element';
			Group::model()->whereId($id);
		}

		Group::model()
			->select(Group::getSelection())
			->whereFarm($eFarm)
			->sort(['name' => SORT_ASC]);

		if($expects === 'element') {
			return Group::model()->get();
		} else {
			return Group::model()->getCollection(NULL, NULL, $index);
		}

	}


	public static function countByFarm(\farm\Farm $eFarm): int {

		return Group::model()
			->whereFarm($eFarm)
			->count();

	}

	public static function getForManage(\farm\Farm $eFarm): \Collection|Group {

		Group::model()
			->select([
				'prices' => Grid::model()
					->group(['group'])
					->delegateProperty('group', new \Sql('COUNT(*)', 'int'), fn($value) => $value ?? 0)
			]);

		$cGroup = self::getByFarm($eFarm);

		foreach($cGroup as $eGroup) {
			$eGroup['customers'] = CustomerLib::countByGroup($eGroup);
		}

		return $cGroup;

	}

	public static function delete(Group $e): void {

		$e->expects(['id', 'farm']);

		Group::model()->beginTransaction();

			Customer::model()
				->whereFarm($e['farm'])
				->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$e['id'].'\')')
				->update([
					'groups' => new \Sql(\sequence\Flow::model()->pdo()->api->jsonRemove('groups', $e['id']))
				]);

			parent::delete($e);

		Group::model()->commit();

	}

}
?>
