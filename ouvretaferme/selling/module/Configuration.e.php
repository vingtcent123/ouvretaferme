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

			'documentInvoices.prepare' => function(string &$value): bool {
				$value--;
				return TRUE;
			},

			'documentInvoices.consistency' => function(string &$value): bool {

				$this->expects(['invoicePrefix']);

				$max = Invoice::model()
					->whereFarm($this['farm'])
					->whereName('LIKE', $this['invoicePrefix'].'%')
					->getValue(new \Sql('MAX(document)'));

				$this['invoicePrefixMin'] = $max - 80;

				return ($value > $this['invoicePrefixMin']);

			},

			'creditPrefix.prepare' => function(string &$prefix): bool {
				$prefix = strtoupper($prefix);
				return TRUE;
			},

			'creditPrefix.fqn' => function(string $prefix): bool {
				return preg_match('/^[a-z0-9\-\_]*[a-z\-\_]$/si', rtrim($prefix, '#')) > 0;
			},

			'invoicePrefix.prepare' => function(string &$prefix): bool {
				$prefix = strtoupper($prefix);
				return TRUE;
			},

			'invoicePrefix.fqn' => function(string $prefix): bool {
				return preg_match('/^[a-z0-9\-\_]*[a-z\-\_]$/si', rtrim($prefix, '#')) > 0;
			},

			'deliveryNotePrefix.prepare' => function(string &$prefix): bool {
				$prefix = strtoupper($prefix);
				return TRUE;
			},

			'deliveryNotePrefix.fqn' => function(string $prefix): bool {
				return preg_match('/^[a-z0-9\-\_]*[a-z\-\_]$/si', $prefix) > 0;
			},

			'orderFormPrefix.prepare' => function(string &$prefix): bool {
				$prefix = strtoupper($prefix);
				return TRUE;
			},

			'orderFormPrefix.fqn' => function(string $prefix): bool {
				return preg_match('/^[a-z0-9\-\_]*[a-z\-\_]$/si', $prefix) > 0;
			},

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