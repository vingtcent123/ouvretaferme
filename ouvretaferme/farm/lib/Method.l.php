<?php
namespace farm;

class MethodLib extends MethodCrud {

	private static $cDemand = NULL;

	public static function getPropertiesCreate(): array {
		return ['name', 'action'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name'];
	}

	public static function askByFarm(\farm\Farm $eFarm, array $ids): \Collection {

		$callback = fn() => Method::model()
			->select([
				'id', 'name'
			])
			->whereFarm($eFarm)
			->getCollection(index: 'id');

		return self::getCache($eFarm['id'], $callback)->findByKeys($ids);

	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, Action $eAction, ?array $properties = []): \Collection {

		if($query !== '') {
			Method::model()->whereName('LIKE', '%'.$query.'%');
		}

		return Method::model()
			->select($properties ?: Method::getSelection())
			->whereFarm($eFarm)
			->whereAction($eAction)
			->getCollection()
			->sort('name', natural: TRUE);

	}

	public static function getForWork(\farm\Farm $eFarm, Action $eAction): \Collection {

		if($eAction->empty()) {
			return new \Collection();
		}

		return Method::model()
			->select([
				'id', 'name'
			])
			->whereFarm($eFarm)
			->whereAction($eAction)
			->getCollection()
			->sort('name', natural: TRUE);

	}

	public static function delete(Method $e): void {

		$e->expects(['id', 'farm', 'action']);

		Method::model()->beginTransaction();

			parent::delete($e);

			/* JSON_SEARCH() ne fonctionne pas avec les entiers */

			\series\Task::model()
				->whereFarm($e['farm'])
				->whereAction($e['action'])
				->where('JSON_CONTAINS(methods, \''.$e['id'].'\')')
				->update([
					'methods' => new \Sql('REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(methods, \'([\\\\[ ])'.$e['id'].', \', \'$1\'), \', '.$e['id'].'([\\\\],])\', \'$1\'), \'['.$e['id'].']\', \'[]\')')
				]);

			\production\Flow::model()
				->whereFarm($e['farm'])
				->whereAction($e['action'])
				->where('JSON_CONTAINS(methods, \''.$e['id'].'\')')
				->update([
					'methods' => new \Sql('REPLACE(REGEXP_REPLACE(REGEXP_REPLACE(methods, \'([\\\\[ ])'.$e['id'].', \', \'$1\'), \', '.$e['id'].'([\\\\],])\', \'$1\'), \'['.$e['id'].']\', \'[]\')')
				]);

		Method::model()->commit();

	}

}
?>
