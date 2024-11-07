<?php

use selling\Item;

(new Page())
	->cli('index', function($data) {

		$cProduct = \shop\Product::model()
			->select(\shop\Product::getSelection())
			->where(new Sql('stock is not null'))
			->getCollection();

		foreach($cProduct as $eProduct) {

			$sold = Item::model()
				->whereProduct($eProduct['product'])
				->whereShopDate($eProduct['date'])
				->whereStatus('!=', \selling\Sale::CANCELED)
				->getValue(new Sql('SUM(number)', 'float'));

			if($sold !== NULL) {

				$available = max(0, round($eProduct['stock'] - $sold, 2));

				\shop\Product::model()->update($eProduct, [
					'available' => $available,
				]);

			}


		}

	});
?>