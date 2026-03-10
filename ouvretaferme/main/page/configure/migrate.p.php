<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Item::model()
			->select([
				'id',
				'price', 'vatRate', 'discount',
				'sale' => ['taxes']
			])
			->whereIngredientOf(NULL)
			->getCollection();

		$i = 1;

		foreach($c as $e) {

			\selling\ItemLib::recalculateNetPricing($e);

			\selling\Item::model()
				->select('netPriceExcludingVat')
				->update($e);

			$i++;

			if($i % 100 === 0) {
				echo $i."\r";
			}

		}

		echo "\n";

	});
?>