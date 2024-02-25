<?php
namespace plant;

/**
 * Plant basic functions
 */
class PlantLib extends PlantCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'latinName', 'family', 'cycle'];
	}

	public static function getPropertiesUpdate(): \Closure {
		return function(Plant $e) {
			if($e->isOwner()) {
				return ['name', 'latinName', 'family', 'cycle'];
			} else {
				return [];
			}
		};
	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, \Search $search = new \Search(), ?array $properties = []): \Collection {

		if(strpos($query, '#') === 0 and ctype_digit(substr($query, 1))) {

			Plant::model()->whereId(substr($query, 1));

		} else if($query !== '') {

			self::applyWhereName($query);

		} else {
			Plant::model()->sort('name');
		}

		if($search->get('ids')) {
			Plant::model()->whereId('IN', $search->get('ids'));
		}

		return Plant::model()
			->select($properties ?: Plant::getSelection())
			->whereFarm($eFarm)
			->whereStatus(Plant::ACTIVE)
			->getCollection();

	}

	private static function applyWhereName(string $query): void {

		Plant::model()
			->where('
					aliases LIKE '.\farm\Farm::model()->format('%'.$query.'%').' OR
					latinName LIKE '.\farm\Farm::model()->format('%'.$query.'%').' OR
					name LIKE '.\farm\Farm::model()->format('%'.$query.'%').'
				')
			->sort([
				new \Sql('
						IF(
							name LIKE '.\farm\Farm::model()->format($query.'%').',
							5,
							IF(
								name LIKE '.\farm\Farm::model()->format('%'.$query.'%').',
								4,
								IF(aliases LIKE '.\farm\Farm::model()->format('%'.$query.'%').', 1, 0) + IF(latinName LIKE '.\farm\Farm::model()->format('%'.$query.'%').', 1, 0)
							)
						) DESC'),
				'name' => SORT_ASC
			]);

	}

	public static function duplicateForFarm(\farm\Farm $eFarm): void {

		$cPlant = Plant::model()
			->select(Plant::getSelection())
			->whereFarm(NULL)
			->getCollection();

		$cPlant->map(function(Plant $ePlant) use ($eFarm) {
			$ePlant['id'] = NULL;
			$ePlant['farm'] = $eFarm;
		});

		Plant::model()->insert($cPlant);

	}

	public static function countByFarm(\farm\Farm $eFarm): array {

		return Plant::model()
			->select([
				Plant::ACTIVE => new \Sql('SUM(status = "'.Plant::ACTIVE.'")', 'int'),
				Plant::INACTIVE => new \Sql('SUM(status = "'.Plant::INACTIVE.'")', 'int')
			])
			->whereFarm($eFarm)
			->get()
			->getArrayCopy() ?: [Plant::ACTIVE => 0, Plant::INACTIVE => 0];

	}

	public static function getByFarm(\farm\Farm $eFarm, ?Plant $sameAs = NULL, mixed $id = NULL, bool $selectMetadata = FALSE, ?array $properties = NULL, \Search $search = new \Search()): \Collection|\Element {

		if($sameAs !== NULL) {

			Plant::model()
				->whereId('!=', $sameAs)
				->whereLatinName($sameAs['latinName']);

		}

		$expects = 'collection';

		if($id !== NULL) {
			$expects = 'element';
			Plant::model()->whereId($id);
		}

		if($selectMetadata) {

			Plant::model()
				->select([
					'varieties' => Variety::model()
						->whereFarm($eFarm)
						->group('plant')
						->delegateProperty('plant', new \Sql('COUNT(*)', 'int')),
					'qualities' => Quality::model()
						->whereFarm($eFarm)
						->group('plant')
						->delegateProperty('plant', new \Sql('COUNT(*)', 'int'))
				]);

		}

		if($search->get('id')) {
			Plant::model()->whereId($search->get('id'));
		}

		if($search->get('ids')) {
			Plant::model()->whereId('IN', $search->get('ids'));
		}

		if($search->get('name')) {
			self::applyWhereName($search->get('name'));
		}

		if($search->get('status')) {
			Plant::model()->whereStatus($search->get('status'));
		} else {
			Plant::model()->whereStatus(Plant::ACTIVE);
		}

		Plant::model()
			->select($properties ?? Plant::getSelection())
			->whereFarm($eFarm)
			->sort('name');

		if($expects === 'element') {
			return Plant::model()->get();
		} else {
			return Plant::model()->getCollection(NULL, NULL, 'id');
		}

	}

	public static function getList(?array $properties = NULL): \Collection {

		return Plant::model()
			->select($properties ?? Plant::getSelection())
			->whereFarm(NULL)
			->sort('name')
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByFamily(Family $eFamily, \farm\Farm $eFarm): \Collection {

		return Plant::model()
			->select(Plant::getSelection())
			->whereFamily($eFamily)
			->whereFarm($eFarm)
			->sort('name')
			->getCollection(NULL, NULL, 'id');

	}

	public static function create(Plant $e): void {

		try {

			parent::create($e);

		} catch(\DuplicateException) {
			Plant::fail('name.duplicate');
		}

	}

	public static function update(Plant $e, array $properties): void {

		$e->expects(['farm']);

		Plant::model()->beginTransaction();

		parent::update($e, $properties);

		if($e['farm']->empty()) {

			$eBis = clone $e;
			$eBis->offsetUnset('id');

			Plant::model()
				->select($properties)
				->whereParent($e)
				->update($eBis);

		}


		Plant::model()->commit();
	}

	public static function delete(Plant $e): void {

		$e->expects(['id']);

		if(
			\production\Crop::model()
				->wherePlant($e)
				->exists() or
			\series\Cultivation::model()
				->wherePlant($e)
				->exists() or
			Variety::model()
				->wherePlant($e)
				->exists() or
			\analyze\Report::model()
				->wherePlant($e)
				->exists()
		) {
			Plant::fail('deleteUsed');
			return;
		}

		Plant::model()->beginTransaction();

		Variety::model()
			->wherePlant($e)
			->delete();

		Forecast::model()
			->wherePlant($e)
			->delete();

		Plant::model()->delete($e);

		Plant::model()->commit();

	}

}
?>
