<?php
namespace production;

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

				$h .= '<div>';
					$h .= '<a data-action="slice-add" data-id="#'.$id.'" class="btn btn-sm btn-outline-primary">'.s("Ajouter une variété").'</a>';
				$h .= '</div>';

			$h .= '</div>';
			$h .= '<div class="slice-actions">';

				if($onlyPercent) {

					$value = $cSlice->sum('partPercent');

					$h .= '<span class="slice-action-limit" data-unit="'.\series\Cultivation::PERCENT.'">'.s("{value} % sélectionnés", '<span class="slice-action-sum">'.$value.'</span>').'</span>';

				} else {

					if($eCrop['series']['use'] === \series\Series::BED) {

						$value = $cSlice->sum('partLength');
						$limit = $eCrop['series']['length'];

						$h .= '<span class="slice-action-limit '.($value > $limit ? 'color-danger' : '').' '.($eCrop['sliceUnit'] !== \series\Cultivation::PERCENT ? '' : 'hide').'" data-unit="'.\series\Cultivation::LENGTH.'">'.s("{value} / {limit} mL", ['value' => '<span class="slice-action-sum">'.$value.'</span>', 'limit' => '<span class="slice-action-max">'.$limit.'</span>']).'</span>';

					} else {

						$value = $cSlice->sum('partArea');
						$limit = $eCrop['series']['area'];

						$h .= '<span class="slice-action-limit '.($eCrop['sliceUnit'] !== \series\Cultivation::PERCENT ? '' : 'hide').'" data-unit="'.\series\Cultivation::AREA.'">'.s("{value} / {limit} m²", ['value' => '<span class="slice-action-sum">'.$value.'</span>', 'limit' => '<span class="slice-action-max">'.$limit.'</span>']).'</span>';

					}

					$value = $cSlice->sum('partPercent');

					$h .= '<span class="slice-action-limit '.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? '' : 'hide').'" data-unit="'.\series\Cultivation::PERCENT.'">'.s("{value} % sélectionnés", '<span class="slice-action-sum">'.$value.'</span>').'</span>';

					$h .= ' | ';

					$h .= s("Répartir : ");

					$h .= '<span class="slice-action-unit">';

						if($eCrop['series']['use'] === \series\Series::BED) {
							$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-unit="'.\series\Cultivation::LENGTH.'" class="'.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? '' : 'hide').'">'.s("au mL de planche").'</a>';
						} else {
							$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-unit="'.\series\Cultivation::AREA.'" class="'.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? '' : 'hide').'">'.s("au m²").'</a>';
						}

						$h .= '<a data-action="slice-unit" data-id="#'.$id.'" data-unit="'.\series\Cultivation::PERCENT.'" class="'.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? 'hide' : '').'">'.s("en %").'</a>';

					$h .= '</span>';

					$h .= ' '.s("ou").' ';

					$h .= '<a data-action="slice-fair" data-id="#'.$id.'">'.s("égalitairement").'</a>';

					$h .= $form->hidden($nameUnit, $eCrop['sliceUnit']);

				}

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

			$h .= '<div class="slice-item-parts">';
				
				if($onlyPercent === FALSE) {
					
					$eSeries = $eCrop['series'];

					$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::PERCENT ? '' : 'hide').'" data-unit="'.\series\Cultivation::PERCENT.'">';
						$h .= $form->range($name.'[varietyPartPercent][]', 0,  100, 1, $eSlice['partPercent'] ?? 0, ['data-label' => '%']);
					$h .= '</div>';

					if($eSeries['use'] === \series\Series::BLOCK) {
						$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::AREA ? '' : 'hide').'" data-unit="'.\series\Cultivation::AREA.'">';
						if($eSeries['area'] > 0) {
							$h .= $form->range($name.'[varietyPartArea][]', 0,  $eSeries['area'], 1, $eSlice['partArea'] ?? 0, ['data-label' => 'm²']);
						} else {
							$h .= '<div class="color-danger" style="line-height: 1;">'.s("Vous pourrez répartir les variétés au m² lorsque vous aurez fait l'assolement de cette culture !").'</div>';
						}
						$h .= '</div>';
					}
	
					if($eSeries['use'] === \series\Series::BED) {
						$h .= '<div class="slice-item-part '.($eCrop['sliceUnit'] === \series\Cultivation::LENGTH ? '' : 'hide').'" data-unit="'.\series\Cultivation::LENGTH.'">';
						if($eSeries['length'] > 0) {
							$h .= $form->range($name.'[varietyPartLength][]', 0,  $eSeries['length'], 1, $eSlice['partLength'] ?? 0, ['data-label' => 'mL']);
						} else {
							$h .= '<div class="color-danger" style="line-height: 1;">'.s("Vous pourrez répartir les variétés au mètre linéaire lorsque vous aurez assolé cette culture !").'</div>';
						}
						$h .= '</div>';
					}
					
				} else {

					$h .= '<div class="slice-item-part" data-unit="'.\series\Cultivation::PERCENT.'">';
						$h .= $form->range($name.'[varietyPartPercent][]', 0,  100, 1, $eSlice['partPercent'] ?? 0, ['data-label' => '%']);
					$h .= '</div>';

				}

			$h .= '</div>';

			$h .= '<a class="slice-item-remove" data-action="slice-remove">'.\Asset::icon('trash').'</a>';

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
