<?php
namespace media;

class WebsiteBannerUi extends MediaUi {

	protected function getCameraContent(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {
		return \website\WebsiteUi::getBanner($eElement, $width);
	}

}
?>
