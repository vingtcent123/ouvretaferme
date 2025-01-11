<?php
namespace shop;

/**
 * Basket management
 */
class BasketLib {

	public static function checkAvailableProducts(array $products, \Collection $cProduct, \selling\Sale $eSale): array {

		$cleanBasket = [];

		foreach($cProduct as $eProduct) {

			$eProductSelling = $eProduct['product'];

			if(array_key_exists($eProductSelling['id'], $products) === FALSE or empty($products[$eProductSelling['id']])) {
				continue;
			}

			$available = ProductLib::getReallyAvailable($eProduct, $eProductSelling, $eSale);

			$product = [
				'product' => $eProduct,
				'warning' => NULL
			];

			$numberOrdered = round($products[$eProductSelling['id']]['number'] ?? 0.0, 2);

			if($available === NULL or $numberOrdered <= $available) {
				$product['number'] = $numberOrdered;
			} else {
				$product['number'] = $available;
			}

			if($available !== NULL and $numberOrdered > $available) {
				$product['warning'] = 'number';
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
				}
			}

			$cleanBasket[$eProductSelling['id']] = $product;

		}

		return $cleanBasket;
	}

}
?>