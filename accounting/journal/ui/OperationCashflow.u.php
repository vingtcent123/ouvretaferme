<?php
namespace journal;

Class OperationCashflowUi {

	public static function p(string $property): \PropertyDescriber {

		$d = Operation::model()->describer($property, [
			'operation' => s("Écriture comptable"),
			'cashflow' => s("Opération bancaire")
		]);

		switch($property) {

			case 'operation':
				$d->autocompleteBody = function() {
					return [
					];
				};
				new OperationUi()->query($d, GET('farm', 'farm\Farm'), new \bank\Cashflow());
				break;


		}

		return $d;

	}
}
