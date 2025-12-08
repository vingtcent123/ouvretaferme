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
			->setCallback('taxCountry.check', function($eCountry): bool {

				if($this['taxCountryVerified']) {
					return FALSE;
				}

				return \user\Country::model()->exists($eCountry);

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
