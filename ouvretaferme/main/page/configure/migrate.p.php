<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->whereProfile(\selling\Sale::MARKET)
			->whereClosed(TRUE)
			->getCollection();

		foreach($c as $e) {

			\selling\PaymentMarketLib::calculateAggregation($e);
			echo '.';

		}

	});
?>