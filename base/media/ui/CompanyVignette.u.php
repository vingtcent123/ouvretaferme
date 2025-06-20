<?php
namespace media;

class CompanyVignetteUi extends MediaUi {

	protected function getCameraContent(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {
		return \company\CompanyUi::getVignette($eElement, $size);
	}

}
?>
