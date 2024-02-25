<?php
namespace map;

class MapboxUi {

	public static function load() {

		\Asset::js('map', 'cartography.js');
		\Asset::css('map', 'cartography.css');

		\Asset::jsUrl('https://npmcdn.com/@turf/turf@6.5.0/turf.min.js');

		\Asset::jsUrl('https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js');
		\Asset::js('map', 'mapbox.js');

		\Asset::cssUrl('https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css');
		\Asset::css('map', 'mapbox.css');

		\Asset::jsContent('<script>
			mapboxToken = "'.\Setting::get('map\mapboxToken').'";
			mapboxgl.accessToken = "'.\Setting::get('map\mapboxToken').'";
		</script>');

	}

	public function getDrawingPolygon(string $container, \util\FormUi $form, Plot|Zone $e, bool $shapes = FALSE): string {

		$e->expects(['id', 'area', 'coordinates']);

		self::load();

		\Asset::jsUrl('https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.4.2/mapbox-gl-draw.js');
		\Asset::js('map', 'rotate.js');

		\Asset::cssUrl('https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-draw/v1.4.2/mapbox-gl-draw.css');

		$h = '<div class="mapbox-polygon-container">';

			$h .= '<div id="'.$container.'"></div>';

			if($shapes) {

				$form = new \util\FormUi();

				$h .= '<div id="mapbox-polygon-shapes" class="'.($e['coordinates'] ? 'hide' : '').'">';
					$h .= '<a '.attr('onclick', 'Cartography.get("'.$container.'").drawPolygon(null)').' class="mapbox-polygon-shape-link">'.\Asset::icon('pencil-fill').'<span>'.s("Forme libre").'</span></a>';
					$h .= '<a class="mapbox-polygon-shape-link" data-dropdown="bottom-center">'.\Asset::icon('bounding-box').'<span>'.s("Rectangle").'</span></a>';
					$h .= '<div class="dropdown-list dropdown-list-minimalist mapbox-polygon-shape-form" data-dropdown-keep>';
						$h .= '<h4>'.s("Rectangle").'</h4>';
						$h .= '<div class="form-columns form-columns-33">';
							$h .= $form->group(
								s("Longueur"),
								$form->inputGroup(
									$form->number('length').$form->addon(s("m"))
								)
							);
							$h .= $form->group(
								s("Largeur"),
								$form->inputGroup(
									$form->number('width').$form->addon(s("m"))
								)
							);
							$h .= $form->button(s("Valider"), ['onclick' => 'Cartography.get("'.$container.'").drawRectangle(this)']);
							$h .= '</div>';
					$h .= '</div>';
				$h .= '</div>';

			}

			$h .= '<div id="'.$container.'-actions" class="mapbox-polygon-actions '.($e['coordinates'] ? '' : 'hide').'">';
				$h .= '<a '.attr('onclick', 'Cartography.get("'.$container.'").setPolygonMode("draw")').' class="mapbox-polygon-action mapbox-polygon-action-draw">'.\Asset::icon('bounding-box').'<span>'.s("Modifier").'</span></a>';
				$h .= '<a '.attr('onclick', 'Cartography.get("'.$container.'").setPolygonMode("rotate")').' class="mapbox-polygon-action mapbox-polygon-action-rotate">'.\Asset::icon('arrow-clockwise').'<span>'.s("Rotation").'</span></a>';
				$h .= '<a '.attr('onclick', 'Cartography.get("'.$container.'").deletePolygon()').' class="mapbox-polygon-action mapbox-polygon-action-rotate">'.\Asset::icon('trash').'<span>'.s("Recommencer").'</span></a>';
			$h .= '</div>';

		$h .= '</div>';

		$h .= $form->hidden('coordinates', json_encode($e['coordinates']));

		return $h;

	}

	public function getDrawingBedLine(string $container, \util\FormUi $form): string {

		self::load();

		$h = '<div class="mapbox-polygon-container">';

			$h .= '<div id="'.$container.'"></div>';

			$h .= '<div id="'.$container.'-actions" class="mapbox-polygon-actions hide">';
				$h .= '<a '.attr('onclick', 'Cartography.get("'.$container.'").deleteBeds()').' class="mapbox-polygon-action mapbox-polygon-action-rotate">'.\Asset::icon('trash').'<span>'.s("Recommencer").'</span></a>';
			$h .= '</div>';

		$h .= '</div>';

		$h .= $form->hidden('coordinates');

		return $h;

	}

}
?>
