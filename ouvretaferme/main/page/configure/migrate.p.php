<?php
(new Page())
	->cli('index', function($data) {

		$cCultivation = \series\Cultivation::model()
			->select(\series\Cultivation::getSelection())
			->whereDistance(\series\Cultivation::SPACING)
			->getCollection();

		foreach($cCultivation as $eCultivation) {

			\series\Cultivation::model()->update($eCultivation, [
				'density' => \production\CropLib::calculateDensity($eCultivation, $eCultivation['series'])
			]);

		}

		$cCrop = \production\Crop::model()
			->select(\production\Crop::getSelection() + [
				'sequence' => \production\SequenceElement::getSelection()
			])
			->whereDistance(\production\Crop::SPACING)
			->getCollection();

		foreach($cCrop as $eCrop) {

			\production\Crop::model()->update($eCrop, [
				'density' => \production\CropLib::calculateDensity($eCrop, $eCrop['sequence'])
			]);

		}

	});
?>