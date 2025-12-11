<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Item::model()
			->select(\selling\Item::getSelection())
			->whereProduct(13659)
			->getCollection();

		foreach($c as $e) {

			\selling\ItemLib::updateIngredients($e);

		}

	});
?>