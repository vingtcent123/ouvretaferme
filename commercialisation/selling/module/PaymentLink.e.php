<?php
namespace selling;

class PaymentLink extends PaymentLinkElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'sale' => Sale::getSelection(),
			'invoice' => Invoice::getSelection(),
		];

	}

	public function getElement(): Sale|Invoice {

			return match($this['source']) {

				PaymentLink::SALE => $this['sale'],
				PaymentLink::INVOICE => $this['invoice'],

			};

	}

	public function getElementName(): string {

			return match($this['source']) {

				PaymentLink::SALE => SaleUi::getName($this['sale']),
				PaymentLink::INVOICE => InvoiceUi::getName($this['invoice']),

			};

	}
	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('validUntil.prepare', function(string $date): bool {

				return date('Y-m-d') < $date;

			})
			->setCallback('invoice.prepare', function(Invoice &$eInvoice) use($p): bool {

				if($p->isBuilt('source') === FALSE or $this['source'] !== PaymentLink::INVOICE) {
					return TRUE;
				}

				$eInvoice = InvoiceLib::getById($eInvoice['id']);

				return $eInvoice->notEmpty() and $eInvoice->acceptStripeLink();

			})
			->setCallback('sale.prepare', function(Sale &$eSale) use($p): bool {

				if($p->isBuilt('source') === FALSE or $this['source'] !== PaymentLink::SALE) {
					return TRUE;
				}

				$eSale = SaleLib::getById($eSale['id']);

				return $eSale->notEmpty() and $eSale->acceptStripeLink();

			})
			->setCallback('amountIncludingVat.prepare', function(float $amountIncludingVat) use($p): bool {

				if($p->isBuilt('source') === FALSE) {
					return TRUE;
				}

				$missingAmount = round(match($this['source']) {
					PaymentLink::SALE => $this['sale']['priceIncludingVat'] - $this['sale']['paymentAmount'],
					PaymentLink::INVOICE => $this['invoice']['priceIncludingVat'] - $this['invoice']['paymentAmount'],
				}, 2);

				return $amountIncludingVat > 0 and $amountIncludingVat <= $missingAmount;

			})
		;

		parent::build($properties, $input, $p);

	}
}
?>
