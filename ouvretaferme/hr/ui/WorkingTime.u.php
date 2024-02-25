<?php
namespace hr;

class WorkingTimeUi {

	public static function p(string $property): \PropertyDescriber {

		$d = WorkingTime::model()->describer($property);

		switch($property) {

			case 'time' :
				$d->append = s("h");
				break;

		}

		return $d;

	}

}
?>