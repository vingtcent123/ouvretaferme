<?php
namespace farm;

class MethodLib extends MethodCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'action'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name'];
	}

	public static function delete(Method $e): void {

		$e->expects(['id', 'farm', 'action']);

		Method::model()->beginTransaction();

			parent::delete($e);

			\series\Task::model()
				->whereFarm($e['farm'])
				->whereAction($e['action'])
				->whereMethod($e)
				->update([
					'method' => NULL
				]);

		Method::model()->commit();

	}

}
?>
