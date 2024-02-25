<?php
namespace media;

class ToolVignetteUi extends MediaUi {

	protected bool $crop = FALSE;

	protected function getCameraContent(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {
		return \farm\ToolUi::getVignette($eElement, $width, $height);
	}

}
?>
