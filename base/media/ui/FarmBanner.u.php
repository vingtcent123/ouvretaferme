<?php
namespace media;

class FarmBannerUi extends MediaUi {

	protected bool $crop = FALSE;

	protected function getCameraContent(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {
		return \farm\FarmUi::getBanner($eElement, $width);
	}

}
?>
