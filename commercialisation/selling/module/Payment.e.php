<?php
namespace selling;

class Payment extends PaymentElement {

	public function acceptDelete(): bool {
		return ($this['closed'] === FALSE);
	}

	public function acceptAccountingImport(): bool {

		$this->expects(['readyForAccounting', 'amountIncludingVat', 'status', 'invoice', 'sale', 'accountingHash', 'accountingDifference', 'cashflow']);

		if($this['cashflow']->empty()) {
			return FALSE;
		}

		$this['cashflow']->expects(['amount']);

		if($this['source'] === Payment::INVOICE) {
			$hasAccount = $this['invoice']->hasAllAccounts();
		} else {
			$hasAccount = $this['sale']->hasAllAccounts();
		}

		return (
			$this['accountingHash'] === NULL and
			($this['cashflow']->empty() or ($this['cashflow']['amount'] === $this['amountIncludingVat']) or $this['accountingDifference'] !== NULL) and
			$hasAccount
		);
	}

	public function acceptAccountingIgnore(): bool {
		return $this['accountingHash'] === NULL;
	}

	public static function validateBatch(\Collection $cInvoice): void {

		if($cInvoice->empty()) {

			throw new \FailAction('selling\Payment::payments.check');

		} else {

			$eFarm = $cInvoice->first()['farm'];

			foreach($cInvoice as $eInvoice) {

				if($eInvoice['farm']['id'] !== $eFarm['id']) {
					throw new \NotExpectedAction('Different farms');
				}

			}
		}

	}

	public static function validateBatchIgnore(\Collection $cPayment): void {

		if($cPayment->empty()) {

			throw new \FailAction('selling\Payment::payments.check');

		} else {

			$eFarm = $cPayment->first()['farm'];

			foreach($cPayment as $ePayment) {

				if($ePayment['farm']['id'] !== $eFarm['id']) {
					throw new \NotExpectedAction('Different farms');
				}

				$ePayment->validate('acceptAccountingIgnore');

			}
		}

	}

	public static function getSelection(): array {

		return parent::getSelection() + [
			'method' => fn($e) => \payment\MethodLib::ask($e['method'], $e['farm']),
		];

	}

	public function getElement(): Sale|Invoice {

		$this->expects(['source', 'sale', 'invoice']);

		return match($this['source']) {
			Payment::SALE => $this['sale'],
			Payment::INVOICE => $this['invoice'],
		};

	}

	public function isReadyForAccounting(): bool {

		$this->expects(['status', 'accountingHash', 'accountingDifference', 'amountIncludingVat']);

		if($this['cashflow']->notEmpty()) {
			$this['cashflow']->expects(['amount']);
		}

		return (
			$this['status'] !== Payment::FAILED and
			$this['accountingHash'] === NULL and
			$this['cashflow']->notEmpty() and
			(
				$this['cashflow']['amount'] === $this['amountIncludingVat'] or
				$this['accountingDifference'] !== NULL
			)
		);

	}

	// On considère qu'un paiement par CB peut déterminer si payé ou non
	// Si le mode de paiement est un autre, on peut considérer que le paiement est OK
	public function isNotPaid(): bool {

		$this->expects(['status']);

		return ($this['status'] !== Payment::PAID);

	}

	public function isPaid(): bool {

		$this->expects(['status']);

		return ($this['status'] === Payment::PAID);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('amountIncludingVat.empty', function(?float $amountIncludingVat) use($p): bool {

				if(
					$p->isInvalid('status') or
					$this['status'] === Payment::NOT_PAID
				) {
					return TRUE;
				}

				return ($amountIncludingVat !== NULL);

			})
			->setCallback('paidAt.empty', function(?string $paidAt) use($p): bool {

				if(
					$p->isInvalid('status') or
					$this['status'] === Payment::NOT_PAID
				) {
					return TRUE;
				}

				return ($paidAt !== NULL);

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
