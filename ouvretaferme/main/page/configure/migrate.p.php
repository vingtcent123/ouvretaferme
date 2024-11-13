<?php
(new Page())
	->cli('index', function($data) {

		$cDate = \shop\Date::model()
			->select(\shop\Date::getSelection())
			->getCollection();

		foreach($cDate as $eDate) {

			\shop\Date::model()->update($eDate, [
				'products' => \shop\Product::model()
					->whereDate($eDate)
					->count()
			]);

		}

	});
?>