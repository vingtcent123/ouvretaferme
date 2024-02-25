<?php
namespace map;

class SeasonUi {

	public function getDescriberField(\util\FormUi $form, \Element $e, \farm\Farm $eFarm, ?Zone $eZone, ?Plot $ePlot, string $property, string $placeholder) {

		if($ePlot !== NULL) {
			$ePlot->expects(['seasonFirst', 'seasonLast']);
		}

		if($eZone !== NULL) {
			$eZone->expects(['seasonFirst', 'seasonLast']);
		}

		$eFarm->expects(['seasonFirst', 'seasonLast']);

		$attributes = [
			'placeholder' => $placeholder
		];

		if($property === 'seasonLast') {
			$attributes['default'] = NULL;
		} else {
			$attributes['default'] = date('Y');
		}

		$values = [];

		$first = NULL;
		$last = NULL;
		
		if($ePlot !== NULL) {
			$first ??= $ePlot['seasonFirst'];
			$last ??= $ePlot['seasonLast'];
		}
		
		if($eZone !== NULL) {
			$first ??= $eZone['seasonFirst'];
			$last ??= $eZone['seasonLast'];
		}

		if($eFarm !== NULL) {
			$first ??= $eFarm['seasonFirst'];
			$last ??= $eFarm['seasonLast'];
		}

		for($season = $first; $season <= $last; $season++) {
			$values[$season] = ($property === 'seasonFirst') ? s("la saison {value}", $season) : s("la fin de saison {value}", $season);
		}

		return $form->select($property, $values, $e[$property] ?? NULL, $attributes);

	}

	public function getField(\util\FormUi $form, \Element $e) {

		return $form->inputGroup(
			$form->addon(s("Depuis")).
			$form->dynamicField($e, 'seasonFirst').
			$form->addon(s("jusqu'à")).
			$form->dynamicField($e, 'seasonLast')
		);

	}

	public static function getInterval(\Element $e): string {

		$e->expects(['seasonFirst', 'seasonLast']);

		if($e['seasonFirst'] !== NULL and $e['seasonLast'] !== NULL) {
			if($e['seasonFirst'] === $e['seasonLast']) {
				return $e['seasonLast'];
			} else {
				return $e['seasonFirst'].' - '.$e['seasonLast'];
			}
		} else if($e['seasonFirst'] !== NULL) {
			return s("Depuis {value}", $e['seasonFirst']);
		} else if($e['seasonLast'] !== NULL) {
			return s("Jusqu'en {value}", $e['seasonLast']);
		} else {
			return '';
		}

	}

}
?>
