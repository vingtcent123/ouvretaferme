<?php
namespace sequence;

class Slice extends SliceElement {

	public function formatPart(Crop $eCrop): string {
		return s("{partPercent} %", $this);
	}

}
?>
