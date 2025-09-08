<?php
namespace selling;

class Payment extends PaymentElement {

	public static function getSelection(): array {
		return parent::getSelection() + [
			'method' => \payment\Method::getSelection(),
		];
	}

	public function isNotPaid(): bool {

		$this->expects(['method' => ['online'], 'onlineStatus']);

		return $this['method']['online'] === TRUE and $this['onlineStatus'] !== Payment::SUCCESS;

	}

	public function isPaid(): bool {

		$this->expects(['method' => ['online'], 'onlineStatus']);

		return $this['method']['online'] === FALSE or $this['onlineStatus'] === Payment::SUCCESS;

	}
}
?>
