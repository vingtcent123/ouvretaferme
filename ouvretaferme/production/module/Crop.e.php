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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

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
		
		$p
			->setCallback('plant.check', function(\plant\Plant $ePlant): bool {

				return (
					$ePlant->empty() === FALSE and
					\plant\Plant::model()
						->select('farm')
						->get($ePlant) and
					$ePlant->canRead()
				);

			})
			->setCallback('seedlingSeeds.prepare', function(?int &$seeds): bool {

				$this->expects(['seedling']);

				if(in_array($this['seedling'], [Crop::SOWING, Crop::YOUNG_PLANT]) === FALSE) {
					$seeds = NULL;
				} else {
					$seeds = (int)$seeds;
				}

				return TRUE;

			})
			->setCallback('density.prepare', function(?float &$density): bool {

				$this->expects(['distance']);

				if($this['distance'] !== Crop::DENSITY) {
					$density = NULL;
				}

				return TRUE;

			})
			->setCallback('rowSpacing.prepare', $spacing)
			->setCallback('rows.prepare', $spacing)
			->setCallback('plantSpacing.prepare', $spacing)
			->setCallback('rowSpacing.check', function(?int &$rowSpacing): bool {

				switch($this['sequence']['use']) {

					case Sequence::BED :
						$this['rowSpacing'] = NULL;
						return TRUE;

					case Sequence::BLOCK :
						return Crop::model()->check('rowSpacing', $rowSpacing);

				}

			})
			->setCallback('rows.check', function(?int &$rows): bool {

				switch($this['sequence']['use']) {

					case Sequence::BED :
						return Crop::model()->check('rows', $rows);

					case Sequence::BLOCK :
						$this['rows'] = NULL;
						return TRUE;

				}

			})
			->setCallback('variety.check', function(?array $varieties, \Properties $p, string $wrapper) {

				if($p->isBuilt('plant') === FALSE) {
					return TRUE;
				}

				$this['cSlice'] = SliceLib::prepare($this, $varieties, $wrapper);

				return TRUE;

			})
			->setCallback('actions.set', function(?array $actions): bool {

				$this->expects(['seedling']);

				$this['actions'] = [];

				$check = function($action) use($actions) {

					$year = (int)($actions[$action]['year'] ?? 0);
					$week = ($actions[$action]['week'] ?? NULL);

					if(
						\Filter::check('week', $week) and
						in_array($year, [-1, 0, 1])
					) {
						$this['actions'][$action] = [week_number($week), $year];
					}

				};

				switch($this['seedling']) {

					case Crop::SOWING :
						$check->call($this, ACTION_SEMIS_DIRECT);
						break;

					case Crop::YOUNG_PLANT :
						$check->call($this, ACTION_SEMIS_PEPINIERE);
						$check->call($this, ACTION_PLANTATION);
						break;

				}

				return TRUE;

			});

		parent::build($properties, $input, $p);

	}

}
?>