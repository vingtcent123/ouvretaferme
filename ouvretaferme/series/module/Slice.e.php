<?php
namespace series;

class Slice extends SliceElement {

	/**
	 * Remplit le Slice en fonction des données de base de son Cultivation pour qu'il prennent l'intégralité de la surface de l'assolement
	 */
	public function fillFromCultivation(Cultivation $eCultivation): void {

		$eCultivation->expects(['sliceUnit']);

		$this['partPercent'] = NULL;
		$this['partArea'] = NULL;
		$this['partLength'] = NULL;

		switch($eCultivation['sliceUnit']) {

			case Cultivation::PERCENT :
				$this['partPercent'] = 100;
				break;

			case Cultivation::AREA :
				$this['partArea'] = $eCultivation['area'];
				break;

			case Cultivation::LENGTH :
				$this['partLength'] = $eCultivation['length'];
				break;

		}

	}

	public function formatPart(): string {

		if($this['partPercent'] !== NULL) {
			return s("{partPercent} %", $this);
		} else if($this['partArea'] !== NULL) {
			return s("{partArea} m²", $this);
		} else if($this['partLength'] !== NULL) {
			return s("{partLength} mL", $this);
		}

	}

}
?>
