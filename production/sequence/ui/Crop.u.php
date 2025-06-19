<?php
namespace sequence;

class CropUi {

	public function __construct() {
		\Asset::css('sequence', 'crop.css');
		\Asset::js('sequence', 'crop.js');
	}

	public static function start(Crop|\series\Cultivation $eCrop, \Collection $cAction = new \Collection(), bool $displayYear = FALSE, bool $displayPrefix = TRUE, ?string $fontSize = NULL, ?string $class = NULL): string {

		if($eCrop->empty()) {
			return '';
		}

		$eCrop->expects([
			'startWeek', 'startAction'
		]);

		if($eCrop['startWeek'] === NULL) {
			return '';
		}

		$week = ($eCrop['startWeek'] + 100) % 100;

		$label = match($eCrop['startAction']) {
			Crop::SOWING => s("Semis direct"),
			Crop::PLANTING => s("Plantation")
		};

		if($displayPrefix) {
			$text = s("s{value}", $week);
		} else {
			$text = $week;
		}

		if($displayYear) {

			$years = SequenceUi::getTextualYear();

			if($eCrop['startWeek'] < 0) {
				$text .= ' '.$years[-1];
			}

			if($eCrop['startWeek'] > 100) {
				$text .= ' '.$years[1];
			}

		}

		if($cAction->notEmpty()) {

			$css = match($eCrop['startAction']) {
				Crop::SOWING => 'background-color: '.$cAction[ACTION_SEMIS_DIRECT]['color'].'; color: white;',
				Crop::PLANTING => 'background-color: '.$cAction[ACTION_PLANTATION]['color'].'; color: white;'
			};

			$h = '<span class="'.($class ?? 'plant-start plant-start-background').'" title="'.$label.'" style="'.$css.'; '.($fontSize ? 'font-size: '.$fontSize : '').'">';
				$h .= $text;
			$h .= '</span>';


		} else {

			$h = '<span class="'.($class ?? 'plant-start plant-start-border').'" title="'.$label.'" style="'.($fontSize ? 'font-size: '.$fontSize : '').'">';
				$h .= $text;
			$h .= '</span>';

		}
		return $h;

	}

	public static function startText(Crop|\series\Cultivation $eCrop, bool $displayYear = FALSE): string {

		$eCrop->expects([
			'startWeek', 'startAction'
		]);

		if($eCrop['startWeek'] === NULL) {
			return '';
		}

		$week = ($eCrop['startWeek'] + 100) % 100;

		$text = match($eCrop['startAction']) {
			Crop::SOWING => s("Semis direct"),
			Crop::PLANTING => s("Plantation")
		};

		$text .= ' '.s("s{value}", $week);

		if($displayYear) {

			$years = SequenceUi::getTextualYear();

			if($eCrop['startWeek'] < 0) {
				$text .= ' '.$years[-1];
			}

			if($eCrop['startWeek'] > 100) {
				$text .= ' '.$years[1];
			}

		}

		return $text;

	}

	public function displayBySequence(Sequence $eSequence, array $harvests, \Collection $cActionMain): string {

		$h = '';

		$cCrop = $eSequence['cCrop'];

		$h .= '<div class="crop-items">';

		foreach($cCrop as $eCrop) {

			$ePlant = $eCrop['plant'];
			$harvest = $harvests[$eCrop['plant']['id']] ?? [];

			$h .= '<div class="crop-item">';
				$h .= '<div class="crop-item-header">';
					$h .= '<div class="crop-item-title">';
						$h .= \plant\PlantUi::getVignette($ePlant, '3rem');
						$h .= '<h2>';
							$h .= \plant\PlantUi::link($ePlant);
							$h .= self::start($eCrop, $cActionMain, fontSize: '0.7em');
						$h .= '</h2>';

						if($eSequence->canWrite()) {

							$h .= '<div>';
								$h .= '<a data-dropdown="bottom-end" class="btn btn-color-primary dropdown-toggle">'.\Asset::icon('gear-fill').'</a>';
								$h .= '<div class="dropdown-list">';
									$h .= '<div class="dropdown-title">'.encode($ePlant['name']).'</div>';
									$h .= '<a href="/sequence/crop:update?id='.$eCrop['id'].'" class="dropdown-item">'.s("Modifier la production").'</a>';
									$h .= '<div class="dropdown-divider"></div>';
									$h .= '<a data-ajax="/sequence/crop:doDelete" post-id="'.$eCrop['id'].'" data-confirm="'.s("Souhaitez-vous réellement supprimer cette production de l'itinéraire technique ?").'" class="dropdown-item">'.s("Supprimer la production").'</a>';
								$h .= '</div>';
							$h .= '</div>';

						}

					$h .= '</div>';
					$h .= $this->getVarieties($eCrop, $eCrop['cSlice']);
				$h .= '</div>';

				$h .= '<div class="crop-item-presentation">';

					$filled = 0;
					$presentation = $this->getPresentation($eSequence, $eCrop, $harvest, $filled);

					if($filled > 0) {
						$h .= $presentation;
					} else {
						$h .= '<div class="text-center">';
							$h .= '<a href="/sequence/crop:update?id='.$eCrop['id'].'&actions" class="btn mt-1 mb-1 btn-outline-primary">'.s("Configurer maintenant").'</a>';
						$h .= '</div>';
					}

				$h .= '</div>';
			$h .= '</div>';

		}

		$h .= '</div>';

		return $h;

	}

	public function getVarieties(\Element $eCrop, \Collection $cSlice): string {

		$h = '<div class="crop-item-varieties crop-item-varieties-'.$cSlice->count().'">';

		foreach($cSlice as $eSlice) {

			$h .= '<div>';
				$h .= '<span class="crop-item-varieties-label">'.encode($eSlice['variety']['name']).'</span>';
				$h .= '<span class="crop-item-varieties-part">'.$eSlice->formatPart($eCrop).'</span>';
			$h .= '</div>';

		}

		$h .= '</div>';

		return $h;

	}

	public function getPresentation(\Element $eSequence, \Element $eCrop, array $harvest, int &$filled): string {

		$h = '<dl class="util-presentation util-presentation-max-content util-presentation-2">';
			$h .= $this->getPresentationYieldExpected($eSequence, $eCrop, $filled);
			$h .= $this->getPresentationDistance($eSequence, $eCrop, $filled);
			$h .= $this->getPresentationSeedling($eSequence, $eCrop, $filled);
			$h .= $this->getPresentationSeedlingSeeds($eSequence, $eCrop);
			if($harvest) {
				$h .= $this->getPresentationHarvest($eSequence, $eCrop, $harvest, $filled);
			}
		$h .= '</dl>';

			return $h;

	}

	public function getPresentationSeedling(\Element $eSequence, \Element $eCrop, ?int &$filled = 0): string {

		$filled += (int)($eCrop['seedling'] !== NULL);
		return '<dt>'.s("Implantation").'</dt><dd>'.($eCrop['seedling'] ? self::p('seedling')->values[$eCrop['seedling']] : '').'</dd>';

	}

	public function getPresentationSeedlingSeeds(\Element $eSequence, \Element $eCrop): string {

		if($eCrop['seedling'] === Crop::YOUNG_PLANT) {
			return '<dt>'.s("Semences").'</dt><dd>'.s("{value} / plant", $eCrop->quick('seedlingSeeds', $eCrop['seedlingSeeds'])).'</dd>';
		} else if($eCrop['seedling'] === Crop::SOWING and $eCrop['seedlingSeeds'] > 1) {
			return '<dt>'.s("Semences").'</dt><dd>'.s("{value} / trou", $eCrop->quick('seedlingSeeds', $eCrop['seedlingSeeds'])).'</dd>';
		} else {
			return '<dt></dt><dd></dd>';
		}


	}

	public function getPresentationYieldExpected(\Element $eSequence, \Element $eCrop, ?int &$filled = 0): string {

		$filled += (int)($eCrop['yieldExpected'] !== NULL);

		$h = '<dt>'.$this->p('yieldExpected').'</dt>';
		$h .= '<dd>';
			$h .= ($eCrop['yieldExpected'] ? $eCrop->quick('yieldExpected', s("{value} / m²", $eCrop->format('yieldExpected', ['short' => TRUE]))) : '');
		$h .= '</dd>';

		return $h;

	}

	public function getPresentationDistance(\Element $eSequence, \Element $eCrop, ?int &$filled = 0): string {

		switch($eCrop['distance']) {

			case Crop::SPACING :
				return $this->getPresentationSpacing($eSequence, $eCrop, $filled);

			case Crop::DENSITY :
				$filled += (int)($eCrop['density'] !== NULL);
				return '<dt>'.s("Densité").'</dt><dd>'.s("{value} / m²", $eCrop['density'] ?? '?').'</dd>';

		}

	}

	public function getPresentationHarvest(\Element $eSequence, \Element $eCrop, array $harvest, ?int &$filled = 0): string {

		$h = '<dt class="crop-item-harvest-label">';
			$h .= s("Mois de récolte");
		$h .= '</dt>';
		$h .= '<dd class="crop-item-harvest-value">'.$this->getMonthlyPeriod(date('Y'), $eSequence, $harvest, $filled).'</dd>';

		return $h;

	}

	public function getPresentationSpacing(\Element $eSequence, \Element $eCrop, ?int &$filled = 0): string {

		$h = '';

		switch($eSequence['use']) {

			case Sequence::BED :

				$h .= '<dt title="'.s("Espace sur le rang x Nombre de rangs").'">'.s("Densité").'</dt>';
				$h .= '<dd>';

					if($eCrop['plantSpacing'] !== NULL or $eCrop['rows'] !== NULL) {

						$filled++;

						$distance = p("{plantSpacing} cm x {rows} rang", "{plantSpacing} cm x {rows} rangs", $eCrop['rows'] ?? 0, ['plantSpacing' => $eCrop['plantSpacing'] ?? '?', 'rows' => $eCrop['rows'] ?? '?']);

						if($eCrop['density'] !== NULL) {
							if($eCrop['density'] < 10) {
								$density = round($eCrop['density'], 1);
							} else {
								$density = round($eCrop['density']);
							}
							$distance .= ' ('.s("{value} / m²", $density).')';
						}

						$h .= $distance;

					}

				$h .= '</dd>';

				break;

			case Sequence::BLOCK :

				$h .= '<dt title="'.s("Espace sur le rang x Espace inter-rangs").'">'.s("Densité").'</dt>';
				$h .= '<dd>';

					if($eCrop['plantSpacing'] !== NULL or $eCrop['rowSpacing'] !== NULL) {

						$filled++;

						$distance = s("{value} cm", ($eCrop['plantSpacing'] ?? '?').' x '.($eCrop['rowSpacing'] ?? '?'));

						if($eCrop['density'] !== NULL) {
							$distance .= ' ('.s("{value} / m²", round($eCrop['density'], 1)).')';
						}

						$h .= $distance;

					}

				$h .= '</dd>';

				break;

		}

		return $h;

	}

	public function getMonthlyPeriod(int $season, Sequence $eSequence, array $harvest, ?int &$filled = 0): string {

		\Asset::css('series', 'cultivation.css');

		$eSequence->expects([
			'cycle'
		]);

		$monthsList = [
			$season - 1 => [10, 11, 12],
			$season => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
			$season + 1 => [1, 2, 3, 4, 5, 6],
		];

		$harvestMonths = [];
		$harvestMonthsFilled = [];

		foreach($monthsList as $year => $months) {

			$harvestMonths[$year] = $this->getHarvestMonths($harvest, $year, $months);
			$harvestMonthsFilled[$year] = array_filter($harvestMonths[$year]);

		}

		if($harvestMonthsFilled) {
			$filled++;
		}

		$sequence = ($harvestMonthsFilled[$season - 1] ? '1' : '0').''.($harvestMonthsFilled[$season + 1] ? '1' : '0');

		$h = '<div class="cultivation-periods cultivation-periods-'.$sequence.' cultivation-months">';

		foreach($monthsList as $year => $months) {

			if(
				$year !== $season and
				$harvestMonthsFilled[$year] === []
			) {
				continue;
			}

			$h .= '<div class="cultivation-periods-season cultivation-periods-season-'.count($months).'">';

				if($harvestMonthsFilled[$season - 1] or $harvestMonthsFilled[$season + 1]) {
					$h .= '<div class="cultivation-periods-year" style="grid-column: span '.count($months).'">';
						if($year === $season) {
							$h .= s("Saison");
						} else {
							$h .= '&nbsp;';
						}
					$h .= '</div>';
				}

				$h .= $this->getPeriodMonths($harvestMonths[$year]);

			$h .= '</div>';

		}

		$h .= '</div>';

		return $h;

	}

	protected function getHarvestMonths(array $harvest, int $year, array $months): array {

		$harvestMonths = [];

		foreach($months as $month) {
			$harvestMonths[$year.'-'.sprintf('%02d', $month)] = NULL;
		}

		foreach($harvest as $monthExpected) {

			if(array_key_exists($monthExpected, $harvestMonths)) {
				$harvestMonths[$monthExpected] = 'expected';
			}

		}

		return $harvestMonths;

	}

	protected function getPeriodMonths(array $harvestMonths): string {

		$labels = \util\DateUi::months(type: 'letter');

		$h = '';

		foreach($harvestMonths as $date => $status) {

			$checked = ($status !== NULL);

			$h .= '<label class="cultivation-month">';

				if($checked) {
					$h .= '<span class="cultivation-month-checked cultivation-month-'.$status.'"></span>';
				}

				$h .= '<div>'.$labels[date_month($date)].'</div>';
			$h .= '</label>';

		}

		return $h;

	}

	public function create(Sequence $eSequence): \Panel {

		$eSequence->expects(['farm']);

		$form = new \util\FormUi();

		$eCrop = new Crop([
			'sequence' => $eSequence,
			'farm' => $eSequence['farm'],
		]);


		$h = '';

		$h .= $form->openAjax('/sequence/crop:doCreate', ['id' => 'crop-create', 'autocomplete' => 'off']);

			$h .= $form->hidden('sequence', $eSequence['id']);
			$h .= $form->dynamicGroup($eCrop, 'plant', function($d) {
				$d->autocompleteDispatch = '#crop-create';
			});
			$h .= '<div id="crop-create-content"></div>';

		$h .= $form->close();

		return new \Panel(
			id: 'panel-crop-create',
			title: s("Ajouter une production"),
			subTitle: SequenceUi::getPanelHeader($eSequence),
			body: $h
		);

	}

	public function createContent(Sequence $eSequence, \Collection $ccVariety, \Collection $cAction): string {

		$eSequence->expects(['farm', 'plants']);

		$form = new \util\FormUi();

		$eSequence['plants']++;

		$eCrop = new Crop([
			'ccVariety' => $ccVariety,
			'sequence' => $eSequence,
			'farm' => $eSequence['farm'],
			'seedling' => NULL,
			'distance' => Crop::SPACING,
			'mainUnit' => Crop::model()->getDefaultValue('mainUnit')
		]);


		$h = $this->getVarietyGroup($form, $eCrop, $eCrop['ccVariety'], new \Collection());
		$h .= $this->getFieldsWrite($form, $eCrop, $cAction);

		$h .= $form->group(
			content: $form->submit(s("Ajouter la production"))
		);

		return $h;

	}

	public function update(Crop $eCrop, \Collection $cAction): \Panel {

		$eCrop->expects([
			'cSlice', 'ccVariety'
		]);

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/sequence/crop:doUpdate', ['id' => 'crop-update', 'autocomplete' => 'off']);

			$h .= $form->hidden('id', $eCrop['id']);

			$h .= $form->dynamicGroup($eCrop, 'plant', function($d) use($eCrop) {
				$d->autocompleteDispatch = '#crop-update';
				$d->attributes = [
					'post-id' => $eCrop['id']
				];
			});

			$h .= $this->getVarietyGroup($form, $eCrop, $eCrop['ccVariety'], $eCrop['cSlice']);
			$h .= $this->getFieldsWrite($form, $eCrop, $cAction);

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-crop-update',
			title: \plant\PlantUi::getVignette($eCrop['plant'], '3rem').' '.encode($eCrop['plant']['name']),
			body: $h,
			subTitle: SequenceUi::getPanelHeader($eCrop['sequence'])
		);

	}

	public function getFieldsWrite(\util\FormUi $form, Crop $eCrop, \Collection $cAction): string {

		$eCrop->expects([
			'sequence' => ['use', 'plants']
		]);

		$h = '';

		$h .= $this->getDistanceField($form, $eCrop, $eCrop['sequence']['use']);

		$h .= $form->dynamicGroup($eCrop, 'seedling');
		$h .= $form->dynamicGroup($eCrop, 'seedlingSeeds');

		if($cAction->notEmpty()) {
			$h .= $this->getActionsField($form, $eCrop, $cAction);
		}

		$h .= $this->getMainUnitField($form, $eCrop);
		$h .= $this->getYieldExpectedField($form, $eCrop);

		return $h;

	}

	private function getActionsField(\util\FormUi $form, Crop $eCrop, \Collection $cAction): string {

		$h = $form->group(
			'<span class="util-badge ml-1" style="background-color: '.$cAction[ACTION_SEMIS_PEPINIERE]['color'].'">'.s("Semaine de semis en pépinière").'</span>',
			$this->getWeekField($form, $eCrop, 'actions['.ACTION_SEMIS_PEPINIERE.']'),
			['class' => 'hide']
		);

		$h .= $form->group(
			'<span class="util-badge ml-1" style="background-color: '.$cAction[ACTION_PLANTATION]['color'].'">'.s("Semaine de plantation").'</span>',
			$this->getWeekField($form, $eCrop, 'actions['.ACTION_PLANTATION.']'),
			['class' => 'hide']
		);

		$h .= $form->group(
			'<span class="util-badge ml-1" style="background-color: '.$cAction[ACTION_SEMIS_DIRECT]['color'].'">'.s("Semaine de semis direct").'</span>',
			$this->getWeekField($form, $eCrop, 'actions['.ACTION_SEMIS_DIRECT.']'),
			['class' => 'hide']
		);

		return $h;

	}

	public function getWeekField(\util\FormUi $form, Crop $eCrop, string $prefix): string {

		\Asset::css('sequence', 'flow.css');

		$h = '<div class="flow-write-week">';
			$h .= $form->week($prefix.'[week]', attributes: ['withYear' => FALSE]);
			if($eCrop['sequence']['cycle'] === Sequence::ANNUAL) {
				$h .= $form->select($prefix.'[year]', FlowUi::getYearsSelect(), 0, attributes: ['mandatory' => TRUE]);
			}
		$h .= '</div>';

		return $h;

	}

	public function getVarietyGroup(\util\FormUi $form, Crop|\series\Cultivation $eCrop, \Collection $ccVariety, \Collection $cSlice, string $suffix = ''): string {

		return $form->group(
			$this->p('variety')->label,
			new SliceUi()->select($form, $suffix, $eCrop, $ccVariety, $cSlice),
			['wrapper' => 'variety'.$suffix.' varietyCreate'.$suffix, 'data-ref' => 'crop-field-variety'.$suffix]
		);

	}

	public function getDistanceField(\util\FormUi $form, Crop|\series\Cultivation $eCrop, string $use, string $suffix = ''): string {

		$eCrop->expects(['distance']);

		$fields = $form->dynamicField($eCrop, 'plantSpacing'.$suffix);
		$fields .= '<span>&nbsp;&nbsp;x&nbsp;&nbsp;</span>';
		$fields .= $form->dynamicField($eCrop, 'rows'.$suffix, function(\PropertyDescriber $d) use($use) {
			$d->inputGroup['class'] = 'crop-write-rows';
			if($use === Sequence::BLOCK) {
				$d->inputGroup['class'] .= ' hide';
			}
		});
		$fields .= $form->dynamicField($eCrop, 'rowSpacing'.$suffix, function(\PropertyDescriber $d) use($use) {
			$d->inputGroup['class'] = 'crop-write-row-spacing';
			if($use === Sequence::BED) {
				$d->inputGroup['class'] .= ' hide';
			}
		});

		$h = $form->group(
			s("Espacement des plantes"),
			'<div class="crop-write-spacing-values">'.
				$fields.
			'</div>'.
			'<div class="field-followup">'.
				'<a '.attr('onclick', 'Crop.changeSpacing(this, "'.Crop::DENSITY.'")').'>'.s("Raisonner en densité au m²").'</a>'.
			'</div>',
			['class' => 'crop-write-spacing '.($eCrop['distance'] === Crop::SPACING ? '' : 'hide'), 'wrapper' => 'plantSpacing rowSpacing rows']
		);

		$h .= $form->dynamicGroup($eCrop, 'density'.$suffix, function($d) use($eCrop) {
			$d->default = function($eCrop) {
				return isset($eCrop['density']) ? round($eCrop['density'], 1) : NULL;
			};
			$d->after = '<div class="field-followup">'.
				'<a '.attr('onclick', 'Crop.changeSpacing(this, "'.Crop::SPACING.'")').'>'.s("Raisonner en espacement des plantes").'</a>'.
			'</div>';
			$d->group['class'] = ($eCrop['distance'] === Crop::DENSITY ? '' : 'hide');
		});

		$h .= $form->hidden('distance'.$suffix, $eCrop['distance']);

		return $h;

	}

	private function getMainUnitField(\util\FormUi $form, Crop $eCrop): string {

		return $form->dynamicGroup($eCrop, 'mainUnit', function(\PropertyDescriber $d) {
			$d->attributes = ['onchange' => 'Crop.changeUnit(this, "crop-unit")'];
		});

	}

	private function getYieldExpectedField(\util\FormUi $form, Crop $eCrop): string {

		return $form->dynamicGroup($eCrop, 'yieldExpected', function(\PropertyDescriber $d) use($eCrop) {
			$d->append = s("{value}&nbsp;/ m²", '<span data-ref="crop-unit">'.self::p('mainUnit')->values[$eCrop['mainUnit']].'</span>');
		});
	}

	public static function getYield(\Element $e, string $propertyValue, string $propertyUnit, array $options): ?string {

		$e->expects([$propertyValue, $propertyUnit]);

		if($e[$propertyValue] === NULL) {
			return NULL;
		}

		$short = $options['short'] ?? FALSE;

		return \selling\UnitUi::getValue($e[$propertyValue], $e[$propertyUnit], $short);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Crop::model()->describer($property, [
			'sequence' => s("Itinéraire technique"),
			'plant' => s("Espèce"),
			'density' => s("Densité d'implantation"),
			'rows' => s("Nombre de rangs par planche"),
			'rowSpacing' => s("Espace inter-rangs"),
			'plantSpacing' => s("Espace sur le rang"),
			'seedling' => s("Implantation"),
			'seedlingSeeds' => '<span class="crop-field-young-plant">'.s("Nombre de graines par plant").'</span><span class="crop-field-sowing">'.s("Nombre de graines par trou").'</span>',
			'yieldExpected' => s("Objectif de rendement"),
			'mainUnit' => s("Unité de récolte principale"),
			'variety' => s("Variété"),
		]);

		switch($property) {

			case 'seedling' :
				$d->values = [
					Crop::SOWING => s("semis direct"),
					Crop::YOUNG_PLANT => s("plant")
				];
				$d->attributes = [
					'columns' => 2,
					'mandatory' => TRUE,
					'onchange' => 'Crop.changeSeedling(this)'
				];
				break;

			case 'seedlingSeeds' :
				$d->append = '<span class="crop-field-young-plant">'.s("graine(s) / plant").'</span><span class="crop-field-sowing">'.s("graine(s) / trou").'</span>';
				$d->group = fn($e) => [
					'data-action' => $e['seedling'] ?? ''
				];
				$d->attributes = [
					'onfocus' => 'this.select()'
				];
				break;

			case 'rowSpacing' :
				$d->append = s("cm inter-rangs");
				break;

			case 'plantSpacing' :
				$d->append = s("cm sur le rang");
				break;

			case 'rows' :
				$d->append = s("rangs");
				break;

			case 'density' :
				$d->append = s("Espèces / m²");
				break;

			case 'mainUnit' :
				$d->field = 'select';
				$d->attributes = ['mandatory' => TRUE];
				$d->values = \selling\UnitUi::getBasicList();
				break;

			case 'plant' :
				new \plant\PlantUi()->query($d);
				$d->group = ['wrapper' => 'plant'];
				$d->autocompleteBody = function(\util\FormUi $form, Crop $e) {
					$e->expects(['farm']);
					return [
						'farm' => $e['farm']['id'],
						'new' => TRUE
					];
				};
				break;

		}

		return $d;

	}

}
?>
