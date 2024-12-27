<?php
namespace production;

class Crop extends CropElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'plant' => ['name', 'fqn', 'vignette'],
			'cSlice' => SliceLib::delegateByCrop(),
		];

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function format(string $property, array $options = []): ?string {

		switch($property) {

			case 'yieldExpected' :
				return \production\CropUi::getYield($this, 'yieldExpected', 'mainUnit', $options);

			default :
				return parent::format($property, $options);

		}

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$this->expects([
			'sequence' => ['use']
		]);

		$spacing = function(?int $value): bool {

			$this->expects(['distance']);

			if($this['distance'] !== Crop::SPACING) {
				$value = NULL;
			}

			return TRUE;

		};

		return parent::build($properties, $input, $callbacks + [

			'plant.check' => function(\plant\Plant $ePlant): bool {

				return (
					$ePlant->empty() === FALSE and
					\plant\Plant::model()
						->select('farm')
						->get($ePlant) and
					$ePlant->canRead()
				);

			},

			'seedlingSeeds.prepare' => function(?int &$seeds): bool {

				$this->expects(['seedling']);

				if($this['seedling'] !== Crop::YOUNG_PLANT) {
					$seeds = NULL;
				} else {
					$seeds = (int)$seeds;
				}

				return TRUE;

			},

			'density.prepare' => function(?float &$density): bool {

				$this->expects(['distance']);

				if($this['distance'] !== Crop::DENSITY) {
					$density = NULL;
				}

				return TRUE;

			},

			'rowSpacing.prepare' => $spacing,
			'rows.prepare' => $spacing,
			'plantSpacing.prepare' => $spacing,

			'rowSpacing.check' => function(?int &$rowSpacing): bool {

				switch($this['sequence']['use']) {

					case Sequence::BED :
						$this['rowSpacing'] = NULL;
						return TRUE;

					case Sequence::BLOCK :
						return Crop::model()->check('rowSpacing', $rowSpacing);

				}

			},

			'rows.check' => function(?int &$rows): bool {

				switch($this['sequence']['use']) {

					case Sequence::BED :
						return Crop::model()->check('rows', $rows);

					case Sequence::BLOCK :
						$this['rows'] = NULL;
						return TRUE;

				}

			},

			'variety.check' => function(?array $varieties, array $newProperties, array $validProperties, string $wrapper) {

				$this['cSlice'] = SliceLib::prepare($this, $varieties, $wrapper);

				return TRUE;

			},

		]);

	}

}
?>