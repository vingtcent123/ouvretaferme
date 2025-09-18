<?php
new Page()
	->cli('index', function($data) {

		$cCultivation = \series\Cultivation::model()
			->select(\series\Cultivation::getSelection())
			->getCollection();

		foreach($cCultivation as $eCultivation) {

			\series\TaskLib::recalculateHarvest($eCultivation['farm'], $eCultivation, $eCultivation['plant']);
			echo '.';

		}


		foreach($cCultivation as $eCultivation) {

			\series\SeriesLib::recalculate($eCultivation['farm'], $eCultivation['series']);
			echo '!';

		}

	});
?>