<?php
namespace invoicing;

Class InvoiceLib extends InvoiceCrud {

	const INVOICES_PER_PAGE = 100;

	public static function getAll(\Search $search, int $offset = 0): array {

		$cInvoice = Invoice::model()
			->select(Invoice::getSelection())
			->whereDirection('=', $search->get('direction'), if: $search->get('direction'))
			->option('count')
			->sort($search->buildSort())
			->getCollection($offset, self::INVOICES_PER_PAGE);

		$nInvoice = Invoice::model()->found();

		return [$cInvoice, $nInvoice];

	}

}
