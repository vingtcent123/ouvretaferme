<?php
namespace media;
/**
 * Manages item vignettes images
 *
 */
class ProductVignetteLib extends MediaLib {

	public function buildElement(): \selling\Product {

		$eProduct = POST('id', 'selling\Product');

		if(
			$eProduct->empty() or
			\selling\Product::model()
				->select(['vignette', 'farm'])
				->get($eProduct) === FALSE
		) {
			throw new \NotExistsAction('Product');
		}

		if(
			\Privilege::can('selling\admin') === FALSE and
			$eProduct->canWrite() === FALSE
		) {
			throw new \NotAllowedAction();
		}

		return $eProduct;

	}

}
?>
