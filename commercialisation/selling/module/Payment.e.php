<?php
namespace selling;

class Payment extends PaymentElement {

	public function acceptDelete(): bool {
		return ($this['closed'] === FALSE);
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
