<?php
namespace selling;

class Invoice extends InvoiceElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customer' => CustomerElement::getSelection(),
			'paymentMethod' => ['name', 'fqn'],
			'farm' => \farm\FarmElement::getSelection(),
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
			$this->acceptUpdate() and
			$this->acceptStatusDelivered() and
			$this['status'] === Invoice::GENERATED and
			$this['emailedAt'] === NULL
		);

	}

	public function acceptRegenerate(): bool {
		return $this->acceptUpdate();
	}

	public function acceptAccounting(): bool {
		return in_array($this['status'], [Invoice::GENERATED, Invoice::DELIVERED]);
	}

	public function acceptDownload(): bool {
		return $this['content']->notEmpty();
	}

	public function acceptUpdate(): bool {
		return in_array($this['status'], [Invoice::DRAFT, Invoice::GENERATED]);
	}

	public function acceptReminder(): bool {

		$days = $this['farm']->getConf('invoiceReminder');

		return (
			in_array($this['status'], [Invoice::GENERATED, Invoice::DELIVERED]) and
			in_array($this['paymentStatus'], [NULL, Invoice::NOT_PAID]) and
			$this['remindedAt'] === NULL and
			$this['dueDate'] !== NULL and
			round((strtotime(currentDate()) - strtotime($this['dueDate'])) / 86400) > $days
		);
	}

	public function acceptDelete(): bool {
		return ($this['status'] === Invoice::DRAFT);
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

	public static function validateBatchIgnore(\Collection $cInvoice): void {

		if($cInvoice->empty()) {

			throw new \FailAction('selling\Invoice::invoices.check');

		} else {

			$eFarm = $cInvoice->first()['farm'];

			foreach($cInvoice as $eInvoice) {

				if($eInvoice['farm']['id'] !== $eFarm['id']) {
					throw new \NotExpectedAction('Different farms');
				}

				$eInvoice->validate('acceptAccountingIgnore');

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

	public function acceptUpdatePayment(): bool {
		return in_array($this['status'], [Invoice::DRAFT, Invoice::GENERATED, Invoice::DELIVERED]);
	}

	public function acceptUpdatePaymentStatus(): bool {

		return (
			$this->acceptUpdatePayment() and
			$this['paymentMethod']->notEmpty()
		);

	}

	public function isCreditNote(): bool {
		return ($this['priceExcludingVat'] < 0.0);
	}

	public function isValid(): bool {
		return in_array($this['status'], [Invoice::GENERATED, Invoice::DELIVERED]);
	}

	public function getInvoice(\farm\Farm $eFarm): ?string {

		$this->expects(['document']);

		if($this->isCreditNote()) {
			$code = $eFarm->getConf('creditPrefix');
		} else {
			$code = $eFarm->getConf('invoicePrefix');
		}

		if($this['document'] === NULL) {
			return NULL;
		} else {
			return \farm\Configuration::getNumber($code, $this['document']);
		}

	}

	public function getTaxes(): string {

		$this->expects(['hasVat', 'taxes']);

		if($this['hasVat']) {
			return SaleUi::p('taxes')->values[$this['taxes']];
		} else {
			return '';
		}

	}

	public function acceptStatusCanceled(): bool {
		return ($this['status'] === Invoice::GENERATED);
	}

	public function acceptStatusConfirmed(): bool {
		return ($this['status'] === Invoice::DRAFT);
	}

	public function acceptStatusDelivered(): bool {
		return ($this['status'] === Invoice::GENERATED);
	}

	//-------- Accounting features -------

	public function acceptAccountingIgnore(): bool {
		return $this['accountingHash'] === NULL;
	}

	public function hasAccountingDifference(): bool {

		$this->expects(['priceIncludingVat', 'cashflow']);

		if($this['cashflow']->notEmpty()) {
			$this['cashflow']->expects(['amount']);
		}

		return ($this['cashflow']->notEmpty() and $this['cashflow']['amount'] !== $this['priceIncludingVat']);

	}


	public function isReadyForAccounting(): bool {

		$this->expects(['status', 'accountingHash', 'paymentMethod', 'accountingDifference', 'priceIncludingVat']);

		if($this['cashflow']->notEmpty()) {
			$this['cashflow']->expects(['amount']);
		}

		return ($this['status'] !== Invoice::DRAFT and
			$this['accountingHash'] === NULL and
			$this['paymentMethod']->notEmpty() and
			(
				$this['cashflow']->empty() or
				$this['cashflow']['amount'] === $this['priceIncludingVat'] or
				$this['accountingDifference'] !== NULL
			)
		);

	}

	public function acceptUpdateAccountingDifference(): bool {

		$this->expects(['accountingHash', 'cashflow', 'priceIncludingVat']);

		return ($this['accountingHash'] === NULL and $this['cashflow']->notEmpty() and $this['cashflow']['amount'] !== $this['priceIncludingVat']);

	}

	public function acceptAccountingImport(): bool {

		$this->expects(['readyForAccounting', 'priceIncludingVat', 'cashflow']);

		if($this['cashflow']->empty()) {
			return FALSE;
		}

		$this['cashflow']->expects(['amount']);

		return (
			$this['accountingHash'] === NULL and
			$this['readyForAccounting'] === TRUE and
			($this['cashflow']->empty() or ($this['cashflow']['amount'] === $this['priceIncludingVat']) or $this['accountingDifference'] !== NULL)
		);
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

					$eMethod = $this['cSale']->first()['cPayment']->first()['method'] ?? new \payment\Method();

					foreach($this['cSale'] as $eSale) {

						if($eMethod->empty()) {

							// Pas de moyen de paiement pour les 2
							if($eSale['cPayment']->count() === 0) {
								continue;
							}

							return FALSE;
						}

						if($eSale['cPayment']->count() !== 1 or $eSale['cPayment']->first()['method']->is($eMethod) === FALSE) {
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
			->setCallback('date.future', function(string $date): bool {

				return ($date <= currentDate());

			})
			->setCallback('date.past', function(string $date): bool {

				$this->expects(['farm']);

				$this['lastDate'] = \selling\InvoiceLib::getLastDate($this['farm']);

				return ($this['lastDate'] === NULL or $date >= $this['lastDate']);

			})
			->setCallback('dueDate.check', function(?string $dueDate): bool {
				return ($dueDate !== NULL);
			})
			->setCallback('dueDate.consistency', function(?string $dueDate) use ($p): bool {

				if(
					$dueDate === NULL or
					$p->isInvalid('date')
				) {
					return TRUE;
				}

				$this->expects(['date']);

				return ($dueDate >= $this['date']);

			})
			->setCallback('paymentMethod.check', function(\payment\Method $eMethod): bool {

				if($eMethod->empty()) {
					return TRUE;
				}

				$this->expects(['farm']);

				return \payment\MethodLib::isSelectable($this['farm'], $eMethod);

			})
			->setCallback('status.check', function(string &$status) use($p): bool {

				return in_array($status, [Invoice::DRAFT, Invoice::CONFIRMED]);

			})
			->setCallback('paymentStatus.check', function(string &$status) use($p): bool {

				$this->expects(['paymentMethod']);

				if($this['paymentMethod']->empty()) {
					$status = NULL;
					return TRUE;
				} else {
					return in_array($status, [Invoice::PAID, Invoice::NOT_PAID]);
				}

			})
			->setCallback('paidAt.prepare', function(?string &$paidAt) use($p): bool {

				$this->expects(['paymentStatus']);

				if($this['paymentStatus'] !== Invoice::PAID) {
					$paidAt = NULL;
				}

				return TRUE;

			})
			->setCallback('paidAt.future', function(?string &$paidAt) use($p): bool {

				return (
					$paidAt === NULL or
					$paidAt <= currentDate()
				);

			});
		
		parent::build($properties, $input, $p);

	}

}
?>
