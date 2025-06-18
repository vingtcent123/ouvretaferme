<?php
namespace main;

class PlaceLib {

	public static function searchCitiesByName(string $name): array {

		$arguments = [];

		$eUser = \user\ConnectionLib::getOnline();

		if($eUser->notEmpty()) {

			\user\Country::model()
				->select('code')
				->get($eUser['country']);

			$arguments['country'] = $eUser['country']['code'];

		}

		return \map\MapboxLib::getGeocodingPlaces($name, $arguments);

	}

}
