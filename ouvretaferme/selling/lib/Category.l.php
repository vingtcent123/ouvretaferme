<?php
namespace selling;

class CategoryLib extends CategoryCrud {

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name'];
	}

	public static function getByFarm(\farm\Farm $eFarm, mixed $id = NULL, string $index = NULL): \Collection|Category {

		$expects = 'collection';

		if($id !== NULL) {
			$expects = 'element';
			Category::model()->whereId($id);
		}

		Category::model()
			->select(Category::getSelection())
			->whereFarm($eFarm)
			->sort(['position' => SORT_ASC]);

		if($expects === 'element') {
			return Category::model()->get();
		} else {
			return Category::model()->getCollection(NULL, NULL, $index);
		}

	}

	public static function create(Category $e): void {

		Category::model()->beginTransaction();

		$e['position'] = Category::model()
			->whereFarm($e['farm'])
			->count() + 1;

		parent::create($e);

		Category::model()->commit();

	}

	public static function incrementPosition(Category $e, int $increment): void {

		$e->expects(['farm']);

		if($increment !== -1 and $increment !== 1) {
			return;
		}

		$eFarm = $e['farm'];

		Category::model()->beginTransaction();

		$position = Category::model()
			->whereId($e)
			->getValue('position');

		switch($increment) {

			case -1 :

				Category::model()
					->whereFarm($eFarm)
					->wherePosition($position - 1)
					->update([
						'position' => $position
					]);

				Category::model()->update($e, [
						'position' => $position - 1
					]);

				break;

			case 1 :

				Category::model()
					->whereFarm($eFarm)
					->wherePosition($position + 1)
					->update([
						'position' => $position
					]);

				Category::model()->update($e, [
						'position' => $position + 1
					]);

				break;

		}

		Category::model()->commit();

	}

	public static function delete(Category $e): void {

		$e->expects(['id', 'farm']);

		Category::model()->beginTransaction();

		Product::model()
			->whereCategory($e)
			->update([
				'category' => new Category()
			]);

		parent::delete($e);

		self::reorder($e['farm']);

		Category::model()->commit();

	}

	public static function reorder(\farm\Farm $eFarm): void {

		$cCategory = self::getByFarm($eFarm);

		$position = 1;

		foreach($cCategory as $eCategory) {

			Category::model()->update($eCategory, [
				'position' => $position++
			]);

		}

	}

}
?>
