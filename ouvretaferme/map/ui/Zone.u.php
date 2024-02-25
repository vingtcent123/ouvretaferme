<?php
namespace map;

class ZoneUi {

	public function __construct() {

		\Asset::css('map', 'zone.css');

	}

	public function getHeader(Zone $eZone, int $season) {

		$eZone->expects(['cGreenhouse', 'cPlot']);

		$cPlot = $eZone['cPlot'];
		$ePlotFill = $cPlot->first(); // Bloc inféodé à la parcelle

		$h = '<div class="zone-cartography" id="zone-item-'.$eZone['id'].'" data-ref="zone" data-name="'.encode($eZone['name']).'">';

			$h .= '<div class="util-action">';
				$h .= '<h2>';
					$h .= s("Parcelle {value}", encode($eZone['name']));
					if($eZone['coordinates'] === NULL) {
						$h .= '<span class="zone-no-cartography">'.s("Non cartographiée").'</span>';
					}
				$h .= '</h2>';
				$h .= '<div>';
					if($eZone['farm']->canManage()) {
						$h .= '<a href="/map/bed:create?plot='.$ePlotFill['id'].'&season='.$season.'" class="dropdown-toggle btn btn-transparent">'.\Asset::icon('plus-circle').' '.s("Ajouter des planches").'</a>';
						$h .= ' <a data-dropdown="bottom-end" class="dropdown-toggle btn btn-transparent">'.\Asset::icon('gear-fill').'</a>';
						$h .= '<div class="dropdown-list bg-secondary">';
							$h .= '<div class="dropdown-title">'.encode($eZone['name']).'</div>';
							$h .= '<a href="/map/plot:create?zone='.$eZone['id'].'&season='.$season.'" class="dropdown-item">'.s("Ajouter un bloc à la parcelle").'</a>';
							$h .= '<a href="/map/greenhouse:create?farm='.$eZone['farm']['id'].'&plot='.$ePlotFill['id'].'" class="dropdown-item">'.s("Ajouter un abri à la parcelle").'</a>';
							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<a href="/map/zone:update?id='.$eZone['id'].'&season='.$season.'" class="dropdown-item">'.s("Modifier la parcelle").'</a>';
							$h .= '<a data-ajax="/map/zone:doDelete" post-id="'.$eZone['id'].'" post-season="'.$season.'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de la parcelle ?").'">'.s("Supprimer la parcelle").'</a>';
						$h .= '</div>';
					}
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div class="util-action-subtitle">';
				$h .= $this->getZoneUse($eZone);
			$h .= '</div>';

			$h .= (new GreenhouseUi())->getList($eZone['farm'], $eZone['cGreenhouse'], 'btn-transparent', 'bg-secondary');

		$h .= '</div>';

		return $h;

	}

	public function getList(\farm\Farm $eFarm, \Collection $cZone, int $season, \Search $search = new \Search()) {

		$eZoneSelected = $cZone->first();

		$h = '<div class="tabs-h" id="zone-tabs" onrender="'.encode('Lime.Tab.restore(this, "map-soil")').'">';

			$h .= '<div class="tabs-item">';

				foreach($cZone as $eZone) {

					$h .= '<a class="tab-item '.($eZone['id'] === $eZoneSelected['id'] ? 'selected' : '').'" data-tab="'.$eZone['id'].'" onclick="Lime.Tab.select(this)">';

						$h .= encode($eZone['name']);

						if($search->notEmpty(['cFamily'])) {

							$beds = $search->get('seen') === 0 ?
								$eZone['cPlot']->reduce(fn($ePlot, $v) => $ePlot['cBed']->find(fn($eBed) => ($eBed['plotFill'] === FALSE and $eBed['zoneFill'] === FALSE))->count() + $v, 0) :
								$eZone['cPlot']->reduce(fn($ePlot, $v) => $ePlot['cBed']->count() + $v, 0);

							if($beds > 0) {
								$h .= ' ('.$beds.')';
							}

						}

					$h .= '</a>';

				}

			$h .= '</div>';

			foreach($cZone as $eZone) {
				$h .= '<div class="tab-panel '.($eZone['id'] === $eZoneSelected['id'] ? 'selected' : '').'" data-tab="'.$eZone['id'].'">';
					$h .= '<div class="util-overflow-sm stick-sm">';
						$h .= $this->getOne($eFarm, $eZone, $season);
					$h .= '</div>';
				$h .= '</div>';
			}


		$h .= '</div>';

		return $h;

	}

	public function getOne(\farm\Farm $eFarm, Zone $eZone, int $season): string {

		$eZone->expects(['cGreenhouse', 'cPlot']);

		$cPlot = $eZone['cPlot'];

		$h = '<div class="zone-item" id="zone-item-'.$eZone['id'].'" data-ref="zone" data-name="'.encode($eZone['name']).'">';

			$h .= '<h2>';
				$h .= s("Parcelle {value}", encode($eZone['name']));
			$h .= '</h2>';
			$h .= '<div>';
				$h .= $this->getZoneUse($eZone);
			$h .= '</div>';

		$h .= '</div>';

		$h .= (new PlotUi())->getPlots($eFarm, $cPlot, $eZone, $season);

		return $h;

	}

	protected function getZoneUse(Zone $eZone): string {

		if($eZone['area'] > 1000) {
			$area = s("{value} ha", sprintf('%.02f', $eZone['area'] / 10000));
		} else {
			$area = s("{value} m²", $eZone['area']);
		}

		$h = $area;

		$interval = SeasonUi::getInterval($eZone);

		if($interval) {
			$h .= ' | '.$interval;
		}

		if($eZone['plots'] > 0) {
			$h .= ' | '.p("{value} bloc", "{value} blocs", $eZone['plots']);
		}

		if($eZone['beds'] > 0) {
			$h .= ' | '.p("{beds} planche permanente de {bedsArea} m²", "{beds} planches permanentes totalisant {bedsArea} m²", $eZone['beds'], ['beds' => $eZone['beds'], 'bedsArea' => $eZone['bedsArea']]);
		}

		return $h;

	}

	public function create(\farm\Farm $eFarm, \Collection $cZone): \Panel {

		$form = new \util\FormUi();

		$eZone = new Zone([
			'farm' => $eFarm
		]);

		$h = '';

		$h .= $form->openAjax('/map/zone:doCreate', ['id' => 'zone-create']);

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->hidden('season', \Setting::get('main\onlineSeason'));

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eFarm, TRUE)
			);

			$h .= $form->dynamicGroups($eZone, ['name', 'seasonFirst']);

			$label = '<p>'.s("Dessiner sur la carte").'</p>';
			$helper = '<p class="util-helper">'.s("Pour dessiner cette parcelle sur la carte, cliquez une première fois sur la carte, puis ajoutez autant de points que vous voulez. Une fois que vous avez terminé, cliquez sur le point initial pour refermer la parcelle.").'</p>';
			$helper .= '<p class="util-helper">'.s("Pour modifier un point existant, cliquez dessus et vous pourrez ensuite la déplacer.").'</p>';

			$h .= $this->drawMap($form, $eZone, $label, $helper, $cZone);

			$h .= $form->group(
				content: $form->submit(s("Créer la parcelle"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter une parcelle"),
			body: $h
		);

	}

	public function update(Zone $eZone, \Collection $cZone): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/map/zone:doUpdate', ['id' => 'zone-update']);

			$h .= $form->hidden('id', $eZone['id']);
			$h .= $form->hidden('season', \Setting::get('main\onlineSeason'));

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eZone['farm'], TRUE)
			);

			$h .= $form->dynamicGroup($eZone, 'name');

			$h .= $form->group(
				s("Exploité"),
				(new SeasonUi)->getField($form, $eZone),
				['wrapper' => 'seasonFirst seasonLast']
			);

			$label = '<p>'.s("Dessiner sur la carte").'</p>';
			$helper = '<p class="util-helper">'.s("Pour modifier un point existant, cliquez dessus et vous pourrez ensuite le déplacer.").'</p>';

			$h .= $this->drawMap($form, $eZone, $label, $helper, $cZone);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une parcelle"),
			body: $h
		);

	}

	public function getZonePlotWidget(\util\FormUi $form, \Collection $cZone, Plot $ePlot = new Plot(), ?string $placeholder = NULL): string {

		$h = '<div>';

			$select = [];

			foreach($cZone as $eZoneSelect) {

				$selectPlots = [];

				foreach($eZoneSelect['cPlot'] as $ePlotSelect) {

					if($ePlotSelect['zoneFill'] === FALSE) {

						$selectPlots[] = [
							'value' => $ePlotSelect['id'],
							'label' => '> '.s("Bloc {value}", $ePlotSelect['name']),
							'attributes' => ['data-zone' => $eZoneSelect['id'], 'data-plot' => $ePlotSelect['id']]
						];

					} else {

						$select[] = [
							'value' => $ePlotSelect['id'],
							'label' => s("Parcelle {value}", $eZoneSelect['name']),
							'attributes' => ['style' => 'font-weight: bold; background-color: var(--background)', 'data-zone' => $eZoneSelect['id']]
						];

					}

				}

				$select = array_merge($select, $selectPlots);

			}

			$h .= $form->select('plot', $select, $ePlot, [
				'placeholder' => $placeholder
			]);

		$h .= '</div>';

		return $h;

	}

	public function drawMap(\util\FormUi $form, Zone $eZone, string $label, string $helper, \Collection $cZone): string {

		$eZone->expects([
			'farm' => ['placeLngLat']
		]);

		$canCartography = MapUi::canCartography($eZone['farm']);

		if($canCartography === FALSE) {

			$map = '<div class="form-control-block">';
				$map .= '<p>'.s("Si vous souhaitez dessiner votre parcelle sur la carte, vous devez d'abord renseigner le siège d'exploitation de la ferme. Vous pouvez aussi sauter cette étape et saisir directement la surface de cette parcelle.").'</p>';
				$map .= '<a href="/farm/farm:update?id='.$eZone['farm']['id'].'" class="btn btn-outline-secondary">'.s("Renseigner le siège d'exploitation").'</a>';
			$map .= '</div>';

		} else {

			$eZone->add([
				'id' => NULL,
				'area' => NULL,
				'coordinates' => NULL
			]);


			$container = 'zone-map-write';

			$map = '<div class="form-control-block" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0">';
				$map .= s("Dessiner cette parcelle sur la carte n'est pas obligatoire, vous pouvez sauter cette étape et saisir directement la surface de la parcelle.");
			$map .= '</div>';

			$map .= (new MapboxUi())->getDrawingPolygon($container, $form, $eZone);

			$map .= '<script>';
				$map .= 'document.ready(() => setTimeout(() => {
					new Cartography("'.$container.'", '.$eZone['farm']['seasonLast'].', false, true, {
							zoom: 14,
							scrollZoom: true,
							center: ['.$eZone['farm']['placeLngLat'][0].', '.$eZone['farm']['placeLngLat'][1].']
						})';
						if($cZone->notEmpty()) {
							foreach($cZone as $eZoneDisplay) {
								$display = ($eZone['id'] === NULL or $eZone['id'] !== $eZoneDisplay['id']);
								$map .= '.addZone('.$eZoneDisplay['id'].', "'.addcslashes($eZoneDisplay['name'], '"').'", '.json_encode($eZoneDisplay['coordinates']).', '.($display ? 'true' : 'false').')';
							}
							if($eZone['id'] === NULL) {
								$map .= '.fitFarmBounds({duration: 0})';
							} else {
								$map .= '.fitZoneBounds('.$eZone['id'].', {duration: 0})';
							}
						}
					$map .= '.drawShape()';
					$map .= '.drawPolygon('.json_encode($eZone['coordinates']).');
				}, 100));';
			$map .= '</script>';

		}

		if($canCartography) {
			$label .= $helper;
		}

		$h = $form->group($label, $map, ['wrapper' => 'coordinates']);
		$h .= $form->dynamicGroup($eZone, 'area');
		
		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Zone::model()->describer($property, [
			'name' => s("Nom de la parcelle"),
			'farm' => s("Ferme"),
			'area' => s("Surface de la parcelle"),
			'seasonFirst' => s("Exploité depuis"),
			'seasonLast' => s("Exploité jusqu'à"),
			'createdAt' => s("Créé le"),
		]);

		switch($property) {

			case 'name' :
				$d->attributes = [
					'placeholder' => s("Ex. : Lieu-dit de Verteprairie")
				];
				break;

			case 'area' :
				$d->append = s("m²");
				break;

			case 'seasonFirst' :
			case 'seasonLast' :
				$d->field = function(\util\FormUi $form, \Element $e, $property) {

					$placeholder = [
						'seasonFirst' => s("la nuit des temps"),
						'seasonLast' => s("la fin des temps")
					][$property];

					return (new SeasonUi())->getDescriberField($form, $e, $e['farm'], NULL, NULL, $property, $placeholder);
				};
				break;

		}

		return $d;

	}

}
?>
