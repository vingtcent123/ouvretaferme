<?php
namespace preaccounting;

Class SuggestionUi {

	public function __construct() {
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Suggestion::model()->describer($property, [
			'paymentMethod' => s("Moyen de paiement"),
		]);

		return $d;

	}
}
