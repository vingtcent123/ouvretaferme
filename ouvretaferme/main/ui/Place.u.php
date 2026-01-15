<?php
namespace main;

class PlaceUi {

	public function __construct() {
		\Asset::css('main', 'place.css');
		\Asset::js('main', 'place.js');
	}

	public static function querySiret(\PropertyDescriber $d, string $prefix): void {

		\Asset::js('main', 'place.js');

		$d->placeholder = s("Ex. : {value}", '123 456 789 00013');

		$h = '<div class="util-block siret-found hide mt-1">';
			$h .= '<h4>'.s("Nous avons trouvé ce SIRET dans la base de données de l'administration :").'</h4>';
			$h .= '<dl class="util-presentation util-presentation-1">';
				$h .= '<dt>'.s("Raison sociale").'</dt>';
				$h .= '<dd class="siret-name"></dd>';
				$h .= '<dt>'.s("Adresse du siège social").'</dt>';
				$h .= '<dd>';
					$h .= '<div class="siret-street1"></div>';
					$h .= '<div class="siret-street2"></div>';
					$h .= '<div>';
						$h .= '<span class="siret-postcode"></span> ';
						$h .= '<span class="siret-city"></span>';
					$h .= '</div>';
				$h .= '</dd>';
			$h .= '</dl>';
			$h .= '<a '.attr('onclick', 'Place.fillSiret(this, "'.$prefix.'")').' class="btn btn-secondary btn-sm mt-1">'.s("Utiliser ces informations").'</a>';
		$h .= '</div>';
		$h .= '<div class="siret-unknown hide mt-1">';
			$h .= '<div class="util-warning">'.s("Nous n'avons pas trouvé ce SIRET dans la base de données de l'administration. Nous vous incitons à vérifier votre saisie mais vous pouvez toujours l'utiliser si vous pensez qu'il est correct.").'</div>';
		$h .= '</div>';

		$d->after = $h;
		$d->labelAfter = \util\FormUi::info(s("Commencez par saisir le numéro de SIRET pour que le logiciel trouve automatiquement les autres informations."));
		$d->attributes['oninput'] = fn(\util\FormUi $form, \Element $e) => 'Place.querySiret(this);';

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