<?php
namespace game;

class GrowingUi {

	public static function getVignette(Growing $e, string $size): string {

		return \plant\PlantUi::getVignette(new \plant\Plant([
			'fqn' => $e['fqn']
		]), $size);

	}

}
?>
