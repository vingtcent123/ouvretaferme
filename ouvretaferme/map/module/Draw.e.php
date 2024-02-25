<?php
namespace map;

class Draw extends DrawElement {


	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'season.check' => function(int $season): bool {
				$this->expects(['farm']);
				return $this['farm']->checkSeason($season);
			},

			'coordinates.check' => function(array &$coords): bool {

				if(count($coords) !== 3) {
					return FALSE;
				}

				foreach($coords as $key => $point) {

					if(
						count($point) !== 2 or
						array_key_exists(0, $point) === FALSE or
						array_key_exists(1, $point) === FALSE or
						is_numeric($point[0]) === FALSE or
						is_numeric($point[1]) === FALSE
					) {
						return FALSE;
					}

					$coords[$key][0] = (float)$point[0];
					$coords[$key][1] = (float)$point[1];

					if(
						$point[0] < -180 or $point[0] > 180 or
						$point[1] < -180 or $point[1] > 180
					) {
						return FALSE;
					}

				}

				return TRUE;

			}

		]);

	}

}
?>