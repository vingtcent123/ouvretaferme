<?php
namespace media;

class UserVignetteLib extends MediaLib {

	public function buildElement(): \Element {
		return \user\ConnectionLib::getOnline();
	}

}
?>
