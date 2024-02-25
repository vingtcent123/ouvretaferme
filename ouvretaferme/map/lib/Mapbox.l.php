<?php
namespace map;

class MapboxLib {

	public static function getGeocodingPlaces(string $query, array $arguments = []): array {

		$arguments += [
			'autocomplete' => 'true',
			'language' => 'fr',
			'types' => 'place',
			'limit' => 12
		];

		$response = self::call('/geocoding/v5/mapbox.places/'.urlencode($query).'.json', $arguments);

		return $response['features'] ?? [];

	}

	protected static function call(string $uri, array $arguments = []): array {

		$arguments['access_token'] = \Setting::get('map\mapboxToken');

		$url = 'https://api.mapbox.com'.$uri;
		$values = (new \util\CurlLib())->exec($url, $arguments);

		return json_decode($values, TRUE);

	}

}
