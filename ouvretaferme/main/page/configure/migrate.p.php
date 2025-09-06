<?php
new Page()
	->cli('index', function($data) {

		$cCultivation = \series\Cultivation::model()
			->select('id', 'farm', 'series', 'plant', 'farm')
			->getCollection();

		foreach($cCultivation as $eCultivation) {

			if(\series\Slice::model()
				->whereCultivation($eCultivation)
				->exists()) {
				echo ' ';
				continue;
			}

			$eSlice = new \sequence\Slice([
				'farm' => $eCultivation['farm'],
				'series' => $eCultivation['series'],
				'cultivation' => $eCultivation,
				'plant' => $eCultivation['plant'],
				'variety' => new \plant\Variety(['id' => \plant\PlantSetting::VARIETY_UNKNOWN]),
				'partPercent' => 100
			]);

			\series\Slice::model()->insert($eSlice);

			\series\Cultivation::model()->update($eCultivation, [
				'sliceUnit' => \series\Cultivation::PERCENT
			]);

			echo '.';

		}

		$cCrop = \sequence\Crop::model()
			->select('id', 'farm', 'sequence', 'plant', 'farm')
			->getCollection();

		foreach($cCrop as $eCrop) {

			if(\sequence\Slice::model()
				->whereCrop($eCrop)
				->exists()) {
				echo ' ';
				continue;
			}

			$eSlice = new \sequence\Slice([
				'farm' => $eCrop['farm'],
				'sequence' => $eCrop['sequence'],
				'crop' => $eCrop,
				'plant' => $eCrop['plant'],
				'variety' => new \plant\Variety(['id' => \plant\PlantSetting::VARIETY_UNKNOWN]),
				'partPercent' => 100
			]);

			\sequence\Slice::model()->insert($eSlice);

			echo '.';

		}

	});
?>