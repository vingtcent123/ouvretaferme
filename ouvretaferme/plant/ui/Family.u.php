<?php
namespace plant;

/**
 * Ui class for families
 *
 */
class FamilyUi {

	/**
	 * Get the URL of a family
	 *
	 */
	public static function link(Family $eFamily, \farm\Farm $eFarm): string {
		return '<a href="'.self::url($eFamily, $eFarm).'">'.encode($eFamily['name']).'</a>';
	}


	/**
	 * Get the URL of a family
	 *
	 */
	public static function url(Family $eFamily, \farm\Farm $eFarm): string {

		$eFamily->expects(['fqn']);

		if($eFarm->empty()) {
			return '/famille/'.$eFamily['fqn'];
		} else {
			return '/ferme/'.$eFarm['id'].'/famille/'.$eFamily['id'];
		}

	}

	public static function getLetterVignette(Family $eFamily, \farm\Farm $eFarm): string {

		\Asset::css('plant', 'family.css');

		if($eFamily->empty()) {

			return '<div title="'.s("Inconnue").'" class="family-vignette-letter">?</div>';

		} else {

			$eFamily->expects(['name', 'color']);

			return '<a href="'.self::url($eFamily, $eFarm).'" title="'.encode($eFamily['name']).'" class="family-vignette-letter" style="background-color: '.$eFamily['color'].'">'.mb_substr($eFamily['name'], 0, 2).'</a>';

		}


	}

	public function display(Family $eFamily, \Collection $cPlant): \Panel {

		\Asset::css('plant', 'family.css');
		\Asset::css('media', 'media.css');

		$h = '<h3>'.s("Les espèces de cette famille").'</h3>';

		if($cPlant->notEmpty()) {

			$h .= '<div class="plant-item-grid">';
				$h .= (new \plant\PlantUi())->getList($cPlant);
			$h .= '</div>';

		} else {
			$h .= '<div class="util-info">'.s("Aucune espèce n'a été renseignée pour cette famille.").'</div>';
		}

		return new \Panel(
			title: encode($eFamily['name']),
			body: $h
		);

	}

	/**
	 * Describe properties
	 */
	public static function p(string $property): \PropertyDescriber {

		$d = Family::model()->describer($property, [
			'name' => s("Nom"),
			'fqn' => s("Nom qualifié"),
			'color' => s("Couleur"),
		]);

		return $d;

	}

}
?>
