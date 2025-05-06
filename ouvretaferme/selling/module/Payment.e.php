<?php
namespace selling;

class Payment extends PaymentElement {


	public function isPaymentOnline(): bool {

		$this->expects(['method', 'checkoutId']);

		return (($this['method']['fqn'] ?? NULL) === \payment\MethodLib::ONLINE_CARD);

	}

}
?>
