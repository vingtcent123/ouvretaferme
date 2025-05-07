<?php
namespace shop;

/**
 * Basket management
 */
class BasketLib {

	public static function getFromQuery(): ?array {

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

	public static function getFromBasket(array $basket): ?array {

		$products = [];
		foreach($basket as ['product' => $eProduct, 'number' => $number]) {
			if($number !== 0.0) {
				$products[(int)$eProduct['product']['id']] = ['number' => $number];
			}
		}
		return $products;

	}

	public static function getFromItem(\Collection $cItem): ?array {

		$products = [];

		foreach($cItem as $eItem) {
			$products[$eItem['product']['id']] = ['number' => $eItem['number']];
		}

		return $products;

	}

	public static function checkAvailableProducts(array $products, \Collection $cProduct, \Collection $cItem, bool &$warning = FALSE): array {

		$cleanBasket = [];

		foreach($cProduct as $eProduct) {

			$eProductSelling = $eProduct['product'];
			$numberOrdered = round($products[$eProductSelling['id']]['number'] ?? 0.0, 2);

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

}
?>