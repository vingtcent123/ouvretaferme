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

	public static function createBySale(Sale $eSale, string $fqn, ?string $comment = NULL, Payment $ePayment = new Payment()): void {

		$eSale->expects(['farm']);

		$eEvent = Event::model()
			->select('id')
			->whereFqn($fqn)
			->get();

		if($eEvent->empty()) {
			throw new \Exception(s("Unknown event '".encode($fqn)."'"));
		}

		$e = new History([
			'sale' => $eSale,
			'farm' => $eSale['farm'],
			'event' => $eEvent,
			'payment' => $ePayment,
			'comment' => $comment
		]);

		History::model()->insert($e);

	}

}
?>
