<?php
namespace series;

class Series extends SeriesElement {

	public function canRead(): bool {
		$this->expects(['farm']);
		return $this['farm']->canWrite();
	}

	public function canWrite(): bool {
		$this->expects(['farm']);
		return $this['farm']->canManage();
	}

	public function isTargeted(): bool {

		$this->expects(['use', 'length', 'lengthTarget', 'area', 'areaTarget']);

		switch($this['use']) {

			case Series::BED :
				return ($this['length'] === NULL and $this['lengthTarget'] !== NULL);

			case Series::BLOCK :
				return ($this['area'] === NULL and $this['areaTarget'] !== NULL);

		}

	}

	public function isAnnual(): bool {

		$this->expects(['cycle']);

		return ($this['cycle'] === Series::ANNUAL);

	}

	public function isPerennial(): bool {

		$this->expects(['cycle']);

		return ($this['cycle'] === Series::PERENNIAL);

	}

	public function isOpen(): bool {

		$this->expects(['status']);

		return ($this['status'] === Series::OPEN);

	}

	public function isClosed(): bool {

		$this->expects(['status']);

		return ($this['status'] === Series::CLOSED);

	}

	public static function validateBatch(\Collection $cSeries, \farm\Farm $eFarm): void {

		if($cSeries->empty()) {
			throw new \FailAction('series\Series::series.check');
		} else {

			$season = $cSeries->first()['season'];

			foreach($cSeries as $eSeries) {

				$eSeries->validateProperty('farm', $eFarm);

				if($eSeries['season'] !== $season) {
					throw new \NotExpectedAction('Different seasons');
				}

			}
		}

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'farm.check' => function(\farm\Farm $eFarm): bool {

				return (
					$eFarm->empty() === FALSE and
					$eFarm->canManage()
				);

			},

			'season.check' => function(int $season): bool {
				$this->expects(['farm']);
				return $this['farm']->checkSeason($season);
			},

			'use.prepare' => function() use ($for): bool {

				if($for === 'update') {
					$this->expects(['use']);
					$this['oldUse'] = $this['use'];
				}

				return TRUE;

			},

			'sequence.check' => function(\production\Sequence $eSequence): bool {

				if($eSequence->empty()) {
					return TRUE;
				}

				if(\production\Sequence::model()
						->select('id', 'farm', 'cycle', 'name')
						->get($eSequence) === FALSE) {
					return FALSE;
				}

				return $eSequence['farm']->canWrite();

			},

			'bedWidth.prepare' => function(?int &$bedWidth): bool {

				$this->expects(['use']);

				if($this['use'] === Series::BLOCK) {
					$bedWidth = NULL;
				}

				if($this['use'] === Series::BED and $bedWidth === NULL) {
					return FALSE;
				}

				return TRUE;

			},

			'alleyWidth.prepare' => function(?int &$alleyWidth): bool {

				$this->expects(['use']);

				if($this['use'] === Series::BLOCK) {
					$alleyWidth = NULL;
				}

				return TRUE;

			},

			'perennialLifetime.prepare' => function(?int &$lifetime): bool {

				$this->expects(['id', 'cycle']);

				if($this['cycle'] === Series::ANNUAL) {
					$lifetime = NULL;
				}

				return TRUE;

			},

			'perennialLifetime.last' => function(?int $lifetime): bool {

				$this->expects(['id', 'cycle']);

				if($this['id'] !== NULL and $this['cycle'] === Series::PERENNIAL) {
					// Il n'est pas possible de modifier la durée de vie d'une série qui est continuée dans le futur
					return ($this['perennialStatus'] !== Series::CONTINUED);
				} else {
					return TRUE;
				}

			},

			'perennialLifetime.consistency' => function(?int $lifetime): bool {

				$this->expects(['id', 'cycle']);

				if($this['id'] !== NULL and $this['cycle'] === Series::PERENNIAL) {

					$this->expects(['perennialSeason']);

					if($lifetime !== NULL) {
						return ($lifetime >= $this['perennialSeason']);
					} else {
						return TRUE;
					}

				} else {
					return TRUE;
				}

			},

		]);

	}

}
?>
