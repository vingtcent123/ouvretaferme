<?php
namespace shop;

class DepartmentLib extends DepartmentCrud {

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name'];
	}

	public static function getByShop(\shop\Shop $eShop): \Collection {

		return Department::model()
			->select(Department::getSelection())
			->whereShop($eShop)
			->sort(['position' => SORT_ASC])
			->getCollection(index: 'id');

	}

	public static function create(Department $e): void {

		Department::model()->beginTransaction();

		$categories = Department::model()
			->whereShop($e['shop'])
			->count();

		$e['position'] = $categories + 1;

		parent::create($e);

		Department::model()->commit();

	}

	public static function incrementPosition(Department $e, int $increment): void {

		$e->expects(['shop']);

		if($increment !== -1 and $increment !== 1) {
			return;
		}

		$eShop = $e['shop'];

		Department::model()->beginTransaction();

		$position = Department::model()
			->whereId($e)
			->getValue('position');

		switch($increment) {

			case -1 :

				Department::model()
					->whereShop($eShop)
					->wherePosition($position - 1)
					->update([
						'position' => $position
					]);

				Department::model()->update($e, [
						'position' => $position - 1
					]);

				break;

			case 1 :

				Department::model()
					->whereShop($eShop)
					->wherePosition($position + 1)
					->update([
						'position' => $position
					]);

				Department::model()->update($e, [
						'position' => $position + 1
					]);

				break;

		}

		Department::model()->commit();

	}

	public static function delete(Department $e): void {

		$e->expects(['id', 'shop']);

		Department::model()->beginTransaction();

			Range::model()
				->whereShop($e['shop'])
				->whereDepartment($e)
				->update([
					'department' => NULL
				]);

			parent::delete($e);

			self::reorder($e['shop']);

		Department::model()->commit();

	}

	public static function reorder(\shop\Shop $eShop): void {

		$cDepartment = self::getByShop($eShop);

		$position = 1;

		foreach($cDepartment as $eDepartment) {

			Department::model()->update($eDepartment, [
				'position' => $position++
			]);

		}

	}

}
?>
