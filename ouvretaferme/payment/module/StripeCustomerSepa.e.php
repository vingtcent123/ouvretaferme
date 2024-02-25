<?php
namespace payment;

class StripeCustomerSepa extends StripeCustomerSepaElement {

	public function hasValid(): bool {
		return $this->notEmpty() and $this['status'] == \payment\StripeCustomerSepa::VALID;
	}

}
?>