<?php
namespace series;

class Timesheet extends TimesheetElement {

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm', 'user']);

		return (
			$this['farm']->canManage() or
			(
				$this['farm']->canWork() and
				$this['user']->isOnline()
			)
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('date.check', function(string $date) {
				return \util\DateLib::compare($date, currentDate()) <= 0;
			});

		parent::build($properties, $input, $p);

	}

}
?>