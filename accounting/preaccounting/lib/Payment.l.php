<?php
namespace preaccounting;

Class PaymentLib {

	public static function getByHash(string $hash): \selling\Payment {

		return \selling\Payment::model()
			->select(\selling\Payment::getSelection() + [
					'invoice' => ['id', 'number', 'document', 'date', 'priceIncludingVat', 'customer' => ['id', 'legalName', 'name', 'type']],
					'sale' => ['id', 'document', 'deliveredAt', 'priceIncludingVat', 'customer' => ['id', 'legalName', 'name', 'type']]
				])
			->whereAccountingHash($hash)
			->get();

	}

	public static function updateAccountingDifference(\selling\Payment $ePayment, string $accountingDifference): void {

		$update = ['accountingDifference' => $accountingDifference];

		$fw = new \FailWatch();

		$ePayment->build(['accountingDifference'], $update);

		$fw->validate();

		if($ePayment->isAccountingReady()) {
			$update['accountingReady'] = TRUE;
		}

		\selling\Payment::model()->update($ePayment, $update);

	}

	public static function getPaymentSelection(): array {
		return \selling\Payment::getSelection() + [
			'sale' => \selling\Sale::getSelection() + [
				'cItem' => \selling\Item::model()
					->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
					->delegateCollection('sale')
			],
			'invoice' => \selling\Invoice::getSelection() + [
				'cSale' => \selling\Sale::model()
					->select([
						'id', 'shipping', 'shippingExcludingVat', 'shippingVatRate',
						'cItem' => \selling\Item::model()
							->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
							->delegateCollection('sale')
					])
					->delegateCollection('invoice')
			],
			'cashflow' => \bank\Cashflow::getSelection() + ['account' => 'account']
		];
	}

	public static function setAccountingReady(\farm\Farm $eFarm): void {

		$cPayment = \selling\Payment::model()
			->select(self::getPaymentSelection())
			->whereFarm($eFarm)
			->whereAccountingReady(FALSE)
			->whereStatus(\selling\Payment::PAID)
			->whereCashflow('!=', NULL)
			->whereAccountingHash(NULL)
			->getCollection();

		foreach($cPayment as $ePayment) {

			if($ePayment->acceptAccountingImport()) {
				$update = ['accountingReady' => TRUE];
				if($ePayment['amountIncludingVat'] !== $ePayment['cashflow']['amount']) {
					if(abs($ePayment['amountIncludingVat'] - $ePayment['cashflow']['amount']) < 1) {
						$update['accountingDifference'] = \selling\Payment::AUTOMATIC;
					} else {
						$update['accountingDifference'] = \selling\Payment::NOTHING;
					}
				}
				\selling\Payment::model()->update($ePayment, $update);
			}

		}

	}

	public static function getByInvoiceForFec(\selling\Invoice $eInvoice): \Collection {

		return \selling\Payment::model()
			->select(\selling\Payment::getSelection() + ['cashflow' => ['id', 'amount', 'account' => ['account']]])
			->whereStatus(\selling\Payment::PAID)
			->whereFarm($eInvoice['farm'])
			->whereInvoice($eInvoice)
			->getCollection();

	}

	public static function getBySaleForFec(\selling\Sale $eSale): \Collection {

		return \selling\Payment::model()
			->select(\selling\Payment::getSelection() + ['cashflow' => ['id', 'amount', 'account' => ['account']]])
			->whereStatus(\selling\Payment::PAID)
			->whereFarm($eSale['farm'])
			->whereSale($eSale)
			->getCollection();

	}

}
