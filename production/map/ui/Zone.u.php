<?php
namespace map;

class ZoneUi {

	private \series\Series|\series\Task $eUpdate;

	public function __construct() {

		\Asset::css('map', 'zone.css');
		\Asset::js('map', 'zone.js');

		$this->eUpdate = new \series\Series();

	}

	public function setUpdate(\series\Series|\series\Task $e): ZoneUi {

		$e->expects(['cPlace']);

		$this->eUpdate = $e;

		return $this;

	}

	public function getHeader(Zone $eZone, int $season) {

		$eZone->expects(['cGreenhouse', 'cPlot']);

		$cPlot = $eZone['cPlot'];
		$ePlotFill = $cPlot->first(); // Jardin inféodé à la parcelle

		$h = '<div class="zone-cartography" id="zone-title-'.$eZone['id'].'" data-ref="zone" data-name="'.encode($eZone['name']).'">';

			$h .= '<div class="util-title">';
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
							$h .= '<a href="/map/plot:create?zone='.$eZone['id'].'&season='.$season.'" class="dropdown-item">'.s("Ajouter un jardin à la parcelle").'</a>';
							$h .= '<a href="/map/greenhouse:create?farm='.$eZone['farm']['id'].'&plot='.$ePlotFill['id'].'" class="dropdown-item">'.s("Ajouter un abri à la parcelle").'</a>';
							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<a href="/map/zone:update?id='.$eZone['id'].'&season='.$season.'" class="dropdown-item">'.s("Modifier la parcelle").'</a>';
							$h .= '<a data-ajax="/map/zone:doDelete" post-id="'.$eZone['id'].'" post-season="'.$season.'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de la parcelle ?").'">'.s("Supprimer la parcelle").'</a>';
						$h .= '</div>';
					}
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div class="util-subtitle">';
				$h .= $this->getZoneUse($eZone);
			$h .= '</div>';

			$h .= new GreenhouseUi()->getList($eZone['farm'], $eZone['cGreenhouse'], 'btn-transparent', 'bg-secondary');

		$h .= '</div>';

		return $h;

	}

	public function getPlan(\farm\Farm $eFarm, \Collection $cZone, Zone $eZoneSelected, int $season) {

		[$startTs, $stopTs] = new \series\PlaceUi()->getBounds($eFarm, $season);

		$h = '<div id="zone-container">';

			$h .= '<style>';
				$h .= ':root {';
					$h .= '--zone-content-months: '.$eFarm['calendarMonths'].';';
				$h .= '}';
			$h .= '</style>';
			$h .= '<div id="zone-content" data-start="'.$startTs.'" data-stop="'.$stopTs.'">';

				$h .= '<div id="zone-header" class="bed-item-grid bed-item-grid-plan bed-item-grid-header">';
					$h .= $this->getPlanHeader($eFarm, $cZone, $eZoneSelected, $season);
				$h .= '</div>';

				foreach($cZone as $eZone) {

					$h .= '<div class="zone-wrapper '.(($eZoneSelected->empty() or $eZone->is($eZoneSelected)) ? '' : 'hide').'" data-zone="'.$eZone['id'].'" data-soil-color="'.$eFarm->getView('viewSoilColor').'">';
						$h .= $this->getPlanZone($eFarm, $eZone, $season);
					$h .= '</div>';

				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getPrinting(\farm\Farm $eFarm, \Collection $cZone, int $season) {

		$h = '<style>';
			$h .= '@page {	size: A4; margin: 0.75cm; }';
			$h .= 'html { font-size: 8px !important; }';
		$h .= '</style>';

		$h .= '<div id="zone-container" class="zone-printing">';

			$h .= '<style>';
				$h .= ':root {';
					$h .= '--zone-content-months: '.$eFarm['calendarMonths'].';';
				$h .= '}';
			$h .= '</style>';
			$h .= '<div id="zone-content">';

				foreach($cZone as $eZone) {

					$h .= '<div class="zone-wrapper" data-soil-color="'.$eFarm->getView('viewSoilColor').'">';

						$h .= '<div class="zone-title">';
							$h .= '<span class="color-muted">'.s("Parcelle").'</span>';
							$h .= '<h2>';
								$h .= encode($eZone['name']);
							$h .= '</h2>';
							$h .= '<span class="color-muted">'.$eZone->getArea().'</span>';
						$h .= '</div>';

						$h .= '<table class="zone-table">';

							$h .= '<thead>';
								$h .= '<tr>';
									$h .= '<th>';
										$h .= '<div class="zone-header-season">';
											$h .= new \series\CultivationUi()->getListSeason($eFarm, $season);
										$h .= '</div>';
									$h .= '</th>';
								$h .= '</tr>';
							$h .= '</thead>';

							$h .= '<tbody>';
								$h .= '<tr>';

									$h .= '<td>';

										$h .= '<div class="zone-plots">';
											$h .= new PlotUi()->getPlots($eFarm, $eZone['cPlot'], $eZone, $season, new \series\Series(), TRUE);
										$h .= '</div>';

									$h .= '</td>';
								$h .= '</tr>';
							$h .= '</tbody>';
						$h .= '</table>';

					$h .= '</div>';
				}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getRotation(\farm\Farm $eFarm, \Collection $cZone, int $season, \Search $search = new \Search()) {

		$eZoneSelected = $cZone->first();

		$h = '<div class="tabs-h" id="zone-container" onrender="'.encode('Lime.Tab.restore(this, "map-soil")').'">';

			$h .= '<div class="tabs-item util-print-hide">';

				foreach($cZone as $eZone) {

					$h .= '<a class="tab-item '.($eZone['id'] === $eZoneSelected['id'] ? 'selected' : '').'" data-tab="'.$eZone['id'].'" onclick="Lime.Tab.select(this)">';

						$h .= encode($eZone['name']);

						if($search->notEmpty(['cFamily'])) {

							$beds = $search->get('seen') === 0 ?
								$eZone['cPlot']->reduce(fn($ePlot, $v) => $ePlot['cBed']->find(fn($eBed) => ($eBed['plotFill'] === FALSE and $eBed['zoneFill'] === FALSE))->count() + $v, 0) :
								$eZone['cPlot']->reduce(fn($ePlot, $v) => $ePlot['cBed']->count() + $v, 0);

							if($beds > 0) {
								$h .= '<span class="tab-item-count">'.$beds.'</span>';
							}

						}

					$h .= '</a>';

				}

			$h .= '</div>';

			$h .= '<div class="bed-item-wrapper">';

				foreach($cZone as $eZone) {

					$h .= '<div class="tab-panel util-print-block '.($eZone['id'] === $eZoneSelected['id'] ? 'selected' : '').'" data-tab="'.$eZone['id'].'">';
						$h .= '<div class="util-overflow-sm stick-sm">';

							$eZone->expects(['cGreenhouse', 'cPlot']);

							$h .= '<div class="zone-title">';

								$h .= '<div class="util-action">';
									$h .= '<h2>';
										$h .= s("Parcelle {value}", encode($eZone['name']));
										$h .= '<span class="zone-title-area">'.$eZone->getArea().'</span>';
									$h .= '</h2>';
								$h .= '</div>';

							$h .= '</div>';

							$h .= '<div class="bed-item-grid bed-item-grid-rotation bed-item-grid-rotation-'.$eFarm['rotationYears'].' bed-item-grid-header">';
								$h .= '<div></div>';
								$h .= $this->displayHeaderByRotation($season, $eFarm['rotationYears']);
							$h .= '</div>';

							$h .= new PlotUi()->getRotations($eFarm, $eZone['cPlot'], $season);

						$h .= '</div>';
					$h .= '</div>';

				}


			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getPlanHeader(\farm\Farm $eFarm, \Collection $cZone, Zone $eZoneSelected, int $season): string {

		$h = '<div class="zone-corner util-print-invisible">';

			if($cZone->count() >= 2) {

				$h .= '<a data-dropdown="bottom-start" class="zone-button btn btn-secondary dropdown-toggle" data-dropdown-hover="true">';
					$h .= '<span class="zone-dropdown" data-placeholder="'.s("Mes parcelles").'">';
						$h .= $eZoneSelected->notEmpty() ? encode($cZone->findById($eZoneSelected)['name']) : s("Mes parcelles");
					$h .= '</span>';
				$h .= '</a>';
				$h .= '<div class="dropdown-list" data-wrapper="#zone-container">';

					$h .= '<a class="dropdown-item '.($eZoneSelected->empty() ? 'selected' : '').'" data-tab="" onclick="Zone.select(this)">';
						$h .= s("Toutes mes parcelles");
					$h .= '</a>';

					if($this->eUpdate->notEmpty()) {
						$zones = array_count_values($this->eUpdate['cPlace']->getColumnCollection('zone')->getIds());
						asort($zones);
					}

					foreach($cZone as $eZone) {

						$beds = $zones[$eZone['id']] ?? 0;

						$h .= '<a class="dropdown-item '.($eZoneSelected->is($eZone) ? 'selected' : '').'" data-zone="'.$eZone['id'].'" onclick="Zone.select(this)" '.attr('data-placeholder', encode($eZone['name'])).'>';
							$h .= s("Parcelle {value}", encode($eZone['name']));
							if($this->eUpdate->notEmpty()) {
								$h .= '<span class="util-badge bg-primary zone-count" id="zone-count-'.$eZone['id'].'">';
									if($beds > 0) {
										$h .= $beds;
									}
								$h .= '</span>';
							}
						$h .= '</a>';

					}

				$h .= '</div>';

			}

		$h .= '</div>';

		$h .= '<div class="zone-header-season">';
			$h .= new \series\CultivationUi()->getListSeason($eFarm, $season, hasWeeks: TRUE);
		$h .= '</div>';

		return $h;

	}

	public function displayHeaderByRotation(int $season, int $number): string {

		$h = '';

		for($i = $season; $i > $season - $number; $i--) {
			$h .= '<div class="util-grid-header '.($i === $season ? 'ml-1' : '').'">'.$i.'</div>';
		}

		return $h;

	}

	public function getPlanZone(\farm\Farm $eFarm, Zone $eZone, int $season): string {

		$eZone->expects(['cGreenhouse', 'cPlot']);

		$cPlot = $eZone['cPlot'];

		$h = '<div class="zone-title zone-sticky-left" id="zone-title-'.$eZone['id'].'" data-ref="zone" data-name="'.encode($eZone['name']).'">';

			$h .= '<div class="util-action">';
				$h .= '<h2>';

					if($this->eUpdate->notEmpty()) {

						$eBed = $eZone['cPlot']->first()['cBed']->first();
						$ePlace = $this->eUpdate['cPlace'][$eBed['id']] ?? new \series\Place();

						$h .= '<label class="bed-item-select bed-write">';
							$h .= new \util\FormUi()->inputCheckbox('beds[]', $eBed['id'], ['checked' => $ePlace->notEmpty(), 'class' => 'zone-title-fill']);
						$h .= '</label>';

					}
					$h .= s("Parcelle {value}", encode($eZone['name']));
					$h .= '<span class="zone-title-area">'.$eZone->getArea().'</span>';
				$h .= '</h2>';
				if(
					$this->eUpdate->notEmpty() and
					$this->eUpdate['use'] === \series\Series::BED
				) {
					$h .= '<label class="bed-item-all bed-write">';
						$h .= '<input type="checkbox" onclick="Place.toggleSelection(this)"/>';
						$h .= '<span class="show-checked">'.s("Tout décocher").'</span>';
						$h .= '<span class="show-not-checked">'.s("Tout cocher").'</span>';
					$h .= '</label>';
				}
			$h .= '</div>';
			$h .= '<div>';
			$h .= '</div>';

		$h .= '</div>';
		$h .= '<div class="zone-plots">';
			$h .= new PlotUi()->getPlots($eFarm, $cPlot, $eZone, $season, $this->eUpdate, FALSE);
		$h .= '</div>';

		return $h;

	}

	protected function getZoneUse(Zone $eZone): string {

		$h = $eZone->getArea();

		$interval = SeasonUi::getInterval($eZone);

		if($interval) {
			$h .= ' | '.$interval;
		}

		if($eZone['plots'] > 0) {
			$h .= ' | '.p("{value} jardin", "{value} jardins", $eZone['plots']);
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
			$h .= $form->hidden('season', \main\MainSetting::$onlineSeason);

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
			id: 'panel-zone-create',
			title: s("Ajouter une parcelle"),
			body: $h
		);

	}

	public function update(Zone $eZone, \Collection $cZone): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/map/zone:doUpdate', ['id' => 'zone-update']);

			$h .= $form->hidden('id', $eZone['id']);
			$h .= $form->hidden('season', \main\MainSetting::$onlineSeason);

			$h .= $form->group(
				s("Ferme"),
				\farm\FarmUi::link($eZone['farm'], TRUE)
			);

			$h .= $form->dynamicGroup($eZone, 'name');

			$h .= $form->group(
				s("Exploité"),
				new SeasonUi()->getField($form, $eZone),
				['wrapper' => 'seasonFirst seasonLast']
			);

			$label = '<p>'.s("Dessiner sur la carte").'</p>';
			$helper = '<p class="util-helper">'.s("Pour modifier un point existant, cliquez dessus et vous pourrez ensuite le déplacer.").'</p>';

			$h .= $this->drawMap($form, $eZone, $label, $helper, $cZone);

			$h .= $form->group(
				content: $form->submit(s("Enregistrer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-zone-update',
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
							'label' => '> '.s("Jardin {value}", $ePlotSelect['name']),
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
			'farm' => ['cultivationLngLat']
		]);

		$canCartography = MapUi::canCartography($eZone['farm']);

		$map = '';

		if($canCartography === FALSE) {

			$map .= '<div class="util-block-important">';
				$map .= '<p>'.s("Si vous souhaitez dessiner plus facilement votre parcelle sur la carte, vous pouvez renseigner le lieu précis de production de la ferme. Vous pouvez aussi sauter cette étape et saisir directement la surface de cette parcelle.").'</p>';
				$map .= '<a href="/farm/farm:updatePlace?id='.$eZone['farm']['id'].'" class="btn btn-transparent">'.s("Renseigner le lieu de production").'</a>';
			$map .= '</div>';

			if($eZone['farm']['legalCountry']->empty()) {
				return $map;
			}

		}

		$eZone->add([
			'id' => NULL,
			'area' => NULL,
			'coordinates' => NULL
		]);


		$container = 'zone-map-write';

		if($canCartography) {

			$map .= '<div class="form-control-block" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0">';
				$map .= s("Dessiner cette parcelle sur la carte n'est pas obligatoire, vous pouvez sauter cette étape et saisir directement la surface de la parcelle.");
			$map .= '</div>';

		}

		$map .= new MapboxUi()->getDrawingPolygon($container, $form, $eZone);

		if($eZone['farm']['cultivationLngLat']) {
			$center = $eZone['farm']['cultivationLngLat'];
			$zoom = 14;
		} else {

			\user\Country::model()
				->select('center')
				->get($eZone['farm']['legalCountry']);

			$center = $eZone['farm']['legalCountry']['center'];
			$zoom = 5;

		}

		$map .= '<script>';
			$map .= 'document.ready(() => setTimeout(() => {
				new Cartography("'.$container.'", '.$eZone['farm']['seasonLast'].', false, true, {
						zoom: '.$zoom.',
						scrollZoom: true,
						center: ['.$center[0].', '.$center[1].']
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

					return new SeasonUi()->getDescriberField($form, $e, $e['farm'], NULL, NULL, $property, $placeholder);
				};
				break;

		}

		return $d;

	}

}
?>
