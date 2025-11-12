<?php
namespace shop;

class RelationLib extends RelationCrud {

	public static function getPropertiesCreate(): array {

		return ['child'];

	}

	public static function getByParent(Product $eProduct): \Collection {

		return Relation::model()
			->select(Relation::getSelection() + [
				'child' => Product::getSelection()
			])
			->whereParent($eProduct)
			->sort([
				'position' => SORT_ASC
			])
			->getCollection();

	}

	public static function incrementPosition(Relation $e, int $increment): void {

		$e->expects(['parent']);

		if($increment !== -1 and $increment !== 1) {
			return;
		}

		$eProduct = $e['parent'];

		Relation::model()->beginTransaction();

		$position = Relation::model()
			->whereId($e)
			->getValue('position');

		switch($increment) {

			case -1 :

				Relation::model()
					->whereParent($eProduct)
					->wherePosition($position - 1)
					->update([
						'position' => $position
					]);

				Relation::model()->update($e, [
						'position' => $position - 1
					]);

				break;

			case 1 :

				Relation::model()
					->whereParent($eProduct)
					->wherePosition($position + 1)
					->update([
						'position' => $position
					]);

				Relation::model()->update($e, [
						'position' => $position + 1
					]);

				break;

		}

		Relation::model()->commit();

	}

	public static function createByParent(Product $eProduct, \Collection $cRelation): void {

		$cRelation->setColumn('parent', $eProduct);

		foreach($cRelation as $eRelation) {
			self::create($eRelation);
		}

	}

	public static function delete(Relation $e): void {

		throw new \Exception('Not implemented');

	}

	public static function deleteByParent(Product $eProductParent): void {

		Relation::model()
			->whereParent($eProductParent)
			->delete();

	}

	public static function deleteByChild(Product $eProductChild): void {

		Product::model()->beginTransaction();

		$cProductParent = Relation::model()
			->whereChild($eProductChild)
			->getColumn('parent');

		Relation::model()
			->whereChild($eProductChild)
			->delete();

		foreach($cProductParent as $eProductParent) {

			if(
				Relation::model()
					->whereParent($eProductParent)
					->exists() === FALSE
			) {

				Product::model()->delete($eProductParent);

			}

		}

		Product::model()->commit();

	}

}
?>
