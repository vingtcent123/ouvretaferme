<?php
namespace selling;

class Payment extends PaymentElement {

	public static function getSelection(): array {
		return parent::getSelection() + [
			'method' => \payment\Method::getSelection(),
		];
	}

	public function isNotPaid(): bool {

		return $this['method']['online'] === TRUE and $this['onlineStatus'] !== Payment::SUCCESS;

	}
}
?>
