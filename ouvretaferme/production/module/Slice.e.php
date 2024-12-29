<?php
namespace production;

class Slice extends SliceElement {

	public function formatPart(Crop $eCrop): string {
		return s("{partPercent} %", $this);
	}

}
?>
