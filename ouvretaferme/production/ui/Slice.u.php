<?php
namespace production;

use series\Series;

class SliceUi {

	public function __construct() {

		\Asset::js('production', 'slice.js');
		\Asset::css('production', 'slice.css');

	}

	public function getLine(\Collection $cSlice): string {

		if($cSlice->empty()) {
			return '';
		}

		return implode(' / ', $cSlice->makeArray(function($eSlice) {
			return '<span title="'.$eSlice->formatPart().'">'.encode($eSlice['variety']['name']).'</span>';
		}));

	}

	public function select(\util\FormUi $form, string $suffix, Crop|\series\Cultivation $eCrop, \Collection $ccVariety, \Collection $cSlice): string {

		$nameVariety = 'variety'.$suffix;
		$nameUnit = 'sliceUnit'.$suffix;
		$nameTool = 'sliceTool'.$suffix;

		if($eCrop instanceof Crop) {

			$sliceUnit = \series\Cultivation::PERCENT;
			$sliceTool = new \farm\Tool();

		} else {

			$eCrop->expects([
				'series' => ['use', 'area', 'length'],
				'cTray'
			]);

			$sliceUnit = $eCrop['sliceUnit'];
			$sliceTool = $eCrop['sliceTool'];

		}

		$id = uniqid('slice-select-');

		$h = '<div class="slice-wrapper" id="'.$id.'" data-n="'.max(1, $cSlice->count()).'">';

			$h .= '<div class="slice-items">';

				if($cSlice->empty()) {
					$h .= $this->getField($form, $nameVariety, $eCrop, $ccVariety, new Slice());
				} else {
					$h .= $cSlice->makeString(fn($eSlice) => $this->getField($form, $nameVariety, $eCrop, $ccVariety, $eSlice));
				}

				$h .= '<div class="slice-item-new">';
					$h .= '<div>';
						$h .= '<a data-action="slice-add" data-id="#'.$id.'" class="btn btn-sm btn-primary">'.s("Ajouter une variété").'</a> ';

						if($eCrop instanceof \series\Cultivation) {

							$labels = [
								\series\Cultivation::LENGTH => fn() => s("Répartir au mL"),
								\series\Cultivation::AREA => fn() => s("Répartir au m²"),
								\series\Cultivation::PERCENT => fn() => s("Répartir en %"),
								\series\Cultivation::PLANT => fn() => match($eCrop['seedling']) {
									\series\Cultivation::SOWING => s("Répartir à la graine"),
									default => s("Répartir au plant"),
								},
								\series\Cultivation::TRAY => fn() => encode($eCrop['sliceTool']['name']),
							];

							$h .= '<a data-dropdown="bottom-start" class="btn btn-sm btn-outline-primary dropdown-toggle slice-several slice-unit-dropdown">'.$labels[$eCrop['sliceUnit']]().'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<div class="dropdown-title">'.s("Répartir les variétés").'</div>';

								$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::PERCENT]().'" data-unit="'.\series\Cultivation::PERCENT.'" class="dropdown-item '.($sliceUnit === \series\Cultivation::PERCENT ? 'selected' : '').'">'.s("en %").'</a>';

								if($eCrop['series']['use'] === \series\Series::BED) {
									$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::LENGTH]().'" data-unit="'.\series\Cultivation::LENGTH.'" class="dropdown-item '.($sliceUnit === \series\Cultivation::LENGTH ? 'selected' : '').'">'.s("au mL de planche").'</a>';
								} else {
									$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::AREA]().'" data-unit="'.\series\Cultivation::AREA.'" class="dropdown-item '.($sliceUnit === \series\Cultivation::AREA ? 'selected' : '').'">'.s("au m²").'</a>';
								}

								$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::PLANT]().'" data-unit="'.\series\Cultivation::PLANT.'" class="dropdown-item '.($sliceUnit === \series\Cultivation::PLANT ? 'selected' : '').'">';
									$h .= match($eCrop['seedling']) {
										\series\Cultivation::SOWING => s("au nombre de graines"),
										default => s("au nombre de plants"),
									};
								$h .= '</a>';

								if($eCrop['cTray']->notEmpty()) {

									$h .= '<div class="dropdown-subtitle">'.s("au plateau de semis :").'</div>';

									foreach($eCrop['cTray'] as $eTray) {

										$selected = ($sliceUnit === \series\Cultivation::TRAY and $eCrop['sliceTool']->is($eTray)) ? 'selected' : '';

										$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.s("Plateau de {value}", $eTray['routineValue']['value']).'" data-unit="'.\series\Cultivation::TRAY.'" data-tool="'.$eTray['id'].'" style="padding-left: 2rem" class="dropdown-item '.$selected.'">'.encode($eTray['name']).'</a>';

									}

								}

							$h .= '</div>';

							$h .= $form->hidden($nameUnit, $sliceUnit);
							$h .= $form->hidden($nameTool, $sliceTool);

						} else {
							$h .= $form->hidden($nameUnit, \series\Cultivation::PERCENT);
							$h .= $form->hidden($nameTool, '');
						}

					$h .= '</div>';

					$h .= '<div>';

						$value = $cSlice->sum('partPercent');

						if($sliceUnit === \series\Cultivation::PERCENT) {
							$color = $this->getColor($value, 100);
						} else {
							$color = '';
						}

						$h .= '<span class="slice-item-limit slice-several '.($sliceUnit === \series\Cultivation::PERCENT ? '' : 'hide').' '.$color.'" data-unit="'.\series\Cultivation::PERCENT.'">'.s(" {value} %", '<span class="slice-action-sum">'.$value.'</span>').'</span>';

						if($eCrop instanceof \series\Cultivation) {

							$limit = match($eCrop['series']['use']) {
								Series::BED => $eCrop['series']['length'] ?? $eCrop['series']['lengthTarget'],
								Series::BLOCK => $eCrop['series']['area'] ?? $eCrop['series']['areaTarget'],
							};

							if(in_array($sliceUnit, [\series\Cultivation::LENGTH, \series\Cultivation::AREA])) {

								$value = match($eCrop['series']['use']) {
									Series::BED => $cSlice->sum('partLength'),
									Series::BLOCK => $cSlice->sum('partArea'),
								};
								$color = $this->getColor($value, $limit);

							} else {
								$color = '';
								$value = 0;
							}

							if($eCrop['series']['use'] === \series\Series::BED) {

								$h .= '<span class="slice-item-limit slice-several '.$color.' '.($sliceUnit === \series\Cultivation::LENGTH ? '' : 'hide').'" data-unit="'.\series\Cultivation::LENGTH.'">';
									$h .= s("{value} {limit} mL", ['value' => '<span class="slice-action-sum">'.$value.'</span>', 'limit' => ($limit > 0 ? '/ ' : '').'<span class="slice-action-max">'.$limit.'</span>']);
								$h.= '</span>';

							} else {

								$h .= '<span class="slice-item-limit slice-several '.$color.' '.($sliceUnit === \series\Cultivation::AREA ? '' : 'hide').'" data-unit="'.\series\Cultivation::AREA.'">';
									$h .= s("{value} {limit} m²", ['value' => '<span class="slice-action-sum">'.$value.'</span>', 'limit' => ($limit > 0 ? '/ ' : '').'<span class="slice-action-max">'.$limit.'</span>']);
								$h.= '</span>';

							}


							$plantLimit = match($eCrop['seedling']) {
								\series\Cultivation::SOWING => $eCrop->getSeeds(),
								default => $eCrop->getYoungPlants(),
							};

							if($sliceUnit === \series\Cultivation::PLANT) {
								$value = $cSlice->sum('partPlant');
								$color = $this->getColor($value, $plantLimit);
							} else {
								$value = 0;
								$color = '';
							}

							$arguments = ['value' => '<span class="slice-action-sum">'.$value.'</span>', 'limit' => ($plantLimit > 0 ? '/ ' : '').'<span class="slice-action-max">'.$plantLimit.'</span>'];

							$h .= '<span class="slice-item-limit slice-several '.$color.' '.($sliceUnit === \series\Cultivation::PLANT ? '' : 'hide').'" data-unit="'.\series\Cultivation::PLANT.'">';
								$h .= match($eCrop['seedling']) {
									\series\Cultivation::SOWING => s("{value} {limit} g.", $arguments),
									default => s("{value} {limit} p.", $arguments),
								};
							$h.= '</span>';

							if($eCrop['cTray']->notEmpty()) {

								foreach($eCrop['cTray'] as $eTray) {

									$visible = ($eCrop['sliceUnit'] === \series\Cultivation::TRAY and $eCrop['sliceTool']->is($eTray));

									if($sliceUnit === \series\Cultivation::TRAY and $sliceTool->is($eTray)) {
										$value = $cSlice->sum('partTray');
									} else {
										$value = 0;
									}
									
									$trayLimit = $plantLimit ? ceil($plantLimit / $eTray['routineValue']['value']) : NULL;
									$color = $this->getColor($value, $trayLimit);

									$h .= '<span class="slice-item-limit slice-several '.$color.' '.($visible ? '' : 'hide').'" data-unit="'.\series\Cultivation::TRAY.'" data-tool="'.$eTray['id'].'">';
										$h .= '<span class="slice-action-sum">'.$value.'</span>';
										$h .= ($trayLimit > 0 ? ' / ' : '');
										$h .= s("{value} p.", '<span class="slice-action-max">'.$trayLimit.'</span>');
									$h .= '</span>';

								}

							}

						}
					$h .= '</div>';
					$h .= '<a data-action="slice-fair" data-id="#'.$id.'" class="btn btn-outline-primary slice-several" title="'.s("Répartir égalitairement").'">'.\Asset::icon('justify').'</a>';
				$h .= '</div>';

			$h .= '</div>';
			$h .= '<div class="slice-spare">';
				$h .= $this->getField($form, $nameVariety, $eCrop, $ccVariety, new Slice());
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getColor(int $value, ?int $limit): string {

		if($limit === NULL) {
			return '';
		} else if($value > $limit) {
			return 'color-danger';
		} else if($value < $limit) {
			return 'color-warning';
		} else {
			return 'color-success';
		}

	}

	protected function getField(\util\FormUi $form, string $name, Crop|\series\Cultivation $eCrop, \Collection $ccVariety, Slice|\series\Slice $eSlice): string {

		$h = '<div class="slice-item">';

			$h .= '<div class="slice-item-variety">';

			if($ccVariety->empty()) {
				$h .= $form->hidden($name.'[variety][]', 'new');
				$h .= $this->getFieldCreate($form, $name, TRUE);
			} else {
				$h .= $form->dynamicField($eSlice, 'variety', function($d) use ($ccVariety, $name, $eSlice) {
					$d->values = $this->getFieldValues($ccVariety);
					$d->name = $name.'[variety][]';
					$d->attributes = [
						'data-action' => 'slice-variety-change'
					];
					if($eSlice->notEmpty()) {
						$d->attributes['mandatory'] = TRUE;
					}
				});
				$h .= $this->getFieldCreate($form, $name, FALSE);
			}

			$h .= '</div>';

			$h .= '<div class="slice-item-arrow">'.\Asset::icon('arrow-right-short').'</div>';

			$h .= '<div class="slice-item-parts">';
				
				if($eCrop instanceof \series\Cultivation) {
					
					$eSeries = $eCrop['series'];

					$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? '' : 'hide').'" data-unit="'.\series\Cultivation::PERCENT.'">';
						$h .= $form->inputGroup(
							$form->number($name.'[varietyPartPercent][]', $eSlice['partPercent'] ?? 0, ['min' => 0, 'onclick' => 'this.select()']).
							$form->addon(s("%")),
						);
					$h .= '</div>';

					if($eSeries['use'] === \series\Series::BLOCK) {
						$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::AREA ? '' : 'hide').'" data-unit="'.\series\Cultivation::AREA.'">';
							$h .= $form->inputGroup(
								$form->number($name.'[varietyPartArea][]', $eSlice['partArea'] ?? 0, ['min' => 0, 'onclick' => 'this.select()']).
								$form->addon(s("m²"))
							);
						$h .= '</div>';
					}
	
					if($eSeries['use'] === \series\Series::BED) {
						$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::LENGTH ? '' : 'hide').'" data-unit="'.\series\Cultivation::LENGTH.'">';
							$h .= $form->inputGroup(
								$form->number($name.'[varietyPartLength][]', $eSlice['partLength'] ?? 0, ['min' => 0, 'onclick' => 'this.select()']).
								$form->addon(s("mL"))
							);
						$h .= '</div>';
					}

					$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::PLANT ? '' : 'hide').'" data-unit="'.\series\Cultivation::PLANT.'">';
						$h .= $form->inputGroup(
							$form->number($name.'[varietyPartPlant][]', $eSlice['partPlant'] ?? 0, ['min' => 0, 'onclick' => 'this.select()']).
							$form->addon(match($eCrop['seedling']) {
								\series\Cultivation::SOWING => s("graines"),
								default => s("plants"),
							})
						);
					$h .= '</div>';

					if($eCrop['cTray']->notEmpty()) {

						foreach($eCrop['cTray'] as $eTray) {

							$visible = ($eCrop['sliceUnit'] === \series\Cultivation::TRAY and $eCrop['sliceTool']->is($eTray));

							$h .= '<div class="slice-item-part '.($visible ? '' : 'hide').'" data-unit="'.\series\Cultivation::TRAY.'" data-tool="'.$eTray['id'].'">';
								$h .= $form->inputGroup(
									$form->number($name.'[varietyPartTray]['.$eTray['id'].'][]', $eSlice['partTray'] ?? 0, ['min' => 0, 'onclick' => 'this.select()']).
									$form->addon(s("plateaux"))
								);
							$h .= '</div>';

						}

					}

				} else {

					$h .= '<div class="slice-item-part" data-unit="'.\series\Cultivation::PERCENT.'">';
						$h .= $form->inputGroup(
							$form->number($name.'[varietyPartPercent][]', $eSlice['partPercent'] ?? 0, ['min' => 0, 'onclick' => 'this.select()']).
							$form->addon(s("%"))
						);
					$h .= '</div>';

				}

			$h .= '</div>';

			$h .= '<a class="btn slice-item-remove" data-action="slice-remove">'.\Asset::icon('trash').'</a>';

		$h .= '</div>';


		return $h;

	}

	protected function getFieldCreate(\util\FormUi $form, string $name, bool $visible) {

		$h = '<div class="slice-item-create" style="display: '.($visible ? 'grid' : 'none').'">';
			$h .= $form->text($name.'[varietyCreate][]', '', ['data-action' => 'slice-variety-create', 'placeholder' => s("Tapez le nom de la variété")]);
			$h .= '<a class="slice-item-create-cancel" data-action="slice-variety-create-cancel">'.\Asset::icon('trash').'</a>';
		$h .= '</div>';

		return $h;

	}

	protected function getFieldValues(\Collection $ccVariety) {

		$values = [];

		if($ccVariety->offsetExists('farm')) {

			if($ccVariety->offsetExists('common')) {

				$values[] = [
					'label' => s("Variétés préférées"),
					'attributes' => ['class' => 'field-radio-separator', 'disabled', 'value']
				];

			}

			foreach($ccVariety['farm'] as $eVariety) {
				$values[] = $eVariety;
			}

		}

		if($ccVariety->offsetExists('common')) {

			if($ccVariety->offsetExists('farm')) {

				$values[] = [
					'label' => s("Autres variétés"),
					'attributes' => ['class' => 'field-radio-separator', 'disabled']
				];

			}

			foreach($ccVariety['common'] as $eVariety) {
				$values[] = $eVariety;
			}

		}

		if($ccVariety->offsetExists('other')) {

			if($ccVariety->offsetExists('farm') or $ccVariety->offsetExists('common')) {

				$values[] = [
					'label' => s("Autres choix"),
					'attributes' => ['class' => 'field-radio-separator', 'disabled']
				];

			}

			foreach($ccVariety['other'] as $eVariety) {
				$values[] = $eVariety;
			}

		}

		$values[] = [
			'value' => 'new',
			'label' => s("Ajouter une nouvelle variété"),
			'attributes' => ['class' => 'variety-field-new']
		];

		return $values;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Slice::model()->describer($property);

		return $d;

	}


}
?>
