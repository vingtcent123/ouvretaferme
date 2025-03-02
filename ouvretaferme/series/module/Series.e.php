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

	public function acceptStatus(): bool {

		return $this->isAnnual();

	}

	public function acceptOpen(): bool {

		return $this->isClosed();

	}

	public function acceptClose(): bool {

		return (
			$this->acceptStatus() and
			$this->isOpen()
		);

	}

	public function acceptDuplicate(): bool {
		return $this->isAnnual();
	}

	public function acceptSeason(): bool {
		return $this->isAnnual();
	}

	public static function validateBatch(\Collection $cSeries, \farm\Farm $eFarm = new \farm\Farm()): void {

		if($cSeries->empty()) {
			throw new \FailAction('series\Series::series.check');
		} else {

			if($eFarm->empty()) {
				$eFarm = $cSeries->first()['farm'];
			}

			$season = $cSeries->first()['season'];

			foreach($cSeries as $eSeries) {

				$eSeries->validateProperty('farm', $eFarm);
				$eSeries->validateProperty('season', $season);

			}
		}

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('farm.check', function(\farm\Farm $eFarm): bool {

				return (
					$eFarm->empty() === FALSE and
					$eFarm->canManage()
				);

			})
			->setCallback('season.check', function(int $season): bool {

				$this->expects(['farm']);

				\farm\Farm::model()
					->select('seasonFirst', 'seasonLast')
					->get($this['farm']);


				return $this['farm']->checkSeason($season);

			})
			->setCallback('status.check', function(): bool {

				return $this->acceptStatus();

			})
			->setCallback('use.prepare', function() use ($p): bool {

				if($p->for === 'update') {
					$this->expects(['use']);
					$this['oldUse'] = $this['use'];
				}

				return TRUE;

			})
			->setCallback('cycle.prepare', function(?string &$cycle) use ($p): bool {

				$p->expectsBuilt('sequence');

				if($this['sequence']->notEmpty()) {
					$cycle = $this['sequence']['cycle'];
				}

				return TRUE;

			})
			->setCallback('sequence.check', function(\production\Sequence $eSequence): bool {

				if($eSequence->empty()) {
					return TRUE;
				}

				if(\production\Sequence::model()
						->select('id', 'farm', 'cycle', 'name')
						->get($eSequence) === FALSE) {
					return FALSE;
				}

				return $eSequence['farm']->canWrite();

			})
			->setCallback('bedWidth.prepare', function(?int &$bedWidth): bool {

				$this->expects(['use']);

				if($this['use'] === Series::BLOCK) {
					$bedWidth = NULL;
				}

				if($this['use'] === Series::BED and $bedWidth === NULL) {
					return FALSE;
				}

				return TRUE;

			})
			->setCallback('alleyWidth.prepare', function(?int &$alleyWidth): bool {

				$this->expects(['use']);

				if($this['use'] === Series::BLOCK) {
					$alleyWidth = NULL;
				}

				return TRUE;

			})
			->setCallback('perennialLifetime.prepare', function(?int &$lifetime): bool {

				$this->expects(['id', 'cycle']);

				if($this['cycle'] === Series::ANNUAL) {
					$lifetime = NULL;
				}

				return TRUE;

			})
			->setCallback('perennialLifetime.last', function(?int $lifetime): bool {

				$this->expects(['id', 'cycle']);

				if($this['id'] !== NULL and $this['cycle'] === Series::PERENNIAL) {
					// Il n'est pas possible de modifier la durée de vie d'une série qui est continuée dans le futur
					return ($this['perennialStatus'] !== Series::CONTINUED);
				} else {
					return TRUE;
				}

			})
			->setCallback('perennialLifetime.consistency', function(?int $lifetime): bool {

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

			})
			->setCallback('taskInterval.check', function(mixed $value): bool {

				$value = (int)$value;

				['min' => $min, 'max' => $max] = \Setting::get('series\duplicateInterval');

				if($value > $min and $value < $max) {
					$this['taskInterval'] = $value;
					return TRUE;
				} else {
					return FALSE;
				}

			});
		
		parent::build($properties, $input, $p);

	}

}
?>
