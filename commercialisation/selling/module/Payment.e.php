<?php
namespace selling;

class Payment extends PaymentElement {

	public static function getSelection(): array {
		return parent::getSelection() + [
			'method' => \payment\Method::getSelection(),
		];
	}

	// On considère qu'un paiement par CB peut déterminer si payé ou non
	// Si le mode de paiement est un autre, on peut considérer que le paiement est OK
	public function isNotPaid(): bool {

		$this->expects(['method' => ['online'], 'onlineStatus']);

		return $this['method']->isOnline() and $this['onlineStatus'] !== Payment::SUCCESS;

	}

	public function isPaid(): bool {

		$this->expects(['method' => ['online'], 'onlineStatus']);

		return $this['method']->isOnline() === FALSE or $this['onlineStatus'] === Payment::SUCCESS;

	}

}
?>
