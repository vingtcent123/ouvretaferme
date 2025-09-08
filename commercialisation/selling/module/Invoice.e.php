<?php
namespace selling;

class Invoice extends InvoiceElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customer' => CustomerElement::getSelection(),
			'paymentMethod' => ['name', 'fqn'],
			'expiresAt' => new \Sql('IF(content IS NULL, NULL, createdAt + INTERVAL '.SellingSetting::DOCUMENT_EXPIRES.' MONTH)'),
			'farm' => ['id', 'name', 'url', 'siret', 'legalName', 'legalEmail', 'legalStreet1', 'legalStreet2', 'legalPostcode', 'legalCity', 'vignette'],
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canPublicRead(): bool {

		$this->expects([
			'farm',
			'customer' => ['user']
		]);

		// Producteur
		if($this->canRead()) {
			return TRUE;
		}

		// Client
		return \user\ConnectionLib::getOnline()->is($this['customer']['user']);

	}

	public function canWrite(): bool {
		$this->expects(['farm']);
		return $this['farm']->canManage();
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

	public function isPaymentOnline(): bool {

		if($this['paymentMethod']->empty()) {
			return FALSE;
		}

		$this->expects(['paymentMethod' => ['fqn']]);

		return ($this['paymentMethod']['fqn'] === \payment\MethodLib::ONLINE_CARD);

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
			->setCallback('sales.paid', function(): bool {

				$this->expects(['cSale']);

				if($this['cSale']->count() < 2) {
					return TRUE;
				} else {
					return $this['cSale']->contains(fn($eSale) => $eSale['paymentStatus'] === Sale::PAID) === FALSE;
				}

			})
			->setCallback('sales.methods', function(): bool {

				$this->expects(['cSale']);

				if($this['cSale']->count() < 2) {
					return TRUE;
				} else {

					$eMethod = $this['cSale']->first()['paymentMethod'];

					foreach($this['cSale'] as $eSale) {
						if($eSale['cPayment']->count() > 1 OR $eSale['cPayment']->first()['method']->is($eMethod) === FALSE) {
							return FALSE;
						}
					}

					return TRUE;
				}

			})
			->setCallback('sales.hasVat', function(): bool {

				$this->expects(['cSale']);

				$hasVat = $this['cSale']->getColumn('hasVat');

				return (count(array_unique($hasVat)) === 1);

			})
			->setCallback('paymentMethod.check', function(\payment\Method $eMethod): bool {

				if($eMethod->empty()) {
					return TRUE;
				}

				$this->expects(['farm']);

				return \payment\MethodLib::isSelectable($this['farm'], $eMethod);

			})
			->setCallback('paymentStatus.check', function(string &$status) use($p): bool {

				$this->expects(['paymentMethod']);

				if($this['paymentMethod']->empty()) {
					$status = Invoice::NOT_PAID;
					return TRUE;
				} else {
					return in_array($status, [Invoice::PAID, Invoice::NOT_PAID]);
				}

			});
		
		parent::build($properties, $input, $p);

	}

}
?>
