<?php
namespace map;

class MapUi {

	public function getFarm(\farm\Farm $eFarm, int $season, \Collection $cZone, Zone $eZoneSelected) {

		$h = '';

		if($cZone->count() > 1) {

			$h .= '<div class="tabs-h" id="cartography-farm-tabs">';
				$h .= '<div class="tabs-item">';

					foreach($cZone as $eZone) {
						$h .= '<a '.attr('onclick', 'Cartography.get("cartography-farm").clickZone('.$eZone['id'].')').' data-url="'.\farm\FarmUi::urlCartography($eFarm, $season).'?zone='.$eZone['id'].'" class="tab-item '.(($eZoneSelected->notEmpty() and $eZoneSelected['id'] === $eZone['id']) ? 'selected' : '').'" id="cartography-farm-tab-'.$eZone['id'].'">';
							$h .= encode($eZone['name']);
						$h .= '</a>';
					}

				$h .= '</div>';
			$h .= '</div>';

		}

		$h .= $this->getMap($eFarm, $season, $cZone, $eZoneSelected);

		return $h;

	}

	public static function canCartography(\farm\Farm $eFarm): bool {
		return ($eFarm['placeLngLat'] !== NULL);
	}

	public function getMap(\farm\Farm $eFarm, int $season, \Collection $cZone, Zone $eZoneSelected): string {

		$cZone->expects(['cPlot']);

		$canCartography = self::canCartography($eFarm);

		MapboxUi::load();

		$h = '';

		if($canCartography) {

				$h .= '<div id="cartography-farm-container" class="'.($eZoneSelected->empty() ? '' : 'cartography-farm-container-hide').'">';
					$h .= '<div id="cartography-farm">';
						if($cZone->count() > 1) {
							$h .= '<a '.attr('onclick', 'Cartography.get("cartography-farm").unzoom()').' id="cartography-farm-zoom-out" class="hide">'.\Asset::icon('zoom-out').'</a>';
						}
					$h .= '</div>';
				$h .= '</div>';

			if($eFarm['placeLngLat'] !== NULL) {
				$center = '{
					zoom: 14,
					center: ['.$eFarm['placeLngLat'][0].', '.$eFarm['placeLngLat'][1].']
				}';
			} else {
				$center = '{}';
			}

		} else {
			$center = '{}';
		}

		$h .= '<script>';
			$h .= 'document.ready(() => setTimeout(() => {
				new Cartography("cartography-farm", '.$season.', true, '.($canCartography ? 'true' : 'false').', '.$center.')';


					if($canCartography) {

						$beds = '';

						foreach($cZone as $eZone) {

							$h .= '.addZone('.$eZone['id'].', "'.addcslashes($eZone['name'], '"').'", '.json_encode($eZone['coordinates']).')';

							foreach($eZone['cPlot'] as $ePlot) {

								if($ePlot['zoneFill'] === FALSE) {
									$h .= '.addPlot('.$ePlot['id'].', "'.addcslashes($ePlot['name'], '"').'", '.$eZone['id'].', '.json_encode($ePlot['coordinates']).')';
									$h .= '.eventPlot('.$ePlot['id'].', '.$eZone['id'].')';
								}

								$beds .= $this->addBeds($ePlot);

							}

							$h .= '.eventZone('.$eZone['id'].')';


						}

						if($beds) {
							$h .= $beds;
						}

						if($eZoneSelected->empty()) {
							$h .= '.fitFarmBounds({duration: 0})';
						} else {
							$h .= '.clickZone('.$eZoneSelected['id'].')';
						}

					} else {

						if($eZoneSelected->notEmpty()) {
							$h .= '.loadZone('.$eZoneSelected['id'].')';
							$h .= '.selectZone('.$eZoneSelected['id'].')';
						}

					}

				$h .= ';
			}, 100));';
		$h .= '</script>';

		return $h;

	}

	public function addBeds(Plot $ePlot, ?int $onlySeason = NULL, ?float $fillOpacity = NULL): string {

		if($ePlot['cDraw']->empty()) {
			return '';
		}

		if(
			$onlySeason !== NULL and
			$ePlot['cDraw']->first()['season'] !== $onlySeason
		) {
			return '';
		}

		$h = '';

		foreach($ePlot['cDraw'] as $eDraw) {

			$bedsSelected = $ePlot['cBed']
				->find(fn($eBed) => in_array($eBed['id'], $eDraw['beds']))
				->sort('name', natural: TRUE)
				->toArray(fn($eBed) => $eBed->extracts(['length', 'width', 'name']));

			if($bedsSelected) {
				$h .= '.addBeds("plot-'.$ePlot['id'].'-'.$eDraw['id'].'", '.json_encode($bedsSelected).', '.json_encode($eDraw['coordinates']).''.($fillOpacity !== NULL ? ', '.$fillOpacity : '').')';
			}

		}

		return $h;

	}

}
?>