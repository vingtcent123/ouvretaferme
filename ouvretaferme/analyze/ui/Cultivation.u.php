<?php
namespace analyze;

class CultivationUi {

	public static function p(string $property): \PropertyDescriber {

		$d = Cultivation::model()->describer($property);

		switch($property) {

			case 'area' :
				$d->append = s("m²");
				break;

			case 'workingTime' :
				$d->append = s("h");
				break;

			case 'costs' :
			case 'turnover' :
				$d->append = s("€");
				break;

		}

		return $d;

	}

}
?>
