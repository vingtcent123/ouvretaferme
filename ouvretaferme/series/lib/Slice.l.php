<?php
namespace series;

class SliceLib extends SliceCrud {

	public static function delegateByCultivation(): SliceModel {

		return Slice::model()
			->select([
				'variety' => \plant\Variety::getSelection(),
				'partPercent', 'partArea', 'partLength'
			])
			->delegateCollection('cultivation', callback: fn($c) => $c->sort(['variety' => ['name']], natural: TRUE));

	}

	public static function getByCultivation(Cultivation $eCultivation): \Collection {

		return Slice::model()
			->select([
				'variety' => ['name'],
				'partPercent', 'partArea', 'partLength'
			])
			->whereCultivation($eCultivation)
			->getCollection();

	}

	public static function getVarietiesByCultivations(\Collection $cCultivation): array {

		$ccSlice = Slice::model()
			->select([
				'cultivation',
				'variety' => ['name']
			])
			->whereCultivation('IN', $cCultivation)
			->getCollection(NULL, NULL, ['cultivation', NULL]);

		if($ccSlice->empty()) {
			return [
				'varietiesIntersect' => [],
				'cVariety' => new \Collection(),
			];
		}

		$varietiesIntersect = array_intersect(...$ccSlice->makeArray(fn($cSlice) => $cSlice->getColumnCollection('variety')->getIds()));

		$cVariety = new \Collection();

		foreach($ccSlice as $cSlice) {
			foreach($cSlice as $eSlice) {
				$eVariety = $eSlice['variety'];
				$cVariety[$eVariety['id']] = $eVariety;
			}
		}

		return [
			'varietiesIntersect' => $varietiesIntersect,
			'cVariety' => $cVariety,
		];

	}

	public static function createCollection(\Collection $c): void {

		$c->map(fn($e) => self::prepareCreate($e));

		try {
			Slice::model()->insert($c);
		} catch(\DuplicateException) {
			Cultivation::fail('variety.duplicate');
		}

	}

	public static function create(Slice $e): void {

		self::prepareCreate($e);

		parent::create($e);

	}

	private static function prepareCreate(Slice $e): void {

		$e->expects([
			'cultivation' => ['farm', 'plant', 'series']
		]);

		// Transfert de propriétés
		$e['farm'] = $e['cultivation']['farm'];
		$e['plant'] = $e['cultivation']['plant'];
		$e['series'] = $e['cultivation']['series'];

	}

	public static function deleteByCultivation(Cultivation $eCultivation): void {

		Slice::model()
			->whereCultivation($eCultivation)
			->delete();

	}

}
?>
