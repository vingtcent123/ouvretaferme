<?php
namespace bank;

class CashflowInvoice extends CashflowInvoiceElement {

	public static function getSelection(): array {

		return CashflowInvoice::model()->getProperties() + [

				'invoice' => \selling\Invoice::getSelection(),

			];

	}
}
?>
