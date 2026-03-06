<?php
namespace cash;

class Archive extends ArchiveElement {

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('to.consistency', function(string $to): bool {

				$this->expects([
					'from'
				]);

				return ($to >= $this['from']);

			})
			->setCallback('to.interval', function(string $to) use ($p): bool {

				if($p->isInvalid('to') or $p->isInvalid('from')) {
					return TRUE;
				}

				$date1 = new \DateTime($this['from']);
				$date2 = new \DateTime($to);

				$diff = $date1->diff($date2);

				return $diff->days <= 366;

			});

		parent::build($properties, $input, $p);

	}

}
?>