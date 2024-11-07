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
			];

			$numberOrdered = (float)($products[$eProductSelling['id']]['number'] ?? 0.0);

			if($available === NULL or $numberOrdered <= $available) {
				$product['number'] = $numberOrdered;
			} else {
				$product['number'] = $available;
			}

			if((float)$product['number'] <= 0.0) {
				continue;
			}

			if($available !== NULL and $numberOrdered > $available) {
				$product['warning'] = 'number';
			}

			$cleanBasket[$eProductSelling['id']] = $product;

		}

		return $cleanBasket;
	}

}
?>