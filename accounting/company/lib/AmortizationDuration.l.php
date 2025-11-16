<?php
namespace company;

class AmortizationDurationLib extends AmortizationDurationCrud {

	public static function getAll(): \Collection {

		return AmortizationDuration::model()
			->select(AmortizationDuration::getSelection())
			->getCollection(NULL, NULL, 'class');

	}

}
?>
