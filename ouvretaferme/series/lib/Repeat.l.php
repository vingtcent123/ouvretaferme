<?php
namespace series;

class RepeatLib extends RepeatCrud {

	public static function getTaskProperties(): array {
		return [
			'farm', 'season', 'cultivation', 'series', 'plant', 'variety', 'action', 'category', 'description', 'timeExpected', 'fertilizer',
			'status'
		];
	}

	public static function createForSeries(Repeat $eRepeat): void {

		$eRepeat->expects([
			'start', 'current',
			'series' => ['season'],
			'action' => ['fqn']
		]);

		$week = toWeek(strtotime($eRepeat['current'] ?? $eRepeat['start']));

		do {

			self::createForElement($eRepeat, $week);

			$week = toWeek(strtotime($week.' + 1 WEEK'));

		} while($eRepeat['completed'] === FALSE);

	}

	public static function createForYear(\farm\Farm $eFarm, string $year): void {

		$cRepeat = Repeat::model()
			->select(Repeat::getSelection())
			->whereFarm($eFarm)
			->whereCompleted(FALSE)
			->where(new \Sql('
				IF(
					frequency = \''.Repeat::M1.'\',
					current + INTERVAL 1 MONTH,
					IF(
						frequency = \''.Repeat::W1.'\',
						current + INTERVAL 1 WEEK,
						IF(
							frequency = \''.Repeat::W2.'\',
							current + INTERVAL 2 WEEK,
						   IF(
								frequency = \''.Repeat::W3.'\',
								current + INTERVAL 3 WEEK,
							   current + INTERVAL 4 WEEK
							)
						)
					)
				)'), '<', $year.'-12-31')
			->or(
				fn() => $this->whereStop(NULL),
				fn() => $this->whereStop('>=', $year.'-W01')
			)
			->getCollection();

		for($weekNumber = 1, $weeks = getWeeksInYear($year); $weekNumber <= $weeks; $weekNumber++) {

			$week = $year.'-W'.sprintf('%02d', $weekNumber);

			$cRepeatWeek = $cRepeat->find(function(Repeat $eRepeat) use ($week) {

				return (
					$eRepeat['stop'] === NULL or
					$eRepeat['stop'] >= $week
				);

			}, clone: FALSE);

			\series\RepeatLib::createForCollection($cRepeatWeek, $week);

		}

	}

	public static function createForWeek(\farm\Farm $eFarm, string $week): void {

		$cRepeat = Repeat::model()
			->select(Repeat::getSelection())
			->whereFarm($eFarm)
			->whereCompleted(FALSE)
			->where(new \Sql('
				IF(
					frequency = \''.Repeat::M1.'\',
					current + INTERVAL 1 MONTH,
					IF(
						frequency = \''.Repeat::W1.'\',
						current + INTERVAL 1 WEEK,
						IF(
							frequency = \''.Repeat::W2.'\',
							current + INTERVAL 2 WEEK,
						   IF(
								frequency = \''.Repeat::W3.'\',
								current + INTERVAL 3 WEEK,
							   current + INTERVAL 4 WEEK
							)
						)
					)
				)'), '<', week_date_ends($week))
			->or(
				fn() => $this->whereStop(NULL),
				fn() => $this->whereStop('>=', $week)
			)
			->getCollection();

		self::createForCollection($cRepeat, $week);

	}

	public static function createForCollection(\Collection $cRepeat, string $week): void {

		foreach($cRepeat as $eRepeat) {
			self::createForElement($eRepeat, $week);
		}

	}

	public static function createForElement(Repeat $eRepeat, string $week): void {

		if(
			$eRepeat['completed'] or
			in_array($week, $eRepeat['discrete'])
		) {
			return;
		}

		if($eRepeat['frequency'] === Repeat::M1) {

			$currentDay = (int)substr($eRepeat['start'], 8, 2);
			$newDayStart = (int)substr(week_date_starts($week), 8, 2);
			$newDayStop = (int)substr(week_date_ends($week), 8, 2);

			if(
				($currentDay >= $newDayStart and $currentDay <= $newDayStop) or
				($currentDay >= $newDayStart and $newDayStart > $newDayStop)
			) {
				$has = TRUE;
				// Jour exact
				$date = substr(week_date_starts($week), 0, 8).substr($eRepeat['start'], 8, 2);
			} else if($currentDay <= $newDayStop and $newDayStart > $newDayStop) {
				$has = TRUE;
				// Jour exact
				$date = substr(week_date_ends($week), 0, 8).substr($eRepeat['start'], 8, 2);
			} else {
				$has = FALSE;
			}

		} else {

			// Arbitrairement, à une fréquence hebdomadaire, le jour exact est le 3e jour de la semaine
			$date = week_date_day($week, 3);

			$deltaWeek = (int)round((strtotime($date) - strtotime(toWeek($eRepeat['start']))) / 86400 / 7, 2);

			$has = match($eRepeat['frequency']) {
				Repeat::W1 => TRUE,
				Repeat::W2 => ($deltaWeek % 2 === 0),
				Repeat::W3 => ($deltaWeek % 3 === 0),
				Repeat::W4 => ($deltaWeek % 4 === 0)
			};

		}

		// Création de la tâche
		if($has) {

			// On doit vérifier si la date réelle est toujours valide car la sélection initiale récupère en fonction du dernier jour de la semaine
			if(
				$eRepeat['current'] !== NULL and
				$date <= $eRepeat['current']
			) {
				return;
			}

			TaskLib::createFromRepeat($eRepeat, $week);

			// Ajout à la suite discrète
			$eRepeat['discrete'][] = $week;
			sort($eRepeat['discrete']);

			foreach($eRepeat['discrete'] as $key => $discreteWeek) {

				$nextTimestamp = self::addFrequency($eRepeat, $eRepeat['current'] ?? $eRepeat['start']);
				$nextDate = date('Y-m-d', $nextTimestamp);
				$nextWeek = toWeek($nextTimestamp);

				if($discreteWeek === $nextWeek) {
					$eRepeat['current'] = $nextDate;
					unset($eRepeat['discrete'][$key]);
				}

			}

			sort($eRepeat['discrete']);

			self::calculateCompleted($eRepeat);

			Repeat::model()
				->select('current', 'discrete', 'completed')
				->update($eRepeat);

		}

	}

	public static function createFromTask(Task $eTask): Repeat {

		$eTask->expects([
			'repeatMaster' => ['frequency', 'stop']
		]);

		// Le démarrage est fixé au 3e jour de la semaine
		$start = match($eTask['status']) {
			Task::TODO => $eTask['plannedDate'] ?? week_date_day($eTask['plannedWeek'], 3),
			Task::DONE => $eTask['doneDate'] ?? week_date_day($eTask['doneWeek'], 3),
		};

		$eRepeat = $eTask['repeatMaster']
			->merge([
				'frequency' => $eTask['repeatMaster']['frequency'],
				'start' => $start,
				'current' => $start,
				'stop' => $eTask['repeatMaster']['stop']
			])
			->merge($eTask->extracts(self::getTaskProperties()));

		$eRepeat['tools'] = $eTask['cTool']->getColumn('id');

		self::calculateCompleted($eRepeat);

		Repeat::model()->insert($eRepeat);

		$eTask['repeat'] = $eRepeat;

		return $eRepeat;

	}

	public static function update(Repeat $e, array $properties = []): void {

		if(in_array('stop', $properties)) {

			self::calculateCompleted($e);
			$properties[] = 'completed';

		}

		parent::update($e, $properties);

	}

	public static function calculateCompleted(Repeat $e): void {

		$e['completed'] = (
			$e['stop'] !== NULL and
			$e['current'] !== NULL and
			$e['stop'] < toWeek(self::addFrequency($e, $e['current']))
		);

	}

	private static function addFrequency(Repeat $e, string $date): int {
		return strtotime($date.' + '.self::getInterval($e));
	}

	private static function getInterval(Repeat $e): string {

		return match($e['frequency']) {
			Repeat::W1 => '7 DAY',
			Repeat::W2 => '14 DAY',
			Repeat::W3 => '21 DAY',
			Repeat::W4 => '28 DAY',
			Repeat::M1 => '1 MONTH'
		};

	}

}
?>
