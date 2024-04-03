<?php
namespace plant;

class AnalyzeUi {

	public function __construct() {

		\Asset::css('plant', 'analyze.css');

		\Asset::css('analyze', 'chart.css');
		\Asset::js('analyze', 'chart.js');

	}

	public function getArea(\farm\Farm $eFarm, array $seasons, int $season, array $area): string {

		$h = $this->getAreaSummary($eFarm, $seasons, $season, $area);
		$h .= $this->getAreaContent($eFarm, $seasons, $area);

		return $h;

	}

	public function getAreaSummary(\farm\Farm $eFarm, array $seasons, int $currentSeason, array $area): string {

		$h = '<br/>';

		$h .= '<h3>'.s("Surfaces développées").'</h3>';

		$h .= '<div class="util-overflow-md mb-2">';

			$h .= '<table class="util-block tr-even">';

				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<td></td>';
						foreach($seasons as $season) {
							$h .= '<th class="text-end">'.s("Saison {value}", $season).'</th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					foreach(['Annual', 'Perennial'] as $type) {

						$h .= '<tr>';
							$h .= '<td>';
								$h .= match($type) {
									'Annual' => s("Espèces annuelles"),
									'Perennial' => s("Espèces pérennes"),
								};
							$h .= '</td>';

							foreach($seasons as $season) {
								$h .= '<td class="text-end '.($season === $currentSeason ? ' selected' : '').'">'.s("{value} m²", \util\TextUi::number($area[$season]['bedsAreaDeveloped'.$type] + $area[$season]['blockAreaDeveloped'.$type], precision: 0)).'</td>';
							}

						$h .= '</tr>';

					}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}


	public function getAreaContent(\farm\Farm $eFarm, array $seasons, array $area): string {

		$h = '<h3>'.s("Cultures sur planches permanentes").'</h3>';

		$h .= '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="tr-even util-block analyze-plant-area">';
				$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th></th>';
					foreach($seasons as $season) {
						$h .= '<th class="analyze-plant-area-season">'.$season.'</th>';
						$h .= '<th></th>';
					}
				$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';
					$h .= '<tr>';
						$h .= '<td>'.s("Nombre de planches déclarées").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.$area[$season]['beds'].'</td>';
							$h .= '<td></td>';
						}
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>'.s("Surface des planches déclarées").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.s("{value} m²", \util\TextUi::number($area[$season]['bedsArea'], 0)).'</td>';
							$h .= '<td></td>';
						}
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td class="text-sm">&nbsp;&nbsp;'.\Asset::icon('arrow-return-right').'&nbsp;&nbsp;'.s("Surface en plein champ").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.s("{value} m²", \util\TextUi::number($area[$season]['bedsAreaField'], 0)).'</td>';
							$h .= '<td class="util-annotation">';
								if($area[$season]['bedsArea'] > 0) {
									$h .= s("{value} %", round($area[$season]['bedsAreaField'] / $area[$season]['bedsArea'] * 100));
								}
							$h .= '</td>';
						}
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td class="text-sm">&nbsp;&nbsp;'.\Asset::icon('arrow-return-right').'&nbsp;&nbsp;'.s("Surface sous abri").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.s("{value} m²", \util\TextUi::number($area[$season]['bedsAreaGreenhouse'], 0)).'</td>';
							$h .= '<td class="util-annotation">';
								if($area[$season]['bedsArea'] > 0) {
									$h .= s("{value} %", round($area[$season]['bedsAreaGreenhouse'] / $area[$season]['bedsArea'] * 100));
								}
							$h .= '</td>';
						}
					$h .= '</tr>';

					if($area[$season]['beds'] > 0) {

						$h .= '<tr>';
							$h .= '<td class="text-sm">&nbsp;&nbsp;'.\Asset::icon('arrow-return-right').'&nbsp;&nbsp;'.s("Surface cultivée avec des espèces annuelles").'</td>';
							foreach($seasons as $season) {
								$h .= '<td class="text-end">'.s("{value} m²", \util\TextUi::number($area[$season]['bedsAreaAnnual'], 0)).'</td>';
								$h .= '<td class="util-annotation">'.s("{value} %", round($area[$season]['bedsAreaAnnual'] / $area[$season]['bedsArea'] * 100)).'</td>';
							}
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td class="text-sm">&nbsp;&nbsp;'.\Asset::icon('arrow-return-right').'&nbsp;&nbsp;'.s("Surface cultivée avec des espèces pérennes").'</td>';
							foreach($seasons as $season) {
								$h .= '<td class="text-end">'.s("{value} m²", \util\TextUi::number($area[$season]['bedsAreaPerennial'], 0)).'</td>';
								$h .= '<td class="util-annotation">'.s("{value} %", round($area[$season]['bedsAreaPerennial'] / $area[$season]['bedsArea'] * 100)).'</td>';
							}
						$h .= '</tr>';
						$h .= '<tr>';
							$h .= '<td>'.s("Nombre d'espèces cultivées").'</td>';
							foreach($seasons as $season) {
								$h .= '<td class="text-end">'.$area[$season]['bedsPlants'].'</td>';
								$h .= '<td></td>';
							}
						$h .= '</tr>';

					}

				$h .= '</tbody>';
			$h .= '</table>';
			$h .= '<br/>';
			$h .= '<h4>'.s("Pour les espèces annuelles").'</h4>';
			$h .= '<table class="tr-even util-block analyze-plant-area">';
				$h .= '<thead class="analyze-plant-area-developed">';
					$h .= '<tr>';
						$h .= '<th></th>';
						foreach($seasons as $season) {
							$h .= '<th class="analyze-plant-area-season">'.$season.'</th>';
							$h .= '<th></th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';
					$h .= '<tr>';
						$h .= '<td title="'.s("Surface réellement couverte par des séries au cours de la saison").'">'.s("Surface développée").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.s("{value} m²", \util\TextUi::number($area[$season]['bedsAreaDevelopedAnnual'])).'</td>';
							$h .= '<td></td>';
						}
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>'.s("Taux d'utilisation annuel des planches cultivées").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.($area[$season]['bedsAreaRateAnnual'] ? \util\TextUi::number($area[$season]['bedsAreaRateAnnual'], 1) : '-').'</td>';
							$h .= '<td></td>';
						}
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td class="text-sm">&nbsp;&nbsp;'.\Asset::icon('arrow-return-right').'&nbsp;&nbsp;'.s("Taux d'utilisation en plein champ").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.($area[$season]['bedsAreaFieldRateAnnual'] ? \util\TextUi::number($area[$season]['bedsAreaFieldRateAnnual'], 1) : '-').'</td>';
							$h .= '<td></td>';
						}
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td class="text-sm">&nbsp;&nbsp;'.\Asset::icon('arrow-return-right').'&nbsp;&nbsp;'.s("Taux d'utilisation sous abri").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.($area[$season]['bedsAreaGreenhouseRateAnnual'] ? \util\TextUi::number($area[$season]['bedsAreaGreenhouseRateAnnual'], 1) : '-').'</td>';
							$h .= '<td></td>';
						}
					$h .= '</tr>';
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		$h .='<br/>';
		$h .= '<h3>'.s("Cultures sur surfaces libres et planches temporaires").'</h3>';

		$h .= '<div class="util-overflow-md stick-xs">';

			$h .= '<table class="tr-even util-block analyze-plant-area">';
				$h .= '<thead>';
				$h .= '<tr>';
					$h .= '<th></th>';
					foreach($seasons as $season) {
						$h .= '<th class="analyze-plant-area-season">'.$season.'</th>';
						$h .= '<th></th>';
					}
				$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';
					$h .= '<tr>';
						$h .= '<td>'.s("Surface développée avec des espèces annuelles").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.s("{value} m²", \util\TextUi::number($area[$season]['blockAreaDevelopedAnnual'], 0)).'</td>';
							$h .= '<td></td>';
						}
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>'.s("Surface développée avec des espèces pérennes").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.s("{value} m²", \util\TextUi::number($area[$season]['blockAreaDevelopedPerennial'], 0)).'</td>';
							$h .= '<td></td>';
						}
					$h .= '</tr>';
					$h .= '<tr>';
						$h .= '<td>'.s("Nombre d'espèces cultivées").'</td>';
						foreach($seasons as $season) {
							$h .= '<td class="text-end">'.$area[$season]['blockPlants'].'</td>';
							$h .= '<td></td>';
						}
					$h .= '</tr>';
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getPlant(int $season, \Collection $ccCultivationPlant, \Search $search): string {

		$h = '';

		if($ccCultivationPlant->offsetExists($season) === FALSE) {
			$h = '<div class="util-info">';
				$h .= s("Aucune espèce n'a été cultivée dans une série cette année.");
			$h .= '</div>';
		} else {
			$h .= '<div>';
				$h .= $this->getSearch($search, 'analyze-plant-search');
			$h .= '</div>';
			$h .= '<div class="analyze-chart-table">';
				$h .= $this->getBestPlantsPie($ccCultivationPlant, $season);
				$h .= $this->getBestPlantsTable($ccCultivationPlant, $season, clone $search);
			$h .= '</div>';
		}


		return $h;

	}

	public function getFamily(\farm\Farm $eFarm, int $season, array $area, \Collection $ccCultivationFamily, \Search $search): string {

		$h = '';

		if($ccCultivationFamily->offsetExists($season) === FALSE) {
			$h = '<div class="util-info">';
				$h .= s("Aucune famille botanique n'a été cultivée dans une série cette année.");
			$h .= '</div>';
		} else {
			$h .= '<div>';
				$h .= $this->getSearch($search, 'analyze-family-search');
			$h .= '</div>';
			$h .= '<div class="analyze-chart-table">';
				$h .= $this->getBestFamiliesPie($ccCultivationFamily, $season);
				$h .= $this->getBestFamiliesTable($eFarm, $ccCultivationFamily, $area, $season, clone $search);
			$h .= '</div>';
		}


		return $h;

	}

	public function getRotation(\farm\Farm $eFarm, array $seasons, array $area, \Collection $cFamily, \Collection $cBed, array $rotations): string {

		$h = '';

		if($cFamily->empty()) {
			$h .= '<div class="util-info">';
				$h .= s("Aucune famille botanique n'a été cultivée sur planche permanente cette année.");
			$h .= '</div>';
		} else {

			$h .= '<div class="util-info">';
				$h .= s("Les rotations ne peuvent être affichées que pour les planches permanentes.");
			$h .= '</div>';

			$h .= '<div class="util-overflow-sm">';
				$h .= '<div class="tabs-v" id="plant-rotation" onrender="'.encode('Lime.Tab.restore(this, "family-'.$cFamily->first()['id'].'")').'">';

					$h .= '<div class="tabs-item">';
						foreach($cFamily as $eFamily) {
							$h .= '<a class="tab-item" data-tab="family-'.$eFamily['id'].'" onclick="Lime.Tab.select(this)">'.encode($eFamily['name']).'</a>';
						}
					$h .= '</div>';
					$h .= '<div class="tabs-panel">';

					foreach($cFamily as $eFamily) {

						$h .= '<div class="tab-panel" data-tab="family-'.$eFamily['id'].'">';
							$h .= $this->getRotationByFamily($eFarm, $seasons, $area, $eFamily, $cBed, $rotations);
						$h .= '</div>';

					}

					$h .= '</div>';
				$h .= '</div>';
			$h .= '</div>';

		}


		return $h;

	}

	protected function getRotationByFamily(\farm\Farm $eFarm, array $seasons, array $area, Family $eFamily, \Collection $cBed, array $rotations): string {

		$seasonsDisplayed = count($seasons);
		$currentSeason = last($seasons);

		$beds = $this->getRotationByBed($eFamily, $cBed, $rotations);

		$h = '<h2>'.encode($eFamily['name']).'</h2>';

		$h .= '<table class="tr-bordered analyze-rotation">';
			$h .= '<thead>';
				$h .= '<tr>';
					foreach($seasons as $season) {
						$h .= '<th class="text-center">'.$season.'</th>';
					}
					$h .= '<th class="text-end">'.s("Surface").'</th>';
					$h .= '<th></th>';
					$h .= '<th class="text-center">'.s("Planches").'</th>';
				$h .= '</tr>';
			$h .= '</thead>';
			$h .= '<tbody>';

				$h .= '<tr>';
					$h .= '<td class="analyze-rotation-seen" colspan="'.$seasonsDisplayed.'">';
						if(count($seasons) > 1) {
							$h .= '<span class="analyze-rotation-label">'.s("Pas vu sur la période").'</span>';
						}
						$h .= '<span class="color-danger">'.str_repeat(' '.\Asset::icon('x-circle'), count($seasons)).'</span>';
					$h .= '</td>';

					$h .= $this->getRotationDisplay(
						$eFarm, $currentSeason,
						$eFamily,
						$this->getRotationByNumber($cBed, $beds, 0),
						$area,
						number: 0
					);

				$h .= '</tr>';

				foreach(array_reverse($seasons) as $seasonLine) {

					$h .= '<tr>';

						foreach($seasons as $seasonColumn) {

							if($seasonColumn === $seasonLine) {
								$h .= '<td class="analyze-rotation-yes">'.\Asset::icon('check-circle').'</td>';
							} else if($seasonColumn > $seasonLine) {
								$h .= '<td class="analyze-rotation-no">'.\Asset::icon('x-circle').'</td>';
							} else {
								$h .= '<td class="analyze-rotation-unknown">?</td>';
							}

 						}

						$h .= $this->getRotationDisplay(
							$eFarm, $currentSeason,
							$eFamily,
							$this->getRotationBySeason($eFamily, $seasonLine, $rotations),
							$area,
							season: $seasonLine
						);

					$h .= '</tr>';

				}


				if(count($seasons) > 1) {

					for($i = 1; $i <= $seasonsDisplayed; $i++) {

						$h .= '<tr>';

							$h .= '<td class="analyze-rotation-seen" colspan="'.$seasonsDisplayed.'">';
								$h .= '<span class="analyze-rotation-label">'.p("Vu {value} année sur la période", "Vu {value} années sur la période", $i).'</span>';
								$h .= '<span class="color-success">'.str_repeat(' '.\Asset::icon('check-circle'), $i).'</span>';
							$h .= '</td>';

							$h .= $this->getRotationDisplay(
								$eFarm, $currentSeason,
								$eFamily,
								$this->getRotationByNumber($cBed, $beds, $i),
								$area,
								number: $i
							);

						$h .= '</tr>';

					}

				}

			$h .= '</tbody>';
		$h .= '</table>';

		return $h;

	}

	protected function getRotationDisplay(\farm\Farm $eFarm, int $currentSeason, Family $eFamily, array $stats, array $area, ?int $number  = NULL, ?int $season = NULL) {

		[
			'area' => $areaBeds,
			'cBed' => $cBed
		] = $stats;

		$h = '<td class="text-end">';
			if($areaBeds > 0) {
				$h .= s("{value} m²", \util\TextUi::number($areaBeds, 0));
			} else {
				$h .= '-';
			}
		$h .= '</td>';
		$h .= '<td class="util-annotation td-min-content">';
			if($areaBeds > 0) {
				$h .= s("{value} %", round($areaBeds / $area['bedsArea'] * 100));
			}
		$h .= '</td>';

		$h .= '<td class="text-center">';
			if($cBed->count() > 0) {
				$h .= '<a href="'.\farm\FarmUi::urlHistory($eFarm, $currentSeason).'?family='.$eFamily['id'].''.($number !== NULL ? '&seen='.$number : '').''.($season !== NULL ? '&seen='.$season : '').'&bed=1" class="btn btn-secondary">'.$cBed->count().'</a>';
			}
		$h .= '</td>';

		return $h;

	}

	public function getRotationByBed(Family $eFamily, \Collection $cBed, array $rotations) {

		$seasons = $rotations[$eFamily['id']]['season'] ?? [];

		$beds = [];
		foreach($cBed as $eBed) {
			$beds[$eBed['id']] = 0;
		}

		foreach($seasons as $list) {

			foreach($list as $bed => ['bed' => $eBed, 'plants' => $cPlant]) {
				$beds[$bed]++;
			}

		}

		return $beds;

	}

	public function getRotationByNumber(\Collection $cBed, array $beds, int $seen) {

		$area = 0;
		$cBedSelected = new \Collection();

		foreach($beds as $bed => $bedSeen) {

			if($seen === $bedSeen) {

				$eBed = $cBed[$bed];

				$area += $eBed['area'];
				$cBedSelected[] = $eBed;

			}

		}

		return [
			'area' => $area,
			'cBed' => $cBedSelected
		];

	}

	public function getRotationBySeason(Family $eFamily, int $season, array $rotations) {

		$list = $rotations[$eFamily['id']]['season'][$season] ?? [];

		$area = 0;
		$cBedSelected = new \Collection();

		foreach($list as $bed => ['bed' => $eBed, 'plants' => $cPlant]) {
			$area += $eBed['area'];
			$cBedSelected[] = $eBed;
		}

		return [
			'area' => $area,
			'cBed' => $cBedSelected
		];

	}

	public function getSearch(\Search $search, string $id): string {

		$h = '<div id="'.$id.'" class="util-block-search '.($search->empty() ? 'hide' : '').' mt-1">';

			$form = new \util\FormUi();
			$url = LIME_REQUEST_PATH;

			$h .= $form->openAjax($url, ['method' => 'get', 'id' => 'form-search']);
				$h .= '<div>';
					$h .= $form->select('cycle', [
							Plant::ANNUAL => s("Annuelles"),
							Plant::PERENNIAL => s("Pérennes"),
						], $search->get('cycle'), ['placeholder' => s("Annuelles et pérennes")]);
					$h .= $form->select('use', [
							\series\Series::BED => s("Cultures sur planches"),
							\series\Series::BLOCK => s("Cultures sur surfaces libres"),
						], $search->get('use'), ['placeholder' => s("Cultures sur planches ou sur surface libres")]);
					$h .= $form->submit(s("Chercher"), ['class' => 'btn btn-secondary']);
					$h .= '<a href="'.$url.'" class="btn btn-secondary">'.\Asset::icon('x-lg').'</a>';
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	protected function getBestPlantsTable(\Collection $ccCultivationPlant, int $season, \Search $search, ?int $limit = NULL): string {

		$search->validateSort(['plant', 'area'], 'area-');

		$ccCultivationPlant[$season]->sort($search->buildSort([
			'plant' => fn($direction) => [
				'plant' => ['name' => $direction]
			]
		]));

		$area = $ccCultivationPlant[$season]->sum('area');
		$areaBefore = $ccCultivationPlant->offsetExists($season - 1) ? $ccCultivationPlant[$season - 1]->sum('area') : 0;

		$h = '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="tr-even analyze-values">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th></th>';
						$h .= '<th colspan="4" class="text-center">'.$season.'</th>';
						$h .= '<th colspan="4" class="text-center color-muted">'.($season - 1).'</th>';
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th>'.$search->linkSort('plant', s("Espèce")).'</th>';
						$h .= '<th colspan="2" class="text-center">'.$search->linkSort('area', s("Surface"), SORT_DESC).'</th>';
						$h .= '<th colspan="2" class="text-center">'.s("Récolté").'</th>';
						$h .= '<th colspan="2" class="text-center color-muted">'.s("Surface").'</th>';
						$h .= '<th colspan="2" class="text-center color-muted">'.s("Récolté").'</th>';
					$h .= '</tr>';

				$h .= '</thead>';
				$h .= '<tbody>';

					$again = $limit;

					foreach($ccCultivationPlant[$season] as $eCultivationPlant) {

						if($again !== NULL and $again-- === 0) {
							break;
						}

						$eCultivationPlantBefore = $ccCultivationPlant[$season - 1][$eCultivationPlant['plant']['id']] ?? new \series\Cultivation();

						$h .= '<tr>';
							$h .= '<td>';
								$h .= PlantUi::getVignette($eCultivationPlant['plant'], '2rem').'&nbsp;&nbsp;';
								$h .= PlantUi::link($eCultivationPlant['plant']);
							$h .= '</td>';
							$h .= $this->getBestPlantsYear($eCultivationPlant, $area);
							$h .= $this->getBestPlantsYear($eCultivationPlantBefore, $areaBefore, 'color-muted');
						$h .= '</tr>';

					}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		if($limit !== NULL and $ccCultivationPlant->count() > $limit) {
			$h .= '<p class="util-info" style="margin-top: -1rem">';
				$h .= p("+ {value} autre espèce", "+ {value} autres espèces", $ccCultivationPlant->count() - $limit);
			$h .= '</p>';
		}

		return $h;

	}

	protected function getBestPlantsYear(\series\Cultivation $eCultivation, int $area, string $class = '') {

		if($eCultivation->empty()) {

			$h = '<td class="text-end '.$class.'">-</td>';
			$h .= '<td></td>';
			$h .= '<td class="text-end '.$class.'"></td>';
			$h .= '<td></td>';

			return $h;

		}

		$h = '<td class="text-end '.$class.'">';
			$h .= s("{value} m²", \util\TextUi::number($eCultivation['area']));
		$h .= '</td>';
		$h .= '<td class="util-annotation">';
			$h .= \util\TextUi::pc($eCultivation['area'] / $area * 100);
		$h .= '</td>';
		$h .= '<td class="text-end '.$class.'">';

			if($eCultivation['harvested'] !== NULL) {

				$position = 0;

				foreach($eCultivation['harvested'] as $unit => $value) {

						$h .= '<div style="'.(($position++ % 2) ? 'opacity: 0.66' : '').'">';
							$h .= \util\TextUi::number(round($value), 0);
						$h .= '</div>';

				}

			}

		$h .= '</td>';
		$h .= '<td style="padding-left: 0" class="'.$class.'">';

			if($eCultivation['harvested'] !== NULL) {

				$position = 0;

				foreach($eCultivation['harvested'] as $unit => $value) {

						$h .= '<div style="'.(($position++ % 2) ? 'opacity: 0.66' : '').'">';
							$h .= \main\UnitUi::getSingular($unit, short: TRUE);
						$h .= '</div>';

				}

			}

		$h .= '</td>';

		return $h;

	}

	public function getBestPlantsPie(\Collection $ccCultivationPlant, int $season): string {

		$cCultivationPlant = $ccCultivationPlant[$season];

		return (new \analyze\ChartUi())->buildPie(
			s("Répartition des espèces pour la saison {value}", $season),
			$cCultivationPlant,
			'area',
			fn($eCultivationPlant) => $eCultivationPlant['plant']['name']
		);

	}

	protected function getBestFamiliesTable(\farm\Farm $eFarm, \Collection $ccCultivationFamily, array $area, int $season, \Search $search, ?int $limit = NULL): string {

		$search->validateSort(['family', 'area'], 'area-');

		$ccCultivationFamily[$season]->sort($search->buildSort([
			'family' => fn($direction) => [
				'family' => ['name' => $direction]
			]
		]));

		$areaCurrent = $ccCultivationFamily[$season]->sum('area');
		$areaBefore = $ccCultivationFamily->offsetExists($season - 1) ? $ccCultivationFamily[$season - 1]->sum('area') : 0;

		$h = '<div class="util-overflow-xs stick-xs">';

			$h .= '<table class="tr-even analyze-values">';

				$h .= '<thead>';

					$h .= '<tr>';
						$h .= '<th rowspan="2">'.$search->linkSort('family', s("Espèce")).'</th>';
						$h .= '<th colspan="4" class="text-center">'.s("Surface").'</th>';
						if($search->get('use') === \series\Series::BED) {
							$h .= '<th colspan="4" class="text-center">'.s("Années de retour<br/><small>(sur planches permanentes)</small>").'</th>';
						}
					$h .= '</tr>';

					$h .= '<tr>';
						$h .= '<th class="text-end">'.$search->linkSort('area', $season, SORT_DESC).'</th>';
						$h .= '<th></th>';
						$h .= '<th class="text-end">'.($season - 1).'</th>';
						$h .= '<th></th>';
						if($search->get('use') === \series\Series::BED) {
							$h .= '<th class="text-end">'.$season.'</th>';
							$h .= '<th class="text-end">'.($season - 1).'</th>';
						}
					$h .= '</tr>';
				$h .= '</thead>';
				$h .= '<tbody>';

					$again = $limit;

					foreach($ccCultivationFamily[$season] as $eCultivationFamily) {

						if($again !== NULL and $again-- === 0) {
							break;
						}

						$familyId = $eCultivationFamily['family']->empty() ? NULL : $eCultivationFamily['family']['id'];
						$eCultivationFamilyBefore = $ccCultivationFamily[$season - 1][$familyId] ?? new \series\Cultivation();

						$h .= '<tr>';
							$h .= '<td>';
								if($eCultivationFamily['family']->empty()) {
									$h .= '<i>'.s("Non renseignée").'</i>';
								} else {
									$h .= FamilyUi::link($eCultivationFamily['family'], $eFarm);
								}
							$h .= '</td>';
							$h .= '<td class="text-end">';
								$h .= s("{value} m²", \util\TextUi::number($eCultivationFamily['area']));
							$h .= '</td>';
							$h .= '<td class="util-annotation">';
								$h .= \util\TextUi::pc($eCultivationFamily['area'] / $areaCurrent * 100);
							$h .= '</td>';

							if($eCultivationFamilyBefore->notEmpty()) {
								$h .= '<td class="text-end">';
									$h .= s("{value} m²", \util\TextUi::number($eCultivationFamilyBefore['area']));
								$h .= '</td>';
								$h .= '<td class="util-annotation">';
									$h .= \util\TextUi::pc($eCultivationFamilyBefore['area'] / $areaBefore * 100);
								$h .= '</td>';
							} else {
								$h .= '<td class="text-end">-</td>';
								$h .= '<td></td>';
							}

							if(
								$search->get('use') === \series\Series::BED and
								$search->get('cycle') !== \series\Series::PERENNIAL
							) {

								$bedsArea = ($search->get('cycle') === \series\Series::ANNUAL) ? 'bedsAreaAnnual' : 'bedsArea';

								$h .= '<td class="text-end">';
									if($eCultivationFamily['areaPermanent'] > 0) {
										$h .= \util\TextUi::number($area[$season][$bedsArea] / $eCultivationFamily['areaPermanent'], 1);
									} else {
										$h .= '-';
									}
								$h .= '</td>';

								$h .= '<td class="text-end">';
									if(
										$eCultivationFamilyBefore->notEmpty() and
										$eCultivationFamilyBefore['areaPermanent'] > 0
									) {
										$h .= \util\TextUi::number($area[$season - 1][$bedsArea] / $eCultivationFamilyBefore['areaPermanent'], 1);
									} else {
										$h .= '-';
									}
								$h .= '</td>';

							}

						$h .= '</tr>';

					}

				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		if($limit !== NULL and $ccCultivationFamily->count() > $limit) {
			$h .= '<p class="util-info" style="margin-top: -1rem">';
				$h .= p("+ {value} autre espèce", "+ {value} autres espèces", $ccCultivationFamily->count() - $limit);
			$h .= '</p>';
		}

		return $h;

	}

	public function getBestFamiliesPie(\Collection $ccCultivationFamily, int $season): string {

		$cCultivationFamily = $ccCultivationFamily[$season];

		return (new \analyze\ChartUi())->buildPie(
			s("Répartition des familles pour la saison {value}", $season),
			$cCultivationFamily,
			'area',
			fn($eCultivationFamily) => $eCultivationFamily['family']->empty() ? s("Non renseignée") : $eCultivationFamily['family']['name']
		);

	}

	public function getSeasons(\farm\Farm $eFarm, array $seasons, int $selectedSeason, string $selectedView): string {

		$h = ' '.\Asset::icon('chevron-right').' ';

		if(count($seasons) === 1) {
			$h .= $selectedSeason;
			return $h;
		}

		$h .= '<a class="util-action-navigation" data-dropdown="bottom-start" data-dropdown-hover="true">'.$selectedSeason.' '.\farm\FarmUi::getNavigation().'</a>';

		$h .= '<div class="dropdown-list dropdown-list-3 bg-secondary">';

			$h .= '<div class="dropdown-title">'.s("Changer la saison").'</div>';

			foreach($seasons as $season) {

				$url = \farm\FarmUi::urlAnalyzeCultivation($eFarm, $season, $selectedView);

				$h .= '<a href="'.$url.'" class="dropdown-item dropdown-item-full '.(($selectedSeason === $season) ? 'selected' : '').'">'. s("Saison {season}", ['season' => $season]) .'</a>';


			}

		$h .= '</div>';

		return $h;

	}

}
?>
