<?php
namespace selling;

class MarketLib {

	public static function checkNewPrices(Sale $eSale, array $post): \Collection {

		if($eSale['market'] === FALSE) {
			throw new \NotExpectedAction('Not a market');
		}

		$cItem = new \Collection();

		foreach($post as $id => $newPrice) {

			if($newPrice === '') {
				continue;
			}

			if(Item::model()->check('unitPrice', $newPrice) === FALSE) {
				Item::model()->rollBack();
				throw new \NotExpectedAction('Invalid price');
			}

			$cItem[] = new Item([
				'id' => (int)$id,
				'unitPrice' => (float)$newPrice
			]);

		}

		return $cItem;

	}

	public static function updateMarketPrices(Sale $eSale, \Collection $cItem): void {

		Item::model()->beginTransaction();

		foreach($cItem as $eItem) {

			// On modifie le prix unitaire sans se soucier de la consistance avec les quantités et le montant total
			// En effet les prix de vente peuvent ne pas être homogènes entre les clients
			Item::model()
				->select('unitPrice')
				->whereSale($eSale)
				->update($eItem);

		}


		Item::model()->commit();

	}

	public static function checkNewItems(Sale $eSaleMarket, Sale $eSale, array $post): \Collection {

		array_expects($post, ['locked']);

		$cItemMarket = SaleLib::getItems($eSaleMarket);

		if($eSale['marketParent']->empty()) {
			throw new \NotExpectedAction('Not a market sale');
		}

		$cItemSale = new \Collection();

		foreach($cItemMarket as $eItemMarket) {

			if(array_key_exists($eItemMarket['id'], $post['locked']) === FALSE) {
				continue;
			}

			$unitPrice = (float)($post['unitPrice'][$eItemMarket['id']] ?? 0.0);
			$number = (float)($post['number'][$eItemMarket['id']] ?? 0.0);
			$price = (float)($post['price'][$eItemMarket['id']] ?? 0.0);
			$locked = var_filter($post['locked'][$eItemMarket['id']] ?? NULL, Item::model()->getPropertyEnum('locked'));

			$eItemSale = new Item([
				'sale' => $eSale,
				'farm' => $eSale['farm'],
				'customer' => $eSale['customer'],
				'product' => $eItemMarket['product'],
				'name' => $eItemMarket['name'],
				'packaging' => NULL,
				'locked' => \selling\Item::PRICE,
				'unit' => $eItemMarket['unit'],
				'unitPrice' => $unitPrice,
				'number' => $number,
				'price' => $price,
				'locked' => $locked,
				'vatRate' => $eItemMarket['vatRate'],
				'parent' => $eItemMarket
			]);

			$cItemSale[] = $eItemSale;

		}

		return $cItemSale;

	}

	public static function updateSaleItems(Sale $eSale, \Collection $cItem): void {

		$cItem->expects(['parent']);

		Item::model()->beginTransaction();

		Item::model()
			->whereSale($eSale)
			->whereParent('IN', $cItem->getColumnCollection('parent'))
			->delete();

		if($cItem->notEmpty()) {
			self::createItems($cItem);
		}

		Item::model()->commit();

	}

	public static function createItems(\Collection $cItem): void {

		foreach($cItem as $eItem) {

			if(
				($eItem['locked'] === Item::UNIT_PRICE and $eItem['number'] !== 0.0 and $eItem['price'] !== 0.0) or
				($eItem['locked'] === Item::PRICE and $eItem['number'] !== 0.0 and $eItem['unitPrice'] !== 0.0) or
				($eItem['locked'] === Item::NUMBER and $eItem['unitPrice'] !== 0.0 and $eItem['price'] !== 0.0)
			) {

				ItemLib::prepareCreate($eItem);

				Item::model()->insert($eItem);

			}
		}

		SaleLib::recalculate($eItem['sale']);

	}

	public static function updateSaleMarket(Sale $eSaleMarket): void {

		Item::model()->beginTransaction();

		Sale::model()
			->select(Sale::getSelection())
			->get($eSaleMarket);

		$cItemMarket = SaleLib::getItems($eSaleMarket);

		$cItemSaled = Item::model()
			->select([
				'parent',
				'totalNumber' => new \Sql('SUM(number)', 'float'),
				'totalPrice' => new \Sql('SUM(price)', 'float'),
				'totalPriceExcludingVat' => new \Sql('SUM(priceExcludingVat)', 'float'),
			])
			->whereParent('IN', $cItemMarket)
			->whereStatus(Sale::DELIVERED)
			->group('parent')
			->getCollection(NULL, NULL, 'parent');

		foreach($cItemMarket as $eItemMarket) {

			if($cItemSaled->offsetExists($eItemMarket['id'])) {

				$eItemSaled = $cItemSaled[$eItemMarket['id']];

				$update = [
					'number' => round($eItemSaled['totalNumber'], 2),
					'price' => round($eItemSaled['totalPrice'], 2),
					'priceExcludingVat' => round($eItemSaled['totalPriceExcludingVat'], 2)
				];

			} else {
				$update = [
					'number' => 0.0,
					'price' => 0.0,
					'priceExcludingVat' => 0.0
				];
			}

			Item::model()->update($eItemMarket, $update);

		}

		Sale::model()->update($eSaleMarket, [
			'marketSales' => Sale::model()
				->whereMarketParent($eSaleMarket)
				->wherePreparationStatus(Sale::DELIVERED)
				->count()
		]);

		SaleLib::recalculate($eSaleMarket);

		Item::model()->commit();

	}

	public static function close(Sale $eSale): void {

		if($eSale['preparationStatus'] !== Sale::SELLING) {
			return;
		}

		$eSale['oldStatus'] = Sale::SELLING;
		$eSale['preparationStatus'] = Sale::DELIVERED;

		SaleLib::update($eSale, ['preparationStatus']);

	}

}
?>
