<?php
namespace hr;

class Presence extends PresenceElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'user' => [
				'vignette',
				'firstName', 'lastName',
				'status'
			],
		];

	}

	public static function isDatePresent(\Collection $cPresence, string $date): Presence {

		foreach($cPresence as $ePresence) {

			if($ePresence['to'] === NULL) {
				if($date >= $ePresence['from']) {
					return $ePresence;
				}
			} else {
				if($date >= $ePresence['from'] and $date <= $ePresence['to']) {
					return $ePresence;
				}
			}

		}

		return new Presence();

	}

	public static function isWeekPresent(\Collection $cPresence, string|array $weeks): Presence {

		$intervals = [];

		foreach((array)$weeks as $week) {
			$intervals[] = [week_date_starts($week), week_date_ends($week)];
		}

		foreach($cPresence as $ePresence) {

			if($ePresence['to'] === NULL) {
				foreach($intervals as [, $stop]) {
					if($stop >= $ePresence['from']) {
						return $ePresence;
					}
				}
			} else {
				foreach($intervals as [$start, $stop]) {
					if($stop >= $ePresence['from'] and $start <= $ePresence['to']) {
						return $ePresence;
					}
				}
			}

		}

		return new Presence();

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('to.consistency', function(?string $to) use($p): bool {

				if($p->isBuilt('from') === FALSE) {
					return TRUE;
				}

				$this->expects([
					'from'
				]);

				return (
					$to === NULL or
					$to >= $this['from']
				);

			})
			->setCallback('to.present', function(?string $to) use($p): bool {

				if($p->isBuilt('from') === FALSE) {
					return TRUE;
				}

				$this->expects([
					'farm', 'user',
					'from'
				]);

				if($p->for === 'update') {
					Presence::model()->whereId('!=', $this['id']);
				}

				return PresenceLib::isPresentBetween($this['farm'], $this['user'], $this['from'], $to) === FALSE;

			});

		parent::build($properties, $input, $p);

	}

}
?>