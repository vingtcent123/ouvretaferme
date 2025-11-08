<?php
namespace selling;

class RelationLib extends RelationCrud {

	public static function getPropertiesCreate(): array {

		return ['child'];

	}

	public static function getByParent(Product $eProduct): \Collection {

		return Relation::model()
			->select(Relation::getSelection() + [
				'child' => ProductElement::getSelection()
			])
			->whereParent($eProduct)
			->sort([
				'position' => SORT_ASC
			])
			->getCollection();

	}

	public static function prepareCollection(\farm\Farm $eFarm, array $input): array {

		$cRelationReference = new \Collection();

		$eProductParent = new Product([
			'farm' => $eFarm,
			'profile' => Product::GROUP,
			'pro' => FALSE,
			'private' => FALSE
		]);

		$eProductParent->build(['name', 'groupSelection'], $input);

		$cProductChildren = ProductLib::getByIds((array)($input['children'] ?? []))
			->validateProperty('farm', $eFarm)
			->validate('acceptRelation');

		$position = 1;

		foreach($cProductChildren as $eProductChild) {

			$eRelationReference = new Relation([
				'farm' => $eFarm,
				'child' => $eProductChild,
				'position' => $position++
			]);

			$cRelationReference[] = $eRelationReference;

			if($eProductChild['pro']) {
				$eProductParent['pro'] = TRUE;
			}

			if($eProductChild['private']) {
				$eProductParent['private'] = TRUE;
			}

		}

		if($cRelationReference->empty()) {
			\selling\Relation::fail('empty');
		}

		return [$eProductParent, $cRelationReference];

	}

	public static function create(Relation $eRelation): void {

		$eRelation->expects([
			'parent' => ['farm'],
			'child'
		]);

		\selling\Relation::model()->beginTransaction();

			$eRelation['position'] = Relation::model()
				->whereParent($eRelation['parent'])
				->count() + 1;

			parent::create($eRelation);

		\selling\Relation::model()->commit();

	}

	public static function createCollection(Product $eProduct, \Collection $cRelation): void {

		\selling\Relation::model()->beginTransaction();

			ProductLib::create($eProduct);

			$cRelation->setColumn('parent', $eProduct);

			foreach($cRelation as $eRelation) {
				parent::create($eRelation);
			}


		\selling\Relation::model()->commit();

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

	public static function delete(Relation $e): void {

		$e->expects(['id', 'parent']);

		Relation::model()->beginTransaction();

			parent::delete($e);

			self::reorderByParent($e['parent']);

		Relation::model()->commit();

	}

	public static function deleteByParent(Product $eProduct): void {

		Relation::model()
			->whereParent($eProduct)
			->delete();

	}

	public static function reorderByParent(Product $eProduct): void {

		$cRelation = self::getByParent($eProduct);

		$position = 1;

		foreach($cRelation as $eRelation) {

			Relation::model()->update($eRelation, [
				'position' => $position++
			]);

		}

	}

}
?>
