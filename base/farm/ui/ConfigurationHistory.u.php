<?php
namespace farm;

Class ConfigurationHistoryUi {

	public static function p(string $property): \PropertyDescriber {

		$d = ConfigurationHistory::model()->describer($property, [
			'effectiveAt' => s("Depuis quand appliquer cette redevabilité à la TVA ?"),
		]);

		switch($property) {

			case 'effectiveAt' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

		}

		return $d;

	}
}
