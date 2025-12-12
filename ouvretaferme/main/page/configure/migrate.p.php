<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Item::model()
			->select(\selling\Item::getSelection())
			->whereProductComposition(TRUE)
			->getCollection();

		foreach($c as $e) {

			\selling\Item::model()->select([
				'cItemIngredient' => \selling\SaleLib::delegateIngredients($e['sale']['deliveredAt'], 'product')
			])->get($e);

			if($e['cItemIngredient']->notEmpty()) {
				$s = $e['cItemIngredient']->first()['sale'];
				\selling\Item::model()->update($e, [
					'composition' => $s
				]);
			} else {
				echo '?';
			}

		}

	});
?>