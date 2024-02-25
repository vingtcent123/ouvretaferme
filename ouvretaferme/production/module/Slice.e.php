<?php
namespace production;

class Slice extends SliceElement {
	
	public function fillFromCrop(Crop $eCrop): void {
		$this['partPercent'] = 100;
	}

	public function formatPart(): string {
		return s("{partPercent} %", $this);
	}

}
?>
