<?php
namespace invoicing;

Class EventLib extends EventCrud {

	public static function getByInvoice(Invoice $eInvoice): \Collection {

		return Event::model()
			->select(Event::getSelection())
			->whereInvoice($eInvoice)
			->sort(['createdAt' => SORT_DESC])
			->getCollection();

	}


}
