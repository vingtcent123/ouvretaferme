<?php
namespace production;

class Slice extends SliceElement {

	public function formatPart(): string {
		return s("{partPercent} %", $this);
	}

}
?>
