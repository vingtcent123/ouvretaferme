<?php
namespace media;

class UserVignetteUi extends MediaUi {

	protected function getCameraCss(): string {
		return 'border-radius: 50%;';
	}

	protected function getCameraContent(\Element $eElement, int|string|null $size = NULL, int|string|null $width = NULL, int|string|null $height = NULL): string {
		return \user\UserUi::getVignette($eElement, $size);
	}

}
?>
