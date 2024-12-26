<?php
namespace series;

class Slice extends SliceElement {

	public function formatPart(Cultivation $eCultivation): string {

		if($this['partPercent'] !== NULL) {
			return s("{partPercent} %", $this);
		} else if($this['partArea'] !== NULL) {
			return s("{partArea} m²", $this);
		} else if($this['partLength'] !== NULL) {
			return s("{partLength} mL", $this);
		} else if($this['partPlant'] !== NULL) {
			return match($eCultivation['seedling']) {
				\series\Cultivation::SOWING => s("{partPlant} graines", $this),
				default => s("{partPlant} plants", $this),
			};
		} else if($this['partTray'] !== NULL) {
			return s("{value} x {tray}", ['value' => $this['partTray'], 'tray' =>  encode($eCultivation['sliceTool']['name'])]);
		}

	}

}
?>
