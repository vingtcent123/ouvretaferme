<?php
namespace cash;

class Cash extends CashElement {

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('date.future', function(string $date) {

				return ($date <= currentDate());

			})
			->setCallback('amountIncludingVat.check', function(?float $amount) {

				return ($amount !== NULL);

			});

		parent::build($properties, $input, $p);

	}

}
?>