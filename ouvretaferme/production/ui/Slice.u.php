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

	public function select(\util\FormUi $form, string $nameVariety, ?string $nameUnit, Crop|\series\Cultivation $eCrop, \Collection $ccVariety, \Collection $cSlice): string {

		if($eCrop instanceof Crop) {

			$onlyPercent = TRUE;

		} else {

			$eCrop->expects([
				'series' => ['use', 'area', 'length']
			]);

			$onlyPercent = ($eCrop['series']['area'] === 0);

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

						if($onlyPercent === FALSE) {

							$labels = [
								\series\Cultivation::LENGTH => s("Répartir au mL"),
								\series\Cultivation::AREA => s("Répartir au m²"),
								\series\Cultivation::PERCENT => s("Répartir en %"),
								\series\Cultivation::PLANT => match($eCrop['seedling']) {
									\series\Cultivation::SOWING => s("Répartir à la graine"),
									default => s("Répartir au plant"),
								},
							];

							$h .= '<a data-dropdown="bottom-start" class="btn btn-sm btn-outline-primary dropdown-toggle slice-several slice-unit-dropdown">'.$labels[$eCrop['sliceUnit']].'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<div class="dropdown-title">'.s("Répartir les variétés").'</div>';

								$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::PERCENT].'" data-unit="'.\series\Cultivation::PERCENT.'" class="dropdown-item '.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? 'selected' : '').'">'.s("en %").'</a>';

								if($eCrop['series']['use'] === \series\Series::BED) {
									$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::LENGTH].'" data-unit="'.\series\Cultivation::LENGTH.'" class="dropdown-item '.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? '' : 'selected').'">'.s("au mL de planche").'</a>';
								} else {
									$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::AREA].'" data-unit="'.\series\Cultivation::AREA.'" class="dropdown-item '.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? '' : 'selected').'">'.s("au m²").'</a>';
								}

								$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-label="'.$labels[\series\Cultivation::PLANT].'" data-unit="'.\series\Cultivation::PLANT.'" class="dropdown-item '.($eCrop['sliceUnit'] === \series\Cultivation::PLANT ? 'selected' : '').'">';
									$h .= match($eCrop['seedling']) {
										\series\Cultivation::SOWING => s("au nombre de graines"),
										default => s("au nombre de plants"),
									};
								$h .= '</a>';

							$h .= '</div>';

							$h .= $form->hidden($nameUnit, $eCrop['sliceUnit']);

						}

					$h .= '</div>';

					$h .= '<div>';

						$value = $cSlice->sum('partPercent');

						if($eCrop['sliceUnit'] === \series\Cultivation::PERCENT) {

							if($value > 100) {
								$color = 'color-danger';
							} else if($value < 100) {
								$color = 'color-warning';
							} else {
								$color = 'color-success';
							}

						} else {
							$color = '';
						}

						$h .= '<span class="slice-item-limit slice-several '.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? '' : 'hide').' '.$color.'" data-unit="'.\series\Cultivation::PERCENT.'">'.s(" {value} %", '<span class="slice-action-sum">'.$value.'</span>').'</span>';

						if($onlyPercent === FALSE) {

							$limit = match($eCrop['series']['use']) {
								Series::BED => $eCrop['series']['length'] ?? $eCrop['series']['lengthTarget'],
								Series::BLOCK => $eCrop['series']['area'] ?? $eCrop['series']['areaTarget'],
							};

							$value = match($eCrop['series']['use']) {
								Series::BED => $cSlice->sum('partLength'),
								Series::BLOCK => $cSlice->sum('partArea'),
							};

							if(in_array($eCrop['sliceUnit'], [\series\Series::BED, \series\Series::BLOCK])) {

								if($value > $limit) {
									$color = 'color-danger';
								} else if($value < $limit) {
									$color = 'color-warning';
								} else {
									$color = 'color-success';
								}

							} else {
								$color = '';
							}

							if($eCrop['series']['use'] === \series\Series::BED) {

								$h .= '<span class="slice-item-limit slice-several '.$color.' '.($eCrop['sliceUnit'] === \series\Cultivation::LENGTH ? '' : 'hide').'" data-unit="'.\series\Cultivation::LENGTH.'">';
									$h .= s("{value} {limit} mL", ['value' => '<span class="slice-action-sum">'.$value.'</span>', 'limit' => ($limit > 0 ? '/ ' : '').'<span class="slice-action-max">'.$limit.'</span>']);
								$h.= '</span>';

							} else {

								$h .= '<span class="slice-item-limit slice-several '.$color.' '.($eCrop['sliceUnit'] === \series\Cultivation::AREA ? '' : 'hide').'" data-unit="'.\series\Cultivation::AREA.'">';
									$h .= s("{value} {limit} m²", ['value' => '<span class="slice-action-sum">'.$value.'</span>', 'limit' => ($limit > 0 ? '/ ' : '').'<span class="slice-action-max">'.$limit.'</span>']);
								$h.= '</span>';

							}

							$value = $cSlice->sum('partPlant');

							$limit = match($eCrop['seedling']) {
								\series\Cultivation::SOWING => $eCrop->getSeeds($eCrop['series']),
								default => $eCrop->getYoungPlants($eCrop['series']),
							};

							$arguments = ['value' => '<span class="slice-action-sum">'.$value.'</span>', 'limit' => ($limit > 0 ? '/ ' : '').'<span class="slice-action-max">'.$limit.'</span>'];

							$h .= '<span class="slice-item-limit slice-several '.$color.' '.($eCrop['sliceUnit'] === \series\Cultivation::PLANT ? '' : 'hide').'" data-unit="'.\series\Cultivation::PLANT.'">';
								$h .= match($eCrop['seedling']) {
									\series\Cultivation::SOWING => s("{value} {limit} graines", $arguments),
									default => s("{value} {limit} plants", $arguments),
								};
							$h.= '</span>';

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

	protected function getField(\util\FormUi $form, string $name, Crop|\series\Cultivation $eCrop, \Collection $ccVariety, Slice|\series\Slice $eSlice): string {

		if($eCrop instanceof Crop) {
			$onlyPercent = TRUE;
		} else {

			$eCrop->expects([
				'series' => ['area']
			]);

			$onlyPercent = ($eCrop['series']['area'] === 0);

		}

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
				
				if($onlyPercent === FALSE) {
					
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
