<?php
namespace plant;

class AnalyzeLib {

	public static function getArea(\farm\Farm $eFarm, array $seasons): array {

		$summary = [];

		$defaultBeds = [
			'beds' => 0,
			'bedsAnnual' => 0,
			'bedsPerennial' => 0,
			'bedsUnused' => 0,
			'bedsList' => '',
			'bedsArea' => 0,
			'bedsAreaField' => 0,
			'bedsAreaGreenhouse' => 0,
			'bedsAreaAnnual' => 0,
			'bedsAreaPerennial' => 0,
			'bedsAreaDevelopedAnnual' => 0,
			'bedsAreaDevelopedPerennial' => 0,
			'bedsAreaRateAnnual' => 0,
			'bedsAreaFieldRateAnnual' => 0,
			'bedsAreaGreenhouseRateAnnual' => 0,
			'bedsAreaRatePerennial' => 0,
			'bedsAreaFieldRatePerennial' => 0,
			'bedsAreaGreenhouseRatePerennial' => 0,
			'bedsPlants' => 0,
		];

		foreach($seasons as $season) {

			$summary[$season] = [];

			$cPlot = \map\Plot::model()
				->select('id')
				->or(
					fn() => $this->whereSeasonFirst(NULL),
					fn() => $this->whereSeasonFirst('<=', $season),
				)
				->or(
					fn() => $this->whereSeasonLast(NULL),
					fn() => $this->whereSeasonLast('>=', $season),
				)
				->whereFarm($eFarm)
				->getCollection();

			if($cPlot->empty()) {
				$summary[$season] = $defaultBeds;
			} else {

				\map\Bed::model()->pdo()->exec('SET SESSION group_concat_max_len = 1000000;');

				$beds = \map\Bed::model()
					->select([
						'beds' => new \Sql('COUNT(*)', 'int'),
						'bedsList' => new \Sql('GROUP_CONCAT("\"", m1.id,"\":", m1.area, "")'),
						'bedsArea' => new \Sql('SUM(m1.area)', 'int'),
						'bedsAreaGreenhouse' => new \Sql('SUM(IF(
							m1.greenhouse IS NOT NULL AND
							(m2.seasonFirst IS NULL OR m2.seasonFirst <= '.$season.') AND
							(m2.seasonLast IS NULL OR m2.seasonLast >= '.$season.'),
							m1.area, 0
						))', 'int')
					])
					->join(\map\Greenhouse::model(), 'm1.greenhouse = m2.id', type: 'LEFT')
					->or(
						fn() => $this->where('m1.seasonFirst', NULL),
						fn() => $this->where('m1.seasonFirst', '<=', $season),
					)
					->or(
						fn() => $this->where('m1.seasonLast', NULL),
						fn() => $this->where('m1.seasonLast', '>=', $season),
					)
					->where('m1.plot', 'IN', $cPlot)
					->where('m1.farm', $eFarm)
					->where('m1.zoneFill', FALSE)
					->wherePlotFill(FALSE)
					->get()
					->getArrayCopy();

				$summary[$season] += ($beds['beds'] === 0 ? [] : $beds) + $defaultBeds;

				$summary[$season]['bedsAreaField'] = $summary[$season]['bedsArea'] - $summary[$season]['bedsAreaGreenhouse'];

				foreach(\series\Place::model()
					->select([
						'beds' => new \Sql('COUNT(*)'),
						'plants' => new \Sql('COUNT(DISTINCT m3.plant)', 'int'),
						'bedsList' => new \Sql('GROUP_CONCAT(DISTINCT m1.bed)'),
						'area' => new \Sql('SUM(m1.area)', 'int'),
						'areaGreenhouse' => new \Sql('SUM(IF(
							m4.greenhouse IS NOT NULL AND
							(m5.seasonFirst IS NULL OR m5.seasonFirst <= '.$season.') AND
							(m5.seasonLast IS NULL OR m5.seasonLast >= '.$season.'),
							m1.area, 0
						))', 'int')
					])
					->join(\series\Series::model()
						->select('cycle'), 'm1.series = m2.id')
					->join(\series\Cultivation::model(), 'm2.id = m3.series')
					->join(\map\Bed::model(), 'm4.id = m1.bed')
					->join(\map\Greenhouse::model(), 'm4.greenhouse = m5.id', type: 'LEFT')
					->where('m1.farm', $eFarm)
					->where('m1.season', $season)
					->where('m4.plotFill', FALSE)
					->where('m4.zoneFill', FALSE)
					->group('m2.cycle')
					->getCollection() as $value) {

					$cycle = ucfirst($value['cycle']);
					$beds = explode(',', $value['bedsList']);
					array_walk($beds, fn(&$value) => $value = (int)$value);

					$summary[$season]['beds'.$cycle] = $value['beds'];
					$summary[$season]['bedsArea'.$cycle] = \map\Bed::model()
						->whereId('IN', $beds)
						->getValue(new \Sql('SUM(area)'));
					$summary[$season]['bedsAreaGreenhouse'.$cycle] = \map\Bed::model()
						->join(\map\Greenhouse::model(), 'm1.greenhouse = m2.id', type: 'LEFT')
						->where('m1.id', 'IN', $beds)
						->where('m1.greenhouse', '!=', NULL)
						->or(
							fn() => $this->where('m2.seasonFirst', NULL),
							fn() => $this->where('m2.seasonFirst', '<=', $season),
						)
						->or(
							fn() => $this->where('m2.seasonLast', NULL),
							fn() => $this->where('m2.seasonLast', '>=', $season),
						)
						->getValue(new \Sql('SUM(m1.area)'));
					$summary[$season]['bedsAreaField'.$cycle] = $summary[$season]['bedsArea'.$cycle] - $summary[$season]['bedsAreaGreenhouse'.$cycle];

					$summary[$season]['bedsPlants'] += $value['plants'];

					$summary[$season]['bedsAreaDeveloped'.$cycle] = $value['area'];
					$summary[$season]['bedsAreaGreenhouseDeveloped'.$cycle] = $value['areaGreenhouse'];
					$summary[$season]['bedsAreaFieldDeveloped'.$cycle] = $value['area'] - $value['areaGreenhouse'];

					$summary[$season]['bedsAreaRate'.$cycle] = ($summary[$season]['bedsArea'.$cycle] > 0) ? round($summary[$season]['bedsAreaDeveloped'.$cycle] / $summary[$season]['bedsArea'.$cycle], 1) : NULL;
					$summary[$season]['bedsAreaGreenhouseRate'.$cycle] = ($summary[$season]['bedsAreaGreenhouse'.$cycle] > 0) ? round($summary[$season]['bedsAreaGreenhouseDeveloped'.$cycle] / $summary[$season]['bedsAreaGreenhouse'.$cycle], 1) : NULL;
					$summary[$season]['bedsAreaFieldRate'.$cycle] = ($summary[$season]['bedsAreaField'.$cycle] > 0) ? round($summary[$season]['bedsAreaFieldDeveloped'.$cycle] / $summary[$season]['bedsAreaField'.$cycle], 1) : NULL;

				}

			}

		}

		$defaultBlocks = [
			'blocks' => 0,
			'blockAreaDevelopedAnnual' => 0,
			'blockAreaDevelopedPerennial' => 0,
			'blockPlants' => 0,
		];

		foreach($seasons as $season) {

			$blocks = \series\Place::model()
				->select([
					'blocks' => new \Sql('COUNT(*)', 'int'),
					'blockPlants' => new \Sql('COUNT(DISTINCT m3.plant)', 'int'),
					'blockAreaDevelopedAnnual' => new \Sql('SUM(IF(m2.cycle="'.\series\Series::ANNUAL.'", m1.area, 0))', 'int'),
					'blockAreaDevelopedPerennial' => new \Sql('SUM(IF(m2.cycle="'.\series\Series::PERENNIAL.'", m1.area, 0))', 'int'),
				])
				->join(\series\Series::model(), 'm1.series = m2.id')
				->join(\series\Cultivation::model(), 'm2.id = m3.series')
				->join(\map\Bed::model(), 'm4.id = m1.bed')
				->where('m1.farm', $eFarm)
				->where('m1.season', $season)
				->or(
					fn() => $this->where('m4.plotFill', TRUE),
					fn() => $this->where('m4.zoneFill', TRUE)
				)
				->get()
				->getArrayCopy() ?: [
					'blockAreaDevelopedAnnual' => 0,
					'blockAreaDevelopedPerennial' => 0,
					'blockPlants' => 0,
				];

			$summary[$season] += ($blocks['blocks'] === 0 ? [] : $blocks) + $defaultBlocks;

		}

		return $summary;

	}

	public static function getPlants(\farm\Farm $eFarm, int $season, \Search $search): \Collection {

		$ccCultivation = \series\Cultivation::model()
			->select([
				'season',
				'plant' => ['name', 'fqn', 'vignette'],
				'area' => new \Sql('SUM(m1.area)', 'int'),
				'areaPermanent' => new \Sql('SUM(m1.areaPermanent)', 'int'),
				'harvested' => new \Sql('CONCAT("[", GROUP_CONCAT(m1.harvestedByUnit SEPARATOR ","), "]")'),
			])
			->join(\series\Series::model(), 'm1.series = m2.id', 'LEFT')
			->where('m1.season', 'IN', [$season, $season - 1])
			->where('m1.area', '!=', NULL)
			->where('m1.farm', $eFarm)
			->where('m2.cycle', $search->get('cycle'), if: $search->get('cycle'))
			->where('m2.use', $search->get('use'), if: $search->get('use'))
			->group(new \Sql('m1.season, m1.plant'))
			->sort(new \Sql('m1_area DESC'))
			->getCollection(NULL, NULL, ['season', 'plant']);

		$ccCultivation->map(function($eCultivation) {

			if($eCultivation['harvested'] === NULL) {
				return;
			}

			$harvested = [];

			foreach(json_decode($eCultivation['harvested'], TRUE) as $harvestedOne) {

				foreach($harvestedOne as $unit => $value) {

					if($value <= 0) {
						continue;
					}

					if(array_key_exists($unit, $harvested) === FALSE) {
						$harvested[$unit] = 0;
					}

					$harvested[$unit] += $value;

				}

			}


			array_walk($harvested, function(&$value) {
				$value = round($value, 2);
			});

			ksort($harvested);

			$eCultivation['harvested'] = $harvested;

		}, 2);

		return $ccCultivation;

	}

	public static function getFamilies(\farm\Farm $eFarm, int $season, \Search $search): \Collection {

		return \series\Cultivation::model()
			->select([
				'season',
				'area' => new \Sql('SUM(m1.area)', 'int'),
				'areaPermanent' => new \Sql('SUM(m1.areaPermanent)', 'int')
			])
			->join(\series\Series::model(), 'm1.series = m2.id', 'LEFT')
			->join(Plant::model()
				->select([
					'family' => ['name', 'fqn']
				]), 'm1.plant = m3.id', 'LEFT')
			->where('m1.season', 'IN', $search->get('seasons') ? $search->get('seasons') : [$season, $season - 1])
			->where('m1.area', '!=', NULL)
			->where('m1.farm', $eFarm)
			->where('m2.cycle', $search->get('cycle'), if: $search->get('cycle'))
			->where('m2.use', $search->get('use'), if: $search->get('use'))
			->group(new \Sql('m1.season, m3.family'))
			->sort(new \Sql('m1_area DESC'))
			->getCollection(NULL, NULL, ['season', 'family']);

	}

}
?>
