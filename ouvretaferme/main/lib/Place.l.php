<?php
namespace main;

class PlaceLib {

	public static function searchCitiesByName(\farm\Farm $eFarm, string $name): array {

		$arguments = [];

		if($eFarm->notEmpty()) {

			\user\Country::model()
				->select('code')
				->get($eFarm['legalCountry']);

			$arguments['country'] = $eFarm['legalCountry']['code'];

		}

		return \map\MapboxLib::getGeocodingPlaces($name, $arguments);

	}

}
