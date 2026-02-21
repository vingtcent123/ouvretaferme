<?php
namespace invoicing;

Class LineLib extends LineCrud {

	public static function getByInvoice(Invoice $eInvoice): \Collection {

		return Line::model()
			->select(Line::getSelection())
			->whereInvoice($eInvoice)
			->sort(['id' => SORT_ASC])
			->getCollection();

	}

}
