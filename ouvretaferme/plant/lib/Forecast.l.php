<?php
namespace plant;

class ForecastLib extends ForecastCrud {

	public static function getPropertiesCreate(): array {
		return array_merge(['plant', 'unit'], self::getPropertiesUpdate());
	}

	public static function getPropertiesUpdate(): array {
		// Private avant Pro pour les vérifications dans build()
		return ['harvestObjective', 'privatePrice', 'privatePart', 'proPrice', 'proPart'];
	}

	public static function getByFarm(\farm\Farm $eFarm, int $season, \Collection $cccCultivation): \Collection {

		$ccForecast = self::getReadOnlyByFarm($eFarm, $season, Forecast::getSelection() + [
				'cCultivation' => fn() => new \Collection()
		]);

		// Pas de prévisionnel, on copie celui de l'année dernière
		if($ccForecast->empty()) {

			$maxSeason = Forecast::model()
				->whereFarm($eFarm)
				->getValue(new \Sql('MAX(season)', 'int'));

			if($maxSeason !== NULL) {

				$cForecast = Forecast::model()
					->select(ForecastElement::getSelection())
					->whereFarm($eFarm)
					->whereSeason($maxSeason)
					->getCollection();

				foreach($cForecast as $eForecast) {

					$eForecast['id'] = NULL;
					$eForecast['season'] = $season;

					if($maxSeason > $season) {
						$eForecast['harvestObjective'] = NULL;
					}

					Forecast::model()
						->option('add-ignore')
						->insert($eForecast);

				}

				$ccForecast = self::getReadOnlyByFarm($eFarm, $season, Forecast::getSelection() + [
						'cCultivation' => fn() => new \Collection()
				]);

			} else {

				// Premier accès
				\Cache::redis()->set('help-forecast-'.$eFarm['id'], 'valid', 86400 * 14);

			}

		}

		foreach($cccCultivation as $ccCultivation) {

			$ePlant = $ccCultivation->first()->first()['plant'];

			foreach($ccCultivation as $unit => $cCultivation) {

				$eForecast = $ccForecast[$ePlant['id']][$unit] ?? new Forecast();

				if($eForecast->empty()) {

					$eForecastBefore = Forecast::model()
						->select([
							'harvestObjective',
							'privatePrice', 'privatePart',
							'proPrice', 'proPart'
						])
						->whereFarm($eFarm)
						->wherePlant($ePlant)
						->whereUnit($unit)
						->sort(['season' => SORT_DESC])
						->get();

					$eForecast->merge([
						'farm' => $eFarm,
						'season' => $season,
						'plant' => $ePlant,
						'unit' => $unit,
					]);

					$eForecast->merge($eForecastBefore);

					Forecast::model()
						->option('add-ignore')
						->insert($eForecast);

					$ccForecast[$ePlant['id']] ??= new \Collection();
					$ccForecast[$ePlant['id']][$unit] = $eForecast;

				}

				$eForecast['cCultivation'] = $cCultivation;

			}

		}

		\series\CultivationLib::orderByPlant($ccForecast);

		return $ccForecast;

	}

	public static function getReadOnlyByFarm(\farm\Farm $eFarm, int $season, ?array $select = NULL): \Collection {

		return Forecast::model()
			->select($select ?? Forecast::getSelection())
			->whereFarm($eFarm)
			->whereSeason($season)
			->getCollection(NULL, NULL, ['plant', 'unit']);

	}

	public static function countCultivations(Forecast $e): int {

		$e->expects(['farm', 'season', 'plant', 'unit']);

		return \series\Cultivation::model()
			->whereFarm($e['farm'])
			->whereSeason($e['season'])
			->wherePlant($e['plant'])
			->whereMainUnit($e['unit'])
			->count();

	}

	public static function create(Forecast $e): void {

		try {

			parent::create($e);

		} catch(\DuplicateException) {
			Forecast::fail('plant.duplicate');
		}

	}

	public static function update(Forecast $e, array $properties): void {

		if(in_array('proPart', $properties) === TRUE and in_array('privatePart', $properties) === FALSE) {

			$e['privatePart'] = 100 - $e['proPart'];
			$properties[] = 'privatePart';

		}

		if(in_array('privatePart', $properties) === TRUE and in_array('proPart', $properties) === FALSE) {

			$e['proPart'] = 100 - $e['privatePart'];
			$properties[] = 'proPart';

		}

		parent::update($e, $properties);

	}

	public static function delete(Forecast $e): void {

		$e->expects(['id']);

		if(self::countCultivations($e) > 0) {
			Forecast::fail('deleteUsed');
			return;
		}

		Forecast::model()->delete($e);

	}

}
?>
