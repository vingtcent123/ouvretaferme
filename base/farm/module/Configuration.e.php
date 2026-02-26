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

		if(array_intersect(['electronicScheme', 'electronicAddress'], $properties)) {
			$properties[] = 'fullElectronicAddress';
		}

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
			->setCallback('vatNumber.check', fn(?string &$vat) => \farm\Farm::checkVatNumber('farm\Configuration', $this['farm'], $vat, POST('isFromInvoicing', 'bool', FALSE) === FALSE))
			->setCallback('defaultVat.check', function(int $vat): bool {
				return array_key_exists($vat, \selling\SellingSetting::getVatRates($this['farm']));
			})
			->setCallback('defaultVatShipping.check', function(?int $vat): bool {
				return (
					$vat === NULL or
					array_key_exists($vat, \selling\SellingSetting::getVatRates($this['farm']))
				);
			})
			->setCallback('vatFrequency.check', function(?string $vatFrequency) use($p): bool {

				if($p->isBuilt('hasVatAccounting') === FALSE or $this['hasVatAccounting'] === FALSE) {
					return TRUE;
				}

				return in_array($vatFrequency, Configuration::model()->getPropertyEnum('vatFrequency'));
			})
			->setCallback('vatChargeability.check', function(?string $vatChargeability) use($p): bool {

				if($p->isBuilt('hasVatAccounting') === FALSE or $this['hasVatAccounting'] === FALSE) {
					return TRUE;
				}

				return in_array($vatChargeability, Configuration::model()->getPropertyEnum('vatChargeability'));
			})
			->setCallback('invoiceCollection.check', function(?string $invoiceCollection) use($p) {

				if($p->isBuilt('invoiceMandatoryTexts') === FALSE or $this['invoiceMandatoryTexts'] === FALSE) {
					return TRUE;
				}
				return $invoiceCollection !== NULL;
			})
			->setCallback('invoiceLateFees.check', function(?string $invoiceLateFees) use($p) {

				if($p->isBuilt('invoiceMandatoryTexts') === FALSE or $this['invoiceMandatoryTexts'] === FALSE) {
					return TRUE;
				}

				return $invoiceLateFees !== NULL;
			})
			->setCallback('invoiceDiscount.check', function(?string $invoiceDiscount) use($p) {

				if($p->isBuilt('invoiceMandatoryTexts') === FALSE or $this['invoiceMandatoryTexts'] === FALSE) {
					return TRUE;
				}

				return $invoiceDiscount !== NULL;
			})
			->setCallback('electronicScheme.check', function(?string $electronicScheme) use ($p): bool {

				if(\pdp\PdpLib::isActive($this['farm']) === FALSE) {
					return TRUE;
				}

				if($electronicScheme === NULL) {
					return (POST('isFromInvoicing', 'bool', FALSE) === FALSE);
				}

				$this['farm']->expects('legalCountry');

				return \pdp\Address::checkScheme($electronicScheme, $this['farm']['legalCountry']);

			})
			->setCallback('electronicAddress.check', function(?string $electronicAddress) use ($p): bool {

				if(\pdp\PdpLib::isActive($this['farm']) === FALSE or $p->isBuilt('electronicScheme') === FALSE) {
					return TRUE;
				}

				if($electronicAddress === NULL) {
					return ($this['electronicScheme'] === NULL or $this['farm']['siret'] === NULL);
				}

				if($this['farm']['siret'] === NULL) {
					return FALSE;
				}
				return \pdp\Address::checkElectronicAddress($electronicAddress, $this['farm']['siret']);

			})
			->setCallback('fullElectronicAddress.check', function() use($p) {

				if(
					$p->isBuilt('electronicScheme') === FALSE and
					$p->isBuilt(('electronicAddress')) === FALSE and
					POST('isFromInvoicing', 'bool', FALSE) === FALSE
				) {
					return TRUE;
				}

				return $p->isBuilt('electronicScheme') and $p->isBuilt(('electronicAddress')) ;

			})
		;
	
		parent::build($properties, $input, $p);

	}

}
?>
