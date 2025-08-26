<?php
namespace media;

class WebpageBannerUi extends MediaUi {

	protected function getCameraContent(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {
		return \website\WebpageUi::getBanner($eElement, $width);
	}

}
?>
