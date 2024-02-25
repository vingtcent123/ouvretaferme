<?php
namespace map;

class BedLib extends BedCrud {

	public static function getPropertiesUpdate(): array {
		return ['plot', 'name', 'length', 'width', 'seasonFirst', 'seasonLast'];
	}

	public static function buildFromNames(Bed $eBedSource, array $names): \Collection {

		$eBedSource->expects([
			'plot' => ['farm']
		]);

		$cBed = new \Collection();

		foreach($names as &$name) {

			Bed::model()->cast('name', $name);

			if(
				$name !== NULL and
				Bed::model()->check('name', $name)
			) {

				$eBed = clone $eBedSource;
				$eBed['name'] = $name;

				$cBed[] = $eBed;

			} else {
				Bed::fail('names.size');
				return new \Collection();
			}

		}

		if($cBed->empty()) {
			Bed::fail('names.check');
		} else if(count(array_count_values($names)) < count($names)) {
			Bed::fail('names.different');
		} else {

			$namesExisting = Bed::model()
				->wherePlot($eBedSource['plot'])
				->whereName('IN', $names)
				->getColumn('name');

			if($namesExisting) {
				Bed::fail('names.duplicate', [$namesExisting]);
			}
		}

		return $cBed;

	}

	public static function buildIds(Plot $ePlot, array $ids): \Collection {

		$ePlot->expects('id');

		if($ids === []) {
			Bed::fail('ids.check');
			return new \Collection();
		}

		$cBed = new \Collection();

		foreach($ids as $id) {

			Bed::model()->cast('id', $id);

			if(Bed::model()->check('id', $id)) {

				$eBed = new Bed([
					'id' => $id
				]);

				$cBed[] = $eBed;

			} else {
				Bed::fail('ids.check');
				return new \Collection();
			}

		}

		if($cBed->empty()) {
			Bed::fail('ids.check');
		}

		// On vérifie que toutes les planches appartiennent bien au bloc
		Bed::model()
			->select(['plot', 'name', 'length', 'width'])
			->get($cBed);

		if(Bed::model()->found() !== $cBed->count()) {
			Bed::fail('ids.check');
		} else {

			foreach($cBed as $eBed) {

				if($eBed['plot']['id'] !== $ePlot['id']) {
					Bed::fail('ids.check');
				}

			}

		}

		return $cBed;

	}

	public static function getByFarm(\farm\Farm $eFarm, int $season): \Collection {

		SeasonLib::whereSeason(Bed::model(), $season);

		$cPlot = PlotLib::getByFarm($eFarm, season: $season);

		return Bed::model()
			->select(Bed::getSelection())
			->whereFarm($eFarm)
			->where('plot', 'IN', $cPlot)
			->where('plotFill', FALSE)
			->where('zoneFill', FALSE)
			->getCollection(index: 'id')
			->sort('name');

	}

	public static function createCollection(\Collection $c): void {

		$c->map(fn($e) => self::prepareCreate($e));

		try {
			Bed::model()->insert($c);
		} catch(\DuplicateException) {
			Bed::fail('names.duplicateAnonymous');
		}

	}

	public static function create(Bed $e): void {

		self::prepareCreate($e);

		Bed::model()->insert($e);

	}

	private static function prepareCreate(Bed $e): void {

		$e->expects([
			'plot' => ['farm', 'zone'],
		]);

		$e['zone'] = $e['plot']['zone'];
		$e['farm'] = $e['plot']['farm'];

		if(
			$e['plot']['zoneFill'] === FALSE and
			$e['plot']['mode'] === Plot::GREENHOUSE
		) {
			$e['greenhouse'] = Greenhouse::model()
				->select('id')
				->wherePlot($e['plot'])
				->get();
		}

	}

	public static function updateCollection(\Collection $c, \Element $e, array $properties): void {

		self::prepareUpdate($e, $properties);

		Bed::model()->beginTransaction();

		parent::updateCollection($c, $e, $properties);

		if(in_array('plot', $properties)) {

			\series\Place::model()
				->whereBed($c)
				->update([
					'plot' => $e['plot']
				]);

		}

		Bed::model()->commit();

	}

	public static function update(Bed $e, array $properties = []): void {

		self::prepareUpdate($e, $properties);

		Bed::model()->beginTransaction();

		parent::update($e, $properties);

		if(in_array('plot', $properties)) {

			\series\Place::model()
				->whereBed($e)
				->update([
					'plot' => $e['plot']
				]);

		}

		Bed::model()->commit();

	}

	private static function prepareUpdate(\Element $e, array &$properties) {

		$e['updatedAt'] = new \Sql('NOW()');
		$properties[] = 'updatedAt';

		if(in_array('length', $properties) or in_array('width', $properties)) {

			$e['area'] = $e['length'] * $e['width'] / 100;
			$properties[] = 'area';

		}


		if(in_array('plot', $properties)) {

			$e->expects(['oldPlot']);

			if($e['plot']['id'] !== $e['oldPlot']['id']) {

				if($e['plot']['mode'] === Plot::GREENHOUSE) {

					$eGreenhouse = Greenhouse::model()
						->select('id')
						->wherePlot($e['plot'])
						->get();

				} else {
					$eGreenhouse = new Greenhouse();
				}

				$e['greenhouse'] = $eGreenhouse;
				$properties[] = 'greenhouse';

			}


		}

	}

	public static function deleteCollection(\Collection $c): void {

		if(\series\Place::model()
				->whereBed('IN', $c)
				->exists()) {
			Bed::fail('deleteUsed');
			return;
		}

		Bed::model()
			->wherePlotFill(FALSE)
			->delete($c);

	}

	public static function delete(Bed $e): void {

		$e->expects(['id']);

		if(\series\Place::model()
				->whereBed($e)
				->exists()) {
			Bed::fail('deleteUsed');
			return;
		}

		Bed::model()
			->wherePlotFill(FALSE)
			->delete($e);

	}

	public static function swapSeries(int $season, Bed $e1, Bed $e2): void {

		$tmp = 999999999 + $e1['id'];

		\series\Place::model()->beginTransaction();

		$affectedTmp = \series\Place::model()
			->whereSeason($season)
			->whereBed($e1)
			->update([
				'bed' => $tmp
			]);

		\series\Place::model()
			->whereSeason($season)
			->whereBed($e2)
			->update([
				'bed' => $e1,
				'plot' => $e1['plot'],
				'zone' => $e1['zone']
			]);

		if($affectedTmp > 0) {

			\series\Place::model()
				->whereSeason($season)
				->whereBed($tmp)
				->update([
					'bed' => $e2,
					'plot' => $e2['plot'],
					'zone' => $e2['zone']
				]);

		}

		\series\Place::model()->commit();

	}

}
?>
