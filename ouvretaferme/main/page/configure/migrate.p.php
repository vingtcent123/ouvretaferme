<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Sale::model()
			->select(\selling\SaleElement::getSelection())
			->whereShipping('!=', NULL)
			->getCollection();

		foreach($c as $e) {

			$eFarm = \farm\FarmLib::getById($e['farm']);
			$e['farm'] = $eFarm;

			$eProduct = \selling\ProductLib::getShippingByFarm($eFarm);

			$eItem = new \selling\Item([
				'sale' => $e,
				'farm' => $e['farm']
			]);

			$eItem->fillFromProduct($eProduct, $eFarm);

			$eItem['locked'] = \selling\Item::PRICE;
			$eItem['number'] = 1;
			$eItem['packaging'] = NULL;
			$eItem['unitPrice'] = $e['shipping'];
			$eItem['unitPriceInitial'] = NULL;
			$eItem['vatRate'] = $e['shippingVatRate'];

			echo $e['id']."\n";

			\selling\ItemLib::create($eItem);

			dd('ok');

		}


	});
?>