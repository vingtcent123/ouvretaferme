<?php
namespace selling;

class CustomerGroupLib extends CustomerGroupCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'color', 'type'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'color'];
	}

	public static function getLimitedByProducts(\Collection $cProduct): \Collection {

		$groups = array_merge(...$cProduct->getColumn('limitGroups'), ...$cProduct->getColumn('excludeGroups'));

		return CustomerGroup::model()
			->select(CustomerGroup::getSelection())
			->whereId('IN', $groups)
			->getCollection(NULL, NULL, 'id');

	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, ?string $type, ?array $properties = []): \Collection {

		if($query !== '') {
			CustomerGroup::model()->whereName('LIKE', '%'.$query.'%');
		}

		return CustomerGroup::model()
			->select($properties ?: CustomerGroup::getSelection())
			->whereFarm($eFarm)
			->whereType($type, if: $type !== NULL)
			->getCollection()
			->sort('name', natural: TRUE);

	}

	public static function askByFarm(\farm\Farm $eFarm, array $ids): \Collection {

		$callback = fn() => CustomerGroup::model()
			->select([
				'id', 'name', 'color'
			])
			->whereFarm($eFarm)
			->getCollection(index: 'id');

		return self::getCache($eFarm['id'], $callback)->findByKeys($ids);

	}

	public static function getByFarm(\farm\Farm $eFarm, mixed $id = NULL, ?string $index = 'id'): \Collection|CustomerGroup {

		$expects = 'collection';

		if($id !== NULL) {
			$expects = 'element';
			CustomerGroup::model()->whereId($id);
		}

		CustomerGroup::model()
			->select(CustomerGroup::getSelection())
			->whereFarm($eFarm)
			->sort(['name' => SORT_ASC]);

		if($expects === 'element') {
			return CustomerGroup::model()->get();
		} else {
			return CustomerGroup::model()->getCollection(NULL, NULL, $index);
		}

	}


	public static function countByFarm(\farm\Farm $eFarm): int {

		return CustomerGroup::model()
			->whereFarm($eFarm)
			->count();

	}

	public static function getForManage(\farm\Farm $eFarm): \Collection|CustomerGroup {

		CustomerGroup::model()
			->select([
				'prices' => Grid::model()
					->group(['group'])
					->delegateProperty('group', new \Sql('COUNT(*)', 'int'), fn($value) => $value ?? 0)
			]);

		$cCustomerGroup = self::getByFarm($eFarm);

		foreach($cCustomerGroup as $eCustomerGroup) {
			$eCustomerGroup['customers'] = CustomerLib::countByGroup($eCustomerGroup);
		}

		return $cCustomerGroup;

	}

	public static function delete(CustomerGroup $e): void {

		$e->expects(['id', 'farm']);

		CustomerGroup::model()->beginTransaction();

			Customer::model()
				->whereFarm($e['farm'])
				->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$e['id'].'\')')
				->update([
					'groups' => new \Sql(\sequence\Flow::model()->pdo()->api->jsonRemove('groups', $e['id']))
				]);

			parent::delete($e);

		CustomerGroup::model()->commit();

	}

}
?>
