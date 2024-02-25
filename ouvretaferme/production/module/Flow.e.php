<?php
namespace production;

class Flow extends FlowElement {

	public static function getSelection(): array {

		return [
			'action' => ['fqn', 'name', 'color', 'series'],
		] + parent::getSelection();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public static function validateBatch(\Collection $cFlow): void {

		if($cFlow->empty()) {
			throw new \FailAction('series\Flow::flows.check');
		} else {

			$eSequence = $cFlow->first()['sequence'];

			foreach($cFlow as $eFlow) {

				$eFlow->validate('canWrite');

				if($eFlow['sequence']['id'] !== $eSequence['id']) {
					throw new \NotExpectedAction('Different sequences');
				}

			}
		}

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$prepareYear = function(?int &$year): bool {

			$this->expects([
				'sequence' => ['cycle']
			]);

			if($this['sequence']['cycle'] === Sequence::PERENNIAL) {
				$year = NULL;
			} else {

				if($year < -1 or $year > 1) {
					$year = NULL;
				}

			}

			return TRUE;

		};

		$prepareSeason = function(?int &$season): bool {

			$this->expects([
				'sequence' => ['cycle', 'perennialLifetime']
			]);

			if($this['sequence']['cycle'] === Sequence::ANNUAL) {
				$season = NULL;
			} else {

				if(
					$season < 0 and
					$season >= $this['sequence']['perennialLifetime']
				) {
					$season = NULL;
				}

			}

			return TRUE;

		};

		$fw = new \FailWatch();

		$result = parent::build($properties, $input, $callbacks + [

			'action.check' => function(\farm\Action $eAction): bool {

				$this->expects([
					'sequence' => ['farm']
				]);

				return \farm\ActionLib::canUse($eAction, $this['sequence']['farm']);

			},

			'crop.check' => function(Crop $eCrop) use ($fw): bool {

				$this->expects([
					'sequence' => ['cCrop']
				]);

				// Action saisie, on vérifie qu'en cas de récolte on ait bien un crop
				if($fw->has('Flow::action.check') === FALSE) {

					$this->expects([
						'action' => ['fqn']
					]);

					if(
						$eCrop->empty() and
						$this['action']['fqn'] === ACTION_RECOLTE
					) {
						\Fail::log('Flow::action.check');
					}

				}

				return (
					$eCrop->empty() or
					Crop::model()
						->select([
								'id', 'plant'
						])
						->whereSequence($this['sequence'])
						->get($eCrop)
				);

			},

			'fertilizer.check' => function(?array &$fertilizer) use ($fw): bool {

				if($fw->has('Flow::action.check')) { // L'action génère déjà une erreur
					return TRUE;
				}

				$this->expects([
					'action' => ['fqn'],
				]);

				if($this['action']['fqn'] !== ACTION_FERTILISATION) {
					$fertilizer = NULL;
					return TRUE;
				}


				if($fertilizer !== NULL) {
					$fertilizer = \farm\RoutineLib::checkFertilizer($fertilizer);
				}

				return TRUE;

			},

			'weekOnly.argument' => fn($property) => $property.'Week',
			'weekStart.argument' => fn($property) => $property.'Week',
			'weekStop.argument' => fn($property) => $property.'Week',

			'yearOnly.prepare' => $prepareYear,
			'yearStart.prepare' => $prepareYear,
			'yearStop.prepare' => $prepareYear,

			'seasonOnly.prepare' => $prepareSeason,
			'seasonStart.prepare' => $prepareSeason,
			'seasonStop.prepare' => $prepareSeason,

			'toolsList.check' => function(mixed $tools): bool {

				try {
					$this->expects(['action']);
				} catch(\Exception) {
					return FALSE;
				}

				if($this['action']->empty()) {
					$this['cTool'] = new \Collection();
					return TRUE;
				}

				$tools = (array)($tools ?? []);

				$cTool = \farm\Tool::model()
					->select('id', 'farm')
					->whereId('IN', $tools)
					->where('action IS NULL or action = '.$this['action']['id'])
					->getCollection()
					->filter(fn($eTool) => $eTool->canRead());

				$this['cTool'] = $cTool;

				return TRUE;

			},

		]);

		$this->buildSeason($properties, $input);
		$this->buildPeriod($properties, $input);

		return $result;

	}

	protected function buildPeriod(array $properties, array $input): void {

		$count = count(array_intersect($properties, ['weekOnly', 'yearOnly', 'weekStart', 'yearStart', 'weekStop', 'yearStop', 'frequency']));

		if($count === 0) {
			return;
		} else if($count < 7) {
			throw new \Exception('Inseparable properties');
		}

		switch($input['period'] ?? NULL) {

			case 'only' :
				$this->buildPeriodOnly();
				break;

			case 'interval' :
				$this->buildPeriodInterval();
				break;

			default :
				Flow::fail('weekOnly.empty');


		}

	}

	protected function buildPeriodOnly() {

		if($this['weekOnly'] === NULL) {
			Flow::fail('weekOnly.empty');
			return;
		}

		$this['weekStart'] = NULL;
		$this['yearStart'] = NULL;

		$this['weekStop'] = NULL;
		$this['yearStop'] = NULL;

		$this['frequency'] = NULL;

		if($this['sequence']['cycle'] === Sequence::PERENNIAL) {

			$this['yearOnly'] = NULL;

		} else {

			if($this['yearOnly'] === NULL) {
				Flow::fail('yearOnly.empty');
				return;
			}

			switch($this['yearOnly']) {

				case -1 :
					if($this['weekOnly'] < \Setting::get('minWeekN-1')) {
						Flow::fail('weekOnly.consistency0');
					}
					break;

				case 1 :
					if($this['weekOnly'] > \Setting::get('maxWeekN+1')) {
						Flow::fail('weekOnly.consistency2');
					}
					break;

			}

		}

	}

	protected function buildPeriodInterval() {

		if($this['weekStart'] === NULL) {
			Flow::fail('weekStart.empty');
			return;
		}

		if($this['weekStop'] === NULL) {
			Flow::fail('weekStop.empty');
			return;
		}

		$this['weekOnly'] = NULL;
		$this['yearOnly'] = NULL;

		if($this['sequence']['cycle'] === Sequence::PERENNIAL) {

			$this['yearStart'] = NULL;
			$this['yearStop'] = NULL;

			if($this['weekStart'] >= $this['weekStop']) {
				Flow::fail('weekStop.consistency');
			}

		} else {

			if($this['yearStart'] === NULL) {
				Flow::fail('yearStart.empty');
				return;
			}

			if($this['yearStop'] === NULL) {
				Flow::fail('yearStop.empty');
				return;
			}

			if(
				$this['yearStart'] > $this['yearStop'] or
				($this['yearStart'] === $this['yearStop'] and $this['weekStart'] >= $this['weekStop'])
			) {
				Flow::fail('weekStop.consistency');
			}

			foreach(['Start', 'Stop'] as $key) {

				switch($this['year'.$key]) {

					case -1 :
						if($this['week'.$key] < \Setting::get('minWeekN-1')) {
							Flow::fail('week'.$key.'.consistency0');
						}
						break;

					case 1 :
						if($this['week'.$key] > \Setting::get('maxWeekN+1')) {
							Flow::fail('week'.$key.'.consistency2');
						}
						break;


				}

			}

		}

		if($this['frequency'] === NULL) {
			Flow::fail('frequency.empty');
		}

	}

	protected function buildSeason(array $properties, array $input): void {

		$this->expects([
			'sequence' => ['cycle']
		]);

		switch($this['sequence']['cycle']) {

			case Sequence::ANNUAL :
				$this['seasonOnly'] = NULL;
				$this['seasonStart'] = NULL;
				$this['seasonStop'] = NULL;
				break;

			case Sequence::PERENNIAL :

				$count = count(array_intersect($properties, ['seasonOnly', 'seasonStart', 'seasonStop']));

				if($count === 0) {
					return;
				} else if($count < 3) {
					throw new \Exception('Inseparable properties');
				}

				switch($input['season'] ?? NULL) {

					case 'only' :
						$this->buildSeasonOnly();
						break;

					case 'interval' :
						$this->buildSeasonInterval();
						break;

					default :
						Flow::fail('seasonOnly.empty');


				}

				break;

		}

	}

	protected function buildSeasonOnly() {

		if($this['seasonOnly'] === NULL) {
			Flow::fail('seasonOnly.empty');
			return;
		}

		$this['seasonStart'] = NULL;
		$this['seasonStop'] = NULL;

	}

	protected function buildSeasonInterval() {

		if($this['seasonStart'] === NULL) {
			Flow::fail('seasonStart.empty');
			return;
		}

		$this['seasonOnly'] = NULL;

		if($this['seasonStop'] !== NULL and $this['seasonStart'] >= $this['seasonStop']) {
			Flow::fail('seasonStop.consistency');
		}

	}

}
?>