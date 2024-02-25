<?php
namespace media;

class ProductVignetteUi extends MediaUi {

	protected function getCameraContent(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {
		if($eElement['vignette'] !== NULL) {
			return \selling\ProductUi::getVignette($eElement, $size);
		} else {
			return '';
		}
	}

}
?>
