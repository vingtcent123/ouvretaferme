<?php
namespace selling;

class Invoice extends InvoiceElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'customer' => CustomerElement::getSelection(),
			'farm' => \farm\FarmElement::getSelection(),
			'cPayment' => PaymentTransactionLib::delegateByInvoice(),
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

	public function acceptDownload(): bool {
		return $this['content']->notEmpty();
	}

	public function acceptUpdate(): bool {
		return (
			$this['closed'] === FALSE and
			in_array($this['status'], [Invoice::DRAFT, Invoice::GENERATED])
		);
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


	public function isPaymentOnline(?string $status = Payment::PAID): bool {

		$this->expects(['cPayment']);
		return PaymentLib::isOnline($this['cPayment'], $status);

	}

	public function acceptUpdatePayment(): bool {
		return (
			in_array($this['status'], [Invoice::DRAFT, Invoice::GENERATED, Invoice::DELIVERED])
		);
	}

	public function acceptReplacePayment(): bool {

		return (
			$this->acceptUpdatePayment() and
			$this['paymentStatus'] !== Sale::PAID and
			$this['paymentStatus'] !== Sale::PARTIAL_PAID
		);

	}

	public function acceptNeverPaid(): bool {

		return (
			$this->acceptReplacePayment() and
			$this['paymentStatus'] !== Sale::NEVER_PAID
		);

	}

	public function acceptPayPayment(): bool {

		return (
			$this->acceptUpdatePayment() and
			$this['paymentStatus'] === Sale::NOT_PAID
		);

	}

	public function isCreditNote(): bool {
		return ($this['priceExcludingVat'] < 0.0);
	}

	public function isValid(): bool {
		return in_array($this['status'], [Invoice::GENERATED, Invoice::DELIVERED]);
	}

	public function calculateNumber(\farm\Farm $eFarm): ?string {

		$this->expects(['document']);

		if($this['document'] === NULL) {
			return NULL;
		} else {
			$code = $eFarm->getConf('invoicePrefix');
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
		return ($this['closed'] === FALSE);
	}

	public function acceptStatusConfirmed(): bool {
		return ($this['status'] === Invoice::DRAFT);
	}

	public function acceptStatusDelivered(): bool {
		return ($this['status'] === Invoice::GENERATED);
	}

	public function hasAllAccounts(): bool {

		$this->expects(['cSale']);

		// Vérifie si tous les items ont un numéro de compte
		foreach($this['cSale'] as $eSale) {
			if($eSale->hasAllAccounts() === FALSE) {
				return FALSE;
			}
		}

		return TRUE;
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
			->setCallback('dueDate.check', function(?string &$dueDate) use ($p): bool {

				if($p->isBuilt('sales') === FALSE) {
					return TRUE;
				}

				// Facture d'avoir
				if($this['cSale']->sum('priceExcludingVat') < 0) {
					$dueDate = NULL;
					return TRUE;
				} else {
					return ($dueDate !== NULL);
				}


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
			->setCallback('status.check', function(string &$status) use($p): bool {

				return in_array($status, [Invoice::DRAFT, Invoice::CONFIRMED]);

			});
		
		parent::build($properties, $input, $p);

	}

}
?>
