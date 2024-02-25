<?php
namespace map;

class Bed extends BedElement {

	public static function getSelection(): array {
		return parent::getSelection() + [
			'zone' => ['seasonFirst', 'seasonLast', 'name'],
			'plot' => ['seasonFirst', 'seasonLast', 'name', 'zoneFill', 'zone', 'mode'],
			'farm' => ['seasonFirst', 'seasonLast', 'defaultBedLength', 'defaultBedWidth', 'defaultAlleyWidth'],
		];
	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canWrite();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function getGreenhouseIcon(): string {

		$this->expects(['greenhouse']);

		return (
			($this['greenhouse']->empty() or $this['name'] === NULL) ?
				'' :
				'<span title="'.encode($this['greenhouse']['name']).'">'.\Asset::icon('greenhouse').'</span>'
		);

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'name.empty' => function(?string $name): bool {
				return ($name !== NULL);
			},

			'name.duplicate' => function(string $name): bool {

				$this->expects(['plot']);

				if($this->offsetExists('id')) {
					Bed::model()->whereId('!=', $this);
				}

				return Bed::model()
					->wherePlot($this['plot'])
					->whereName($name)
					->exists() === FALSE;

			},

			'zone.check' => function(Zone $eZone): bool {

				return (
					Zone::model()
						->select('farm')
						->get($eZone) and
					$eZone['farm']->canWrite()
				);

			},

			'plot.check' => function(Plot $ePlot): bool {

				$this->expects(['zone']);

				$this['oldPlot'] = $this['plot'];

				return (
					Plot::model()
						->select([
							'id', 'seasonFirst', 'seasonLast',
							'zone', 'zoneFill', 'mode'
						])
						->whereZone($this['zone'])
						->get($ePlot)
				);

			},

			'length.check' => function(?float $length): bool {

				return (
					$length !== NULL and
					Bed::model()->check('length', $length)
				);

			},

			'width.check' => function(?float $width): bool {

				return (
					$width !== NULL and
					Bed::model()->check('width', $width)
				);

			},

			'greenhouse.check' => function(Greenhouse $eGreenhouse): bool {

				$this->expects([
					'plot' => ['zone', 'zoneFill']
				]);

				if($eGreenhouse->empty()) {
					return TRUE;
				}

				if($this['plot']['zoneFill']) {
					Greenhouse::model()->whereZone($this['plot']['zone']);
				} else {
					Greenhouse::model()->wherePlot($this['plot']);
				}


				return Greenhouse::model()
						->exists($eGreenhouse);

			},

			'seasonFirst.check' => function(?int $season): bool {
				$this->expects([
					'plot' => ['seasonFirst', 'seasonLast']
				]);
				return ($season === NULL or $this['plot']['seasonFirst'] === NULL or $season >= $this['plot']['seasonFirst']);
			},
			'seasonLast.check' => function(?int $season): bool {
				$this->expects([
					'plot' => ['seasonFirst', 'seasonLast']
				]);
				return ($season === NULL or $this['plot']['seasonLast'] === NULL or $season <= $this['plot']['seasonLast']);
			},
			'seasonLast.consistency' => function(?int $season): bool {

				return (
					$this['seasonFirst'] === NULL or
					$season === NULL or
					$this['seasonFirst'] <= $season
				);
			}

		]);

	}

}
?>