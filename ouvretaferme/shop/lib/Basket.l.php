<?php
namespace shop;

/**
 * Basket management
 */
class BasketLib {

	public static function checkProductsAndStock(array $products, Date $eDate): array {

		$eDate->expects(['cProduct']);

		$cProduct = $eDate['cProduct'];

		$cleanBasket = [];

		foreach($cProduct as $eProduct) {

			$eProductSelling = $eProduct['product'];

			if(array_key_exists($eProductSelling['id'], $products) === FALSE or empty($products[$eProductSelling['id']])) {
				continue;
			}

			$product = [
				'price' => $eProduct['price'],
				'product' => $eProductSelling,
			];

			$quantityOrder = (float)$products[$eProductSelling['id']]['quantity'] ?? 0.0;
			$quantityRemaining = $eProduct['stock'] - $eProduct['sold'];

			if($eProduct['stock'] === NULL or $quantityOrder <= $quantityRemaining) {
				$product['quantity'] = $quantityOrder;
			} else {
				$product['quantity'] = $quantityRemaining;
			}

			if((float)$product['quantity'] <= 0.0) {
				continue;
			}

			if($eProduct['stock'] !== NULL and $quantityOrder > $quantityRemaining) {
				$product['warning'] = 'quantity';
			}

			$product['maxQuantity'] = $quantityRemaining;

			$cleanBasket[$eProductSelling['id']] = $product;

		}

		return $cleanBasket;
	}

}
?>