<?php
namespace map;

class PlotUi {

	public function __construct() {

		\Asset::css('map', 'plot.css');
		\Asset::js('map', 'plot.js');

	}

	public function getPlots(\farm\Farm $eFarm, \Collection $cPlot, Zone $eZone, int $season): string {

		$eZone->expects(['id', 'area']);

		if($cPlot->empty()) {
			return '';
		}

		$h = '';

		foreach($cPlot as $ePlot) {

			if($ePlot['zoneFill']) {

				$beds = (new BedUi())->displayFromPlot($eFarm, $ePlot, $season);

				if($beds) {
					$h .= $beds;
				}

			} else {

				$h .= '<div data-ref="plot" id="plot-item-'.$ePlot['id'].'" class="plot-item" data-name="'.encode(\Asset::icon('chevron-right').' '.encode($ePlot['name'])).'">';

					$h .= '<h4 class="plot-item-title">';
						$h .= s("Bloc {value}", encode($ePlot['name']));
					$h .= '</h4>';
					$h .= '<div>';
						$h .= $this->getPlotUse($ePlot);
					$h .= '</div>';

				$h .= '</div>';

				$h .= (new BedUi())->displayFromPlot($eFarm, $ePlot, $season);

			}

		}

		return $h;

	}

	public function getPlotsForZone(\Collection $cPlot, Zone $eZone, int $season, \Collection $cGreenhouse, Plot $ePlotSelected = new Plot()): string {

		$eZone->expects(['id', 'area', 'farm']);

		$h = '';

		foreach($cPlot as $ePlot) {

			if(
				$ePlotSelected->notEmpty() and
				$ePlotSelected['id'] !== $ePlot['id']
			) {
				continue;
			}

			$h .= '<div class="plot-item-wrapper">';

				if($ePlot['zoneFill'] === FALSE) {

					$h .= '<div data-ref="plot" id="plot-item-'.$ePlot['id'].'" class="plot-item" data-name="'.encode(\Asset::icon('chevron-right').' '.encode($ePlot['name'])).'">';

						$h .= '<div class="util-action">';
							$h .= '<div>';
								$h .= '<h4 class="plot-item-title">';
									$h .= s("Bloc {value}", encode($ePlot['name']));
									if($ePlot['coordinates'] === NULL) {
										$h .= '<span class="plot-item-no-cartography">'.s("Non cartographié").'</span>';
									}
								$h .= '</h4>';
								$h .= $this->getPlotUse($ePlot);
							$h .= '</div>';

							$h .= '<div>';

								if($eZone['farm']->canManage()) {

									$h .= '<a href="/map/bed:create?plot='.$ePlot['id'].'&season='.$season.'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter des planches").'</a>';

									$h .= ' <a data-dropdown="bottom-end" class="dropdown-toggle btn btn-primary">'.\Asset::icon('gear-fill').'</a>';
									$h .= '<div class="dropdown-list bg-primary">';
										$h .= '<div class="dropdown-title">'.encode($ePlot['name']).'</div>';
										if($ePlot['mode'] === Plot::OUTDOOR) {
											$h .= '<a href="/map/greenhouse:create?farm='.$eZone['farm']['id'].'&plot='.$ePlot['id'].'" class="dropdown-item">'.s("Couvrir le bloc avec un abri").'</a>';
											$h .= '<div class="dropdown-divider"></div>';
										}
										$h .= '<a href="/map/plot:update?id='.$ePlot['id'].'&season='.$season.'" class="dropdown-item">'.s("Modifier le bloc").'</a>';
										$h .= '<a data-ajax="/map/plot:doDelete" post-id="'.$ePlot['id'].'" post-season="'.$season.'" post-view="'.\Setting::get('main\viewMap').'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de ce bloc ?").'">'.s("Supprimer le bloc").'</a>';
									$h .= '</div>';

								}

							$h .= '</div>';

						$h .= '</div>';
						$h .= (new GreenhouseUi())->getList($eZone['farm'], $ePlot['cGreenhouse'], 'btn-primary', 'bg-primary');

					$h .= '</div>';

				}

				$h .= '<div class="plot-item-beds" id="plot-item-beds-'.$ePlot['id'].'">';
					if($ePlot['beds'] > 0) {
						$h .= (new \map\BedUi())->configure($season, $eZone, $ePlot, $ePlot['cBed'], $cGreenhouse);
					}
				$h .= '</div>';

			$h .= '</div>';

		}

		if(
			$ePlotSelected->empty() and
			$eZone['farm']->canManage()
		) {

			$h .= '<div class="plot-item-wrapper">';

				$h .= '<div class="plot-item text-center">';
					if($cPlot->count() > 1) {
						$label = s("Ajouter un autre bloc à cette parcelle");
					} else {
						$label = s("Ajouter un bloc à cette parcelle");
					}
					$h .= '<a href="/map/plot:create?zone='.$eZone['id'].'&season='.$season.'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.$label.'</a>';
				$h .= '</div>';

			$h .= '</div>';

		}

		return $h;

	}

	public function getPlotUse(Plot $ePlot): string {

		$h = '';

		if($ePlot['area'] > 1000) {
			$h .= s("{value} ha", sprintf('%.02f', $ePlot['area'] / 10000));
		} else {
			$h .= s("{value} m²", $ePlot['area']);
		}

		$interval = SeasonUi::getInterval($ePlot);

		if($interval) {
			$h .= ' | '.$interval;
		}

		return $h;

	}

	public function create(Zone $eZone): \Panel {

		$form = new \util\FormUi();

		$ePlot = new Plot([
			'zone' => $eZone,
			'farm' => $eZone['farm'],
			'mode' => Plot::OUTDOOR
		]);

		$h = '';

		$h .= $form->openAjax('/map/plot:doCreate', ['id' => 'plot-create', 'autocomplete' => 'off']);

			$h .= $form->hidden('zone', $eZone['id']);
			$h .= $form->hidden('season', \Setting::get('main\onlineSeason'));
			$h .= $form->hidden('view', \Setting::get('main\viewMap'));

			$h .= $form->group(
				s("Parcelle"),
				'<u>'.encode($eZone['name']).'</u>'
			);

			$h .= $this->write($form, $ePlot);
			$h .= $this->writeGreenhouse($form, $ePlot, new Greenhouse());
			$h .= $form->dynamicGroup($ePlot, 'seasonFirst');

			$label = '<p>'.s("Dessiner sur la carte").'</p>';

			$h .= $this->drawMap($form, $eZone, $ePlot, $label);

			$h .= $form->group(
				content: $form->submit(s("Créer le bloc"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-plot-create',
			title: s("Ajouter un bloc"),
			body: $h
		);

	}

	public function update(Plot $ePlot): \Panel {

		$form = new \util\FormUi();

		$eZone = $ePlot['zone'];
		$eZone['farm'] = $ePlot['farm'];

		$h = '';

		$h .= $form->openAjax('/map/plot:doUpdate', ['id' => 'plot-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $ePlot['id']);
			$h .= $form->hidden('season', \Setting::get('main\onlineSeason'));
			$h .= $form->hidden('view', \Setting::get('main\viewMap'));

			$h .= $form->group(
				s("Parcelle"),
				'<u>'.encode($eZone['name']).'</u>'
			);

			$h .= $this->write($form, $ePlot);

			$h .= $form->group(
				s("Exploité"),
				(new SeasonUi)->getField($form, $ePlot),
				['wrapper' => 'seasonFirst seasonLast']
			);

			$label = '<p>'.s("Dessiner sur la carte").'</p>';
			$h .= $this->drawMap($form, $eZone, $ePlot, $label);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-plot-update',
			title: s("Modifier un bloc"),
			body: $h
		);

	}

	public function write(\util\FormUi $form, Plot $ePlot): string {

		return $form->dynamicGroup($ePlot, 'name');

	}

	public function writeGreenhouse(\util\FormUi $form, Plot $ePlot, Greenhouse $eGreenhouse): string {

		$ePlot->expects(['mode']);

		$h = $form->dynamicGroup($ePlot, 'mode');

		$h .= '<div id="plot-mode-greenhouse" class="util-block-gradient '.($ePlot['mode'] === Plot::OUTDOOR ? 'hide' : '').'">';
			$h .= $form->group(content: '<p class="util-info">'.s("L'abri couvrira automatiquement toutes les planches que vous ajouterez ultérieurement à ce bloc. Indiquez ci-dessous ses dimensions.").'</p>');
			$h .= $form->dynamicGroups($eGreenhouse, ['length', 'width']);
		$h .= '</div>';

		return $h;

	}

	public function drawMap(\util\FormUi $form, Zone $eZone, Plot $ePlot, string $label): string {

		if($eZone['coordinates'] === NULL) {

			$map = '<div class="form-control-block">';
				$map .= '<p>'.s("Pour dessiner ce bloc sur la carte, vous devez d'abord cartographier la parcelle dans laquelle il se trouve. Vous pouvez aussi sauter cette étape et saisir directement la surface de ce bloc.").'</p>';
				$map .= '<a href="/map/zone:update?id='.$eZone['id'].'&season='.\Setting::get('main\onlineSeason').'" class="btn btn-outline-primary">'.s("Cartographier la parcelle").'</a>';
			$map .= '</div>';

		} else {

			$ePlot->add([
				'id' => NULL,
				'area' => NULL,
				'coordinates' => NULL
			]);

			\map\MapboxUi::load();

			$container = 'plot-map-write';

			$map = '<div class="form-control-block" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0">';
				$map .= s("Dessiner ce bloc sur la carte n'est pas obligatoire, vous pouvez sauter cette étape et saisir directement la surface du bloc.");
			$map .= '</div>';

			$map .= (new MapboxUi())->getDrawingPolygon($container, $form, $ePlot, TRUE);

			$map .= '<script>';
				$map .= 'document.ready(() => setTimeout(() => {
					new Cartography("'.$container.'", '.$eZone['farm']['seasonLast'].', false, true)';
						$map .= '.addZone('.$eZone['id'].', "'.addcslashes($eZone['name'], '"').'", '.json_encode($eZone['coordinates']).')';
						foreach($eZone['cPlot'] as $ePlotDisplay) {
							if($ePlotDisplay['zoneFill'] === FALSE) {
								$display = ($ePlot['id'] === NULL or $ePlot['id'] !== $ePlotDisplay['id']);
								$map .= '.addPlot('.$ePlotDisplay['id'].', "'.addcslashes($ePlotDisplay['name'], '"').'", '.$eZone['id'].', '.json_encode($ePlotDisplay['coordinates']).', '.($display ? 'true' : 'false').')';
								$map .= '.eventPlot('.$ePlotDisplay['id'].', '.$eZone['id'].')';
							}
						}
						$map .= '.eventZone('.$eZone['id'].')';
						if($ePlot['id'] === NULL) {
							$map .= '.fitZoneBounds('.$eZone['id'].', {duration: 0})';
						} else {
							$map .= '.fitPlotBounds('.$ePlot['id'].', {duration: 0})';
						}
						$map .= '.drawShape()';
						if($ePlot['coordinates'] !== NULL) {
							$map .= '.drawPolygon('.json_encode($ePlot['coordinates']).')';
						}
						$map .= ';';
				$map .= '}, 100));';
			$map .= '</script>';

		}

		$h = $form->group($label, $map, ['wrapper' => 'coordinates']);
		$h .= $form->dynamicGroup($ePlot, 'area');

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Plot::model()->describer($property, [
			'name' => s("Nom du bloc"),
			'farm' => s("Ferme"),
			'mode' => s("Ce bloc est-il couvert par un abri ?"),
			'area' => s("Surface du bloc"),
			'seasonFirst' => s("Bloc exploité depuis"),
			'seasonLast' => s("Bloc exploité jusqu'à"),
			'createdAt' => s("Créée le"),
		]);

		switch($property) {

			case 'name' :
				$d->attributes = [
					'placeholder' => s("Ex. : Jardin n°1")
				];
				break;

			case 'area' :
				$d->append = s("m²");
				break;

			case 'mode' :
				$d->values = [
					Plot::GREENHOUSE => s("Oui"),
					Plot::OUTDOOR => s("Non"),
				];
				$d->attributes = [
					'data-action' => 'plot-mode-change',
					'columns' => 2,
					'mandatory' => TRUE
				];
				break;

			case 'seasonFirst' :
			case 'seasonLast' :
				$d->field = function(\util\FormUi $form, \Element $e, $property) {

					$e->expects(['zone', 'farm']);

					$placeholder = [
						'seasonFirst' => s("la création de la parcelle"),
						'seasonLast' => s("la disparition de la parcelle")
					][$property];

					return (new SeasonUi())->getDescriberField($form, $e, $e['farm'], $e['zone'], NULL, $property, $placeholder);
				};
				break;

		}

		return $d;

	}

}
?>
