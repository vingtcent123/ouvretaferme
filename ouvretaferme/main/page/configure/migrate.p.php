<?php
new Page()
	->cli('index', function($data) {

		\selling\Item::model()
			->whereNature(\selling\Item::SILENT)
			->delete();

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

			\selling\ItemLib::create($eItem);

			$calc = \selling\Item::model()
				->whereIngredientOf(NULL)
				->whereSale($e)
				->getValue(new Sql('SUM(priceStats)', 'float'));

			$p = $e['type'] === 'pro' ? $e['priceExcludingVat'] : $e['priceExcludingVat'];

			if(abs($calc - $p) > 0.02) {
				echo $e['id'].': '.$calc.' / '.$p."\n";
			} else {
				echo $e['id']."\n";
			}

		}


	});
?>