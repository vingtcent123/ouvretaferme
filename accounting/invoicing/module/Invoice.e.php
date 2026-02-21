<?php
namespace invoicing;

class Invoice extends InvoiceElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'buyer' => ThirdParty::getSelection(),
			'seller' => ThirdParty::getSelection(),
		];

	}

}
?>
