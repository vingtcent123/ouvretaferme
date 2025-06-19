<?php
namespace series;

class SliceUi {

	public static function p(string $property): \PropertyDescriber {

		$d = Slice::model()->describer($property);

		return $d;

	}


}
?>
