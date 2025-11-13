<?php
namespace shop;

/**
 * Basket management
 */
class BasketLib {

	public static function getProductsFromQuery(): ?array {

		try {
			if(get_exists('products')) {
				$products = [];
				foreach(json_decode(GET('products'), TRUE, flags:  JSON_THROW_ON_ERROR) as $product => $value) {
					$number = (float)($value['number'] ?? 0.0);
					if($number !== 0.0) {
						$products[(int)$product] = ['number' => $number];
					}
				}
				return $products;
			}
		} catch(Exception) {
		}

		return NULL;

	}

	public static function getProductsFromBasket(array $basket): ?array {

		$products = [];
		foreach($basket as ['product' => $eProduct, 'number' => $number]) {
			if($number !== 0.0) {
				$products[$eProduct['product']['id']] = ['number' => $number];
			}
		}
		return $products;

	}

	public static function getProductsFromItem(\Collection $cItem): ?array {

		$products = [];

		foreach($cItem as $eItem) {
			$products[$eItem['product']['id']] = ['number' => $eItem['number']];
		}

		return $products;

	}

	public static function checkAvailableProducts(array $products, \Collection $cProduct, \Collection $cItem, bool &$warning = FALSE): array {

		$cleanBasket = [];

		foreach($cProduct as $eProduct) {

			if($eProduct['parent']) {
				continue;
			}

			$eProductSelling = $eProduct['product'];
			$numberOrdered = round((float)($products[$eProductSelling['id']]['number'] ?? 0.0), 2);

			if($numberOrdered === 0.0) {
				continue;
			}

			$available = ProductLib::getReallyAvailable($eProduct, $eProductSelling, $cItem);

			$product = [
				'product' => $eProduct,
				'warning' => NULL
			];

			if($available === NULL or $numberOrdered <= $available) {
				$product['number'] = $numberOrdered;
			} else {
				$product['number'] = $available;
			}

			if($available !== NULL and $numberOrdered > $available) {
				$product['warning'] = 'number';
				$warning = TRUE;
			}

			if(
				$eProduct['limitMin'] !== NULL and
				$product['number'] < $eProduct['limitMin']
			) {

				if(
					$product['warning'] === 'number' or
					($available !== NULL and $eProduct['limitMin'] > $available)
				) {
					continue;
				} else {
					$product['number'] = $eProduct['limitMin'];
					$product['warning'] = 'min';
					$warning = TRUE;
				}
			}

			$cleanBasket[$eProductSelling['id']] = $product;

		}

		return $cleanBasket;
	}

	public static function reorganizeByFarm(Shop $eShop, array $basket, array $discounts): array {

		$basketByFarm = [];
		$approximate = FALSE;

		foreach($basket as $product) {

			$eFarm = $product['product']['product']['farm'];

			$basketByFarm[$eFarm['id']] ??= [
				'farm' => $eFarm,
				'products' => [],
				'discount' => $discounts[$eFarm['id']] ?? 0,
				'priceGross' => NULL,
				'price' => 0
			];

			$basketByFarm[$eFarm['id']]['products'][] = $product;
			$basketByFarm[$eFarm['id']]['price'] += round($product['product']['price'] * $product['number'] * ($product['product']['packaging'] ?? 1), 2);

			if(
				$eShop->isApproximate() and
				$product['product']['product']['unit']->notEmpty() and
				$product['product']['product']['unit']['approximate']
			) {
				$approximate = TRUE;
			}

		}

		$price = 0;

		foreach($basketByFarm as $farm => $basket) {

			if($basket['discount'] > 0) {

				$basketByFarm[$farm]['priceGross'] = $basket['price'];
				$basketByFarm[$farm]['price'] = round($basket['price'] - \selling\Sale::calculateDiscount($basket['price'], $basket['discount']), 2);

			}

			$price += $basketByFarm[$farm]['price'];

		}

		$price = round($price, 2);

		return [$basketByFarm, $price, $approximate];

	}

}
?>
