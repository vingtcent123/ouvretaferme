<?php
namespace farm;

class Configuration extends ConfigurationElement {

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public static function getNumber(string $code, int $document): string {

		$prefix = rtrim($code, '#');
		$zero = strlen($code) - strlen($prefix);

		return $prefix.($zero > 1 ? sprintf('%0'.$zero.'d', $document) : $document);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('documentInvoices.set', function(int &$value): void {
				$this['documentInvoices'] = $value - 1;
			})
			->setCallback('documentInvoices.consistency', function(int &$value): bool {

				$this->expects(['invoicePrefix']);

				$max = \selling\Invoice::model()
					->whereFarm($this['farm'])
					->whereNumber('LIKE', $this['invoicePrefix'].'%')
					->getValue(new \Sql('MAX(document)'));

				$this['invoicePrefixMin'] = $max - 80;

				return ($value > $this['invoicePrefixMin']);

			})
			->setCallback('invoiceDueDays.prepare', function(?int &$days) use ($p): bool {
				if($this['invoiceDue'] === FALSE) {
					$days = NULL;
				}
				return TRUE;
			})
			->setCallback('invoiceDueMonth.prepare', function(?bool &$month): bool {
				if($this['invoiceDue'] === FALSE) {
					$month = NULL;
				} else {
					$month ??= FALSE;
				}
				return TRUE;
			})
			->setCallback('invoiceDueMonth.consistency', function(?bool &$month) use ($p): bool {

				if(
					$this['invoiceDue'] === FALSE or
					$p->isBuilt('invoiceDueDays') === FALSE
				) {
					return TRUE;
				}

				return (
					$this['invoiceDueDays'] !== NULL or
					$month !== FALSE
				);

			})
			->setCallback('invoicePrefix.prepare', function(string &$prefix): bool {
				$prefix = strtoupper($prefix);
				return TRUE;
			})
			->setCallback('invoicePrefix.fqn', function(string $prefix): bool {
				return preg_match('/^[a-z0-9\-\_]*[a-z\-\_]$/si', rtrim($prefix, '#')) > 0;
			})
			->setCallback('vatNumber.check', fn(?string &$vat) => \farm\Farm::checkVatNumber('farm\Configuration', $this['farm'], $vat))
			->setCallback('defaultVat.check', function(int $vat): bool {
				return array_key_exists($vat, \selling\SellingSetting::getVatRates($this['farm']));
			})
			->setCallback('defaultVatShipping.check', function(?int $vat): bool {
				return (
					$vat === NULL or
					array_key_exists($vat, \selling\SellingSetting::getVatRates($this['farm']))
				);
			});
	
		parent::build($properties, $input, $p);

	}

}
?>
