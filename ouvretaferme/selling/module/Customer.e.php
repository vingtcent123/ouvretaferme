<?php
namespace selling;

class Customer extends CustomerElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'vignette', 'url', 'featureDocument'],
			'user' => ['email']
		];

	}

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canGrid(): bool {

		$this->expects(['type']);

		return (
			$this->canManage() and
			$this['type'] === Customer::PRO
		);

	}

	public function canManage(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function canDelete(): bool {
		return $this->canManage();
	}

	public function isPro(): bool {
		$this->expects(['type']);
		return $this['type'] === Customer::PRO;
	}

	public function isPrivate(): bool {
		$this->expects(['type']);
		return $this['type'] === Customer::PRIVATE;
	}

	public function getInvoiceAddress(): ?string {

		if($this->hasInvoiceAddress() === FALSE) {
			return NULL;
		}

		$address = $this['invoiceStreet1']."\n";
		if($this['invoiceStreet2'] !== NULL) {
			$address .= $this['invoiceStreet2']."\n";
		}
		$address .= $this['invoicePostcode'].' '.$this['invoiceCity'];

		return $address;

	}

	public function hasInvoiceAddress(): bool {
		return ($this['invoiceCity'] !== NULL);
	}

	public function getEmailOptIn() {

		$this->expects(['emailOptIn']);

		if($this['emailOptIn'] === NULL) {
			return '<span class="color-muted">'.\Asset::icon('question-circle').' '.s("Pas de consentement explicite du client").'</span>';
		} else if($this['emailOptIn'] === FALSE) {
			return '<span class="color-danger">'.\Asset::icon('x-circle').' '.s("Refus du client").'</span>';
		} else {
			return '<span class="color-success">'.\Asset::icon('check-circle').' '.s("Accord du client").'</span>';
		}

	}

	public function getOptInHash() {
		return hash('sha256', random_bytes(1024));
	}

}
?>