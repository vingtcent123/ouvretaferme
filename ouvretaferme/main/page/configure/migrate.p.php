<?php
new Page()
	->cli('netPrice', function($data) {

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

	})
	->cli('shipping', function($data) {

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
				->getValue(new Sql('ROUND(SUM(netPriceExcludingVat), 2)', 'float'));

			$p = $e['type'] === 'pro' ? $e['priceExcludingVat'] : $e['priceExcludingVat'];

			if(abs($calc - $p) > 0.02) {
				echo $e['id'].': '.$calc.' / '.$p."\n";
			} else {
				echo $e['id']."\n";
			}

		}


	});
?>