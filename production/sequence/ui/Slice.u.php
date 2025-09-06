<?php
namespace sequence;

class SliceUi {

	public function __construct() {

		\Asset::js('sequence', 'slice.js');
		\Asset::css('sequence', 'slice.css');

	}

	public function getLine(Crop|\series\Cultivation $eCrop, \Collection $cSlice): string {

		if($cSlice->empty()) {
			return '';
		}

		$output = [];

		foreach($cSlice as $eSlice) {

			if($eSlice['variety']['id'] !== \plant\PlantSetting::VARIETY_UNKNOWN) {
				$output[] = '<span title="'.$eSlice->formatPart($eCrop).'">'.encode($eSlice['variety']['name']).'</span>';
			}

		}

		return implode(' / ', $output);

	}

	public function select(\util\FormUi $form, string $suffix, Crop|\series\Cultivation $eCrop, \Collection $cVariety, \Collection $cSlice): string {

		$nameVariety = 'variety'.$suffix;
		$nameUnit = 'sliceUnit'.$suffix;
		$nameTool = 'sliceTool'.$suffix;

		if($cSlice->empty()) {

			$cSlice->append(new Slice([
				'partPercent' => 100
			]));

			$sliceUnit = \series\Cultivation::PERCENT;
			$sliceTool = new \farm\Tool();

		} else {

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

		}

		$id = uniqid('slice-select-');

		$h = '<div class="slice-wrapper" id="'.$id.'">';

			$h .= '<div class="slice-items">';

				$h .= $cSlice->makeString(fn($eSlice) => $this->getField($form, $nameVariety, $eCrop, $cVariety, $eSlice));

				$h .= '<div class="slice-item-new">';
					$h .= '<div class="slice-item-actions">';
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

							$h .= '<a data-dropdown="bottom-start" class="btn btn-sm btn-outline-primary dropdown-toggle slice-unit-dropdown">'.$labels[$eCrop['sliceUnit']]().'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<div class="dropdown-title">'.s("Répartir les variétés").'</div>';

								$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::PERCENT]().'" data-unit="'.\series\Cultivation::PERCENT.'" class="dropdown-item '.($sliceUnit === \series\Cultivation::PERCENT ? 'selected' : '').'">'.s("en %").'</a>';

								$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::LENGTH]().'" data-unit="'.\series\Cultivation::LENGTH.'" class="dropdown-item '.($sliceUnit === \series\Cultivation::LENGTH ? 'selected' : '').' '.($eCrop['series']['use'] === \series\Series::BED ? '' : 'hide').'">'.s("au mL de planche").'</a>';

								$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::AREA]().'" data-unit="'.\series\Cultivation::AREA.'" class="dropdown-item '.($sliceUnit === \series\Cultivation::AREA ? 'selected' : '').' '.($eCrop['series']['use'] === \series\Series::BLOCK ? '' : 'hide').'">'.s("au m²").'</a>';

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

						$h .= '<span class="slice-item-limit '.($sliceUnit === \series\Cultivation::PERCENT ? '' : 'hide').' '.$color.'" data-unit="'.\series\Cultivation::PERCENT.'">';
							$h .= $this->getLimit($value, NULL, s("%"));
						$h .= '</span>';

						if($eCrop instanceof \series\Cultivation) {

							$limit = $eCrop['series']['length'] ?? $eCrop['series']['lengthTarget'];

							if($sliceUnit === \series\Cultivation::LENGTH) {
								$value = $cSlice->sum('partLength');
								$color = $this->getColor($value, $limit);
							} else {
								$color = '';
								$value = 0;
							}

							$h .= '<span class="slice-item-limit '.$color.' '.($sliceUnit === \series\Cultivation::LENGTH ? '' : 'hide').'" data-unit="'.\series\Cultivation::LENGTH.'">';
								$h .= $this->getLimit($value, $limit, s("mL"));
							$h.= '</span>';

							$limit = $eCrop['series']['area'] ?? $eCrop['series']['areaTarget'];

							if($sliceUnit === \series\Cultivation::AREA) {
								$value = $cSlice->sum('partArea');
								$color = $this->getColor($value, $limit);
							} else {
								$color = '';
								$value = 0;
							}

							$h .= '<span class="slice-item-limit '.$color.' '.($sliceUnit === \series\Cultivation::AREA ? '' : 'hide').'" data-unit="'.\series\Cultivation::AREA.'">';
								$h .= $this->getLimit($value, $limit, s("m²"));
							$h.= '</span>';

							$plantLimit = match($eCrop['seedling']) {
								\series\Cultivation::SOWING => $eCrop->getSeeds() ?: NULL,
								default => $eCrop->getYoungPlants() ?: NULL,
							};

							if($sliceUnit === \series\Cultivation::PLANT) {
								$value = $cSlice->sum('partPlant');
								$color = $this->getColor($value, $plantLimit);
							} else {
								$value = 0;
								$color = '';
							}

							$h .= '<span class="slice-item-limit '.$color.' '.($sliceUnit === \series\Cultivation::PLANT ? '' : 'hide').'" data-unit="'.\series\Cultivation::PLANT.'">';
								$h .= $this->getLimit($value, $plantLimit, match($eCrop['seedling']) {
									\series\Cultivation::SOWING => s("graines"),
									default => s("plants"),
								});
							$h.= '</span>';

							if($eCrop['cTray']->notEmpty()) {

								foreach($eCrop['cTray'] as $eTray) {

									$visible = ($eCrop['sliceUnit'] === \series\Cultivation::TRAY and $eCrop['sliceTool']->is($eTray));

									if($sliceUnit === \series\Cultivation::TRAY and $sliceTool->is($eTray)) {
										$value = $cSlice->sum('partTray');
									} else {
										$value = 0;
									}
									
									$trayLimit = $plantLimit ? (int)ceil($plantLimit / $eTray['routineValue']['value']) : NULL;
									$color = $this->getColor($value, $trayLimit);

									$h .= '<span class="slice-item-limit '.$color.' '.($visible ? '' : 'hide').'" data-unit="'.\series\Cultivation::TRAY.'" data-tool="'.$eTray['id'].'" data-value="'.$eTray['routineValue']['value'].'">';
										$h .= $this->getLimit($value, $trayLimit, s("plateaux"));
									$h .= '</span>';

								}

							}

						}
					$h .= '</div>';
					$h .= '<a data-action="slice-fair" data-id="#'.$id.'" class="btn btn-outline-primary" title="'.s("Répartir égalitairement").'">'.\Asset::icon('justify').'</a>';
				$h .= '</div>';

			$h .= '</div>';
			$h .= '<div class="slice-spare">';
				$h .= $this->getField($form, $nameVariety, $eCrop, $cVariety, new Slice(), spare: TRUE);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getLimit(int $value, ?int $limit, string $suffix): string {

		$h = '<span class="slice-item-sum">'.$value.'</span>';
		$h .= '<span class="slice-item-by">';
			$h .= '<span class="slice-item-max">'.$limit.'</span>';
			$h .= '<span class="slice-item-unit">'.$suffix.'</span>';
			$h .= '<span class="slice-item-separator">/</span>';
		$h .= '</span>';

		return $h;

	}

	protected function getColor(int $value, ?int $limit): string {

		if($limit === NULL) {
			return '';
		} else if($value < $limit or $value > $limit) {
			return 'color-warning';
		} else {
			return 'color-success';
		}

	}

	protected function getField(\util\FormUi $form, string $name, Crop|\series\Cultivation $eCrop, \Collection $cVariety, Slice|\series\Slice $eSlice, bool $spare = FALSE): string {

		$h = '<div class="slice-item">';

			$h .= '<div class="slice-item-variety">';

				$h .= $form->dynamicField($eSlice, 'variety', function($d) use($cVariety, $name, $eSlice, $spare) {
					$d->values = $this->getFieldValues($cVariety);
					$d->name = $name.'['.($spare ? 'spare' : 'variety').'][]';
					$d->attributes = [
						'data-action' => 'slice-variety-change'
					];
					$d->attributes['mandatory'] = TRUE;
				});

				$h .= '<div class="slice-item-create" style="display: none">';
					$h .= $form->text($name.'['.($spare ? 'spare' : 'variety').'Create][]', '', ['data-action' => 'slice-variety-create', 'placeholder' => s("Tapez le nom de la variété")]);
					$h .= '<a class="slice-item-create-cancel" data-action="slice-variety-create-cancel">'.\Asset::icon('trash').'</a>';
				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div class="slice-item-arrow">'.\Asset::icon('arrow-right-short').'</div>';

			$h .= '<div class="slice-item-parts">';
				
				if($eCrop instanceof \series\Cultivation) {
					
					$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? '' : 'hide').'" data-unit="'.\series\Cultivation::PERCENT.'">';
						$h .= $form->inputGroup(
							$form->number($name.'[varietyPartPercent][]', $eSlice['partPercent'] ?? 0, ['min' => 0, 'onfocus' => 'this.select()']).
							$form->addon(s("%")),
						);
					$h .= '</div>';

					$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::AREA ? '' : 'hide').'" data-unit="'.\series\Cultivation::AREA.'">';
						$h .= $form->inputGroup(
							$form->number($name.'[varietyPartArea][]', $eSlice['partArea'] ?? 0, ['min' => 0, 'onfocus' => 'this.select()']).
							$form->addon(s("m²"))
						);
					$h .= '</div>';

					$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::LENGTH ? '' : 'hide').'" data-unit="'.\series\Cultivation::LENGTH.'">';
						$h .= $form->inputGroup(
							$form->number($name.'[varietyPartLength][]', $eSlice['partLength'] ?? 0, ['min' => 0, 'onfocus' => 'this.select()']).
							$form->addon(s("mL"))
						);
					$h .= '</div>';

					$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::PLANT ? '' : 'hide').'" data-unit="'.\series\Cultivation::PLANT.'">';
						$h .= $form->inputGroup(
							$form->number($name.'[varietyPartPlant][]', $eSlice['partPlant'] ?? 0, ['min' => 0, 'onfocus' => 'this.select()']).
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
									$form->number($name.'[varietyPartTray]['.$eTray['id'].'][]', $eSlice['partTray'] ?? 0, ['min' => 0, 'onfocus' => 'this.select()']).
									$form->addon(s("plateaux"))
								);
							$h .= '</div>';

						}

					}

				} else {

					$h .= '<div class="slice-item-part" data-unit="'.\series\Cultivation::PERCENT.'">';
						$h .= $form->inputGroup(
							$form->number($name.'[varietyPartPercent][]', $eSlice['partPercent'] ?? 0, ['min' => 0, 'onfocus' => 'this.select()']).
							$form->addon(s("%"))
						);
					$h .= '</div>';

				}

			$h .= '</div>';

			$h .= '<a class="btn slice-item-remove" data-action="slice-remove">'.\Asset::icon('trash').'</a>';

		$h .= '</div>';


		return $h;

	}

	protected function getFieldValues(\Collection $cVariety) {

		$values = [];

		foreach($cVariety as $eVariety) {

			if($eVariety['id'] === \plant\PlantSetting::VARIETY_MIX) {

				$values[] = [
					'label' => '* * *',
					'attributes' => ['class' => 'field-radio-separator', 'disabled']
				];

			}

			$values[] = $eVariety;

			if($eVariety['id'] === \plant\PlantSetting::VARIETY_UNKNOWN) {
				$values[] = [
					'label' => '* * *',
					'attributes' => ['class' => 'field-radio-separator', 'disabled']
				];
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
