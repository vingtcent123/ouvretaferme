<?php
namespace map;

class DrawLib extends DrawCrud {

	public static function getByFarm(\farm\Farm $eFarm): \Collection {

		return Draw::model()
			->select(Draw::getSelection())
			->whereFarm($eFarm)
			->getCollection(index: ['plot', NULL]);

	}

	public static function createByBeds(Draw $e, \Collection $cBed): void {

		$e->expects([
			'season',
			'plot' => ['farm', 'zone'],
			'coordinates'
		]);

		$e['farm'] = $e['plot']['farm'];
		$e['zone'] = $e['plot']['zone'];
		$e['beds'] = $cBed->getIds();

		Draw::model()->beginTransaction();

		self::deleteByBeds($e, $cBed);

		parent::create($e);

		Draw::model()->commit();


	}

	public static function deleteByBeds(Draw $e, \Collection $cBed): void {

		$e->expects(['plot', 'season']);

		Draw::model()->beginTransaction();

		$cDraw = Draw::model()
			->select(['id', 'beds'])
			->wherePlot($e['plot'])
			->whereSeason($e['season'])
			->getCollection();

		$bedsDelete = $cBed->getIds();

		foreach($cDraw as $eDraw) {

			$newBeds = array_diff($eDraw['beds'], $bedsDelete);
			$newBeds = array_merge($newBeds);

			if(count($newBeds) === 0) {
				Draw::model()->delete($eDraw);
			} else if(count($newBeds) !== count($eDraw['beds'])) {

				Draw::model()->update($eDraw, [
					'beds' => $newBeds
				]);

			}

		}

		Draw::model()->commit();

	}

}
?>
