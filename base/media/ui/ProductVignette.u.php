<?php
namespace media;

use selling\ProductUi;

class ProductVignetteUi extends MediaUi {

	protected function getCameraCss(): string {
		return 'border-radius: 50%;';
	}

	protected function getCameraContent(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {
		if($eElement['vignette'] !== NULL) {
			return \selling\ProductUi::getVignette($eElement, $size);
		} else {

			if($eElement['composition']) {
				return ProductUi::getVignetteComposition();
			} else {
				return '';
			}

		}
	}

}
?>
