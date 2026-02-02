<?php
namespace cash;

class Cash extends CashElement {

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('date.future', function(string $date) {

				return ($date <= currentDate());

			});

		parent::build($properties, $input, $p);

	}

}
?>