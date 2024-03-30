<?php
namespace selling;

class Configuration extends ConfigurationElement {

	public function isComplete(): bool {

		return (
			$this['legalName'] !== NULL and
			$this['legalEmail'] !== NULL and
			$this['invoiceCity'] !== NULL
		);

	}

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

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

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'defaultVat.check' => function(int $vat): bool {
				return array_key_exists($vat, SaleLib::getVatRates($this['farm']));
			},

			'defaultVatShipping.check' => function(?int $vat): bool {
				return (
					$vat === NULL or
					array_key_exists($vat, SaleLib::getVatRates($this['farm']))
				);
			},

		]);

	}

}
?>