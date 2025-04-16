<?php
namespace selling;

class Invoice extends InvoiceElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customer' => CustomerElement::getSelection(),
			'expiresAt' => new \Sql('IF(content IS NULL, NULL, createdAt + INTERVAL '.\Setting::get('selling\documentExpires').' MONTH)')
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {
		$this->expects(['farm']);
		return $this['farm']->canManage();
	}

	public function canRemote(): bool {
		return $this->canRead() or GET('key') === \Setting::get('selling\remoteKey');
	}

	public function acceptSend(): bool {

		$this->expects(['emailedAt', 'createdAt', 'generation']);

		return (
			$this['generation'] === Invoice::SUCCESS and
			$this['emailedAt'] === NULL
		);

	}

	public function acceptRegenerate(): bool {
		return in_array($this['generation'], [Invoice::FAIL, Invoice::SUCCESS]);
	}

	public static function validateBatch(\Collection $cInvoice): void {

		if($cInvoice->empty()) {
			throw new \FailAction('selling\Invoice::invoices.check');
		} else {

			$eFarm = $cInvoice->first()['farm'];

			foreach($cInvoice as $eInvoice) {

				if($eInvoice['farm']['id'] !== $eFarm['id']) {
					throw new \NotExpectedAction('Different farms');
				}

			}
		}

	}

	public function isCreditNote(): bool {
		return ($this['priceExcludingVat'] < 0.0);
	}

	public function getInvoice(\farm\Farm $eFarm): string {

		$this->expects(['document']);

		if($this->isCreditNote()) {
			$code = $eFarm->getSelling('creditPrefix');
		} else {
			$code = $eFarm->getSelling('invoicePrefix');
		}

		return Configuration::getNumber($code, $this['document']);

	}

	public function getTaxes(): string {

		$this->expects(['hasVat', 'taxes']);

		if($this['hasVat']) {
			return SaleUi::p('taxes')->values[$this['taxes']];
		} else {
			return '';
		}

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('sales.prepare', function(array &$sales): bool {

				$this->expects(['customer']);

				$this['cSale'] = SaleLib::getForInvoice($this['customer'], $sales);

				if($this['cSale']->empty()) {
					return FALSE;
				}

				return TRUE;

			})
			->setCallback('sales.check', function(array &$sales): bool {

				return ($this['cSale']->count() === count($sales));

			})
			->setCallback('sales.taxes', function(): bool {

				$this->expects(['cSale']);

				$taxes = $this['cSale']->getColumn('taxes');

				return (count(array_unique($taxes)) === 1);

			})
			->setCallback('sales.hasVat', function(): bool {

				$this->expects(['cSale']);

				$hasVat = $this['cSale']->getColumn('hasVat');

				return (count(array_unique($hasVat)) === 1);

			});
		
		parent::build($properties, $input, $p);

	}

}
?>