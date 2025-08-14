<?php
new \selling\GridPage()
	->applyElement(function($data, \selling\Grid $e) {

		\selling\Product::model()
			->select('farm', 'status')
			->get($e['product']);

		$e['product']->validate('canWrite');

		if($e['priceInitial'] !== NULL) {
			$e['priceDiscount'] = $e['price'];
		}
	})
	->quick(['price', 'priceDiscount', 'packaging']);
?>
