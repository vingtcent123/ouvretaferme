<?php
namespace selling;

class HistoryLib extends HistoryCrud {

	public static function getBySale(Sale $eSale): \Collection {

		return History::model()
			->select(History::getSelection())
			->whereSale($eSale)
			->sort(['id' => SORT_DESC])
			->getCollection();

	}

	public static function getByInvoice(Invoice $eInvoice): \Collection {

		return History::model()
			->select(History::getSelection())
			->whereInvoice($eInvoice)
			->sort(['id' => SORT_DESC])
			->getCollection();

	}

	public static function createByElement(Sale|Invoice $eElement, string $fqn, ?string $comment = NULL, Payment $ePayment = new Payment()): void {

		$eElement->expects(['farm']);

		$eEvent = Event::model()
			->select('id')
			->whereFqn($fqn)
			->get();

		if($eEvent->empty()) {
			throw new \Exception(s("Unknown event '".encode($fqn)."'"));
		}

		$e = new History([
			'farm' => $eElement['farm'],
			'event' => $eEvent,
			'payment' => $ePayment,
			'comment' => $comment
		]);

		if($eElement instanceof Sale) {
			$e['sale'] = $eElement;
			$e['source'] = History::SALE;
		} else {
			$e['invoice'] = $eElement;
			$e['source'] = History::INVOICE;
		}

		History::model()->insert($e);

	}

}
?>
