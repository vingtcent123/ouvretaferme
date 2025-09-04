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
				->select(['vignette', 'composition', 'farm', 'status'])
				->get($eProduct) === FALSE
		) {
			throw new \NotExistsAction('Product');
		}

		if(
			\user\ConnectionLib::getOnline()->isAdmin() === FALSE and
			$eProduct->canWrite() === FALSE
		) {
			throw new \NotAllowedAction();
		}

		return $eProduct;

	}

}
?>
