<?php
namespace selling;

class Configuration extends ConfigurationElement {

	public function isLegal(): bool {

		return (
			$this['legalName'] !== NULL and
			$this['legalEmail'] !== NULL
		);

	}

	public function isComplete(): bool {

		return (
			$this->isLegal() and
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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('legalName.empty', function(?string $value): bool {
				return ($value !== NULL);
			})
			->setCallback('legalEmail.empty', function(?string $value): bool {
				return ($value !== NULL);
			})
			->setCallback('documentInvoices.set', function(int &$value): void {
				$this['documentInvoices'] = $value - 1;
			})
			->setCallback('documentInvoices.consistency', function(int &$value): bool {

				$this->expects(['invoicePrefix']);

				$max = Invoice::model()
					->whereFarm($this['farm'])
					->whereName('LIKE', $this['invoicePrefix'].'%')
					->getValue(new \Sql('MAX(document)'));

				$this['invoicePrefixMin'] = $max - 80;

				return ($value > $this['invoicePrefixMin']);

			})
			->setCallback('creditPrefix.prepare', function(string &$prefix): bool {
				$prefix = strtoupper($prefix);
				return TRUE;
			})
			->setCallback('creditPrefix.fqn', function(string $prefix): bool {
				return preg_match('/^[a-z0-9\-\_]*[a-z\-\_]$/si', rtrim($prefix, '#')) > 0;
			})
			->setCallback('invoicePrefix.prepare', function(string &$prefix): bool {
				$prefix = strtoupper($prefix);
				return TRUE;
			})
			->setCallback('invoicePrefix.fqn', function(string $prefix): bool {
				return preg_match('/^[a-z0-9\-\_]*[a-z\-\_]$/si', rtrim($prefix, '#')) > 0;
			})
			->setCallback('deliveryNotePrefix.prepare', function(string &$prefix): bool {
				$prefix = strtoupper($prefix);
				return TRUE;
			})
			->setCallback('deliveryNotePrefix.fqn', function(string $prefix): bool {
				return preg_match('/^[a-z0-9\-\_]*[a-z\-\_]$/si', rtrim($prefix, '#')) > 0;
			})
			->setCallback('orderFormPrefix.prepare', function(string &$prefix): bool {
				$prefix = strtoupper($prefix);
				return TRUE;
			})
			->setCallback('orderFormPrefix.fqn', function(string $prefix): bool {
				return preg_match('/^[a-z0-9\-\_]*[a-z\-\_]$/si', rtrim($prefix, '#')) > 0;
			})
			->setCallback('defaultVat.check', function(int $vat): bool {
				return array_key_exists($vat, SaleLib::getVatRates($this['farm']));
			})
			->setCallback('defaultVatShipping.check', function(?int $vat): bool {
				return (
					$vat === NULL or
					array_key_exists($vat, SaleLib::getVatRates($this['farm']))
				);
			});
	
		parent::build($properties, $input, $p);

	}

}
?>