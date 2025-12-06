<?php
namespace main;

class PlaceUi {

	public function __construct() {
		\Asset::css('main', 'place.css');
		\Asset::js('main', 'place.js');
	}


	public function query(\PropertyDescriber $d) {

		$mapId = uniqid('place-map-');
		$lngLatId = uniqid('place-reference-');

		$d->prepend = \Asset::icon('geo-fill');
		$d->field = 'autocomplete';
		$d->group = function() {
			return ['wrapper' => 'cultivationPlace'];
		};

		$d->placeholder = s("Tapez une ville...");
		$d->attributes = [
			'data-autocomplete-map' => $mapId,
			'data-autocomplete-lnglat' => $lngLatId
		];

		$d->autocompleteUrl = '/main/place:cities';
		$d->autocompleteResults = function($place) {
			return [
				'itemText' => $place,
				'value' => $place
			];
		};

		$d->after = function(\util\FormUi $form, \Element $e) use($mapId, $lngLatId) {

			$valueLngLat = $e['cultivationLngLat'] ?? NULL;

			// Doit être décrit en premier pour que ce soit le champ 'place' qui communique son nom au groupe
			$h = $form->hidden('cultivationLngLat', $valueLngLat ? json_encode($valueLngLat) : '', ['id' => $lngLatId]);

			\map\MapboxUi::load();

			$h .= '<div id="'.$mapId.'" class="place-map"></div>';

			if($e['cultivationLngLat'] ?? NULL) {

				$h .= '<script>';
					$h .= 'document.ready(() => setTimeout(() => {';
						$h .= 'Place.updateMap("'.$mapId.'", ['.implode(',', $e['cultivationLngLat']).']);';
					$h .= '}, 100));';
				$h .= '</script>';

			}

			return $h;

		};

	}

}