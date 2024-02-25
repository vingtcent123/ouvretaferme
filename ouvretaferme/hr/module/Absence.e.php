<?php
namespace hr;

class Absence extends AbsenceElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name'],
			'user' => [
				'firstName', 'lastName'
			],
		];

	}

	public static function isDateAbsent(\Collection $cAbsence, string $date): Absence {

		foreach($cAbsence as $eAbsence) {

			if($date >= substr($eAbsence['from'], 0, 10) and $date <= substr($eAbsence['to'], 0, 10)) {
				return $eAbsence;
			}

		}

		return new Absence();

	}

	public static function isWeekAbsent(\Collection $cAbsence, string|array $weeks): Absence {

		$intervals = [];

		foreach((array)$weeks as $week) {
			$intervals[] = [week_date_starts($week), week_date_ends($week)];
		}

		foreach($cAbsence as $eAbsence) {

			foreach($intervals as [$start, $stop]) {
				if($stop >= substr($eAbsence['from'], 0, 10) and $start <= substr($eAbsence['to'], 0, 10)) {
					return $eAbsence;
				}
			}

		}

		return new Absence();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'to.consistency' => function(string $to): bool {

				$this->expects([
					'from'
				]);

				return ($to > $this['from']);

			},

		]);

	}

}
?>