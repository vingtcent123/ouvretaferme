<?php
new Page()
	->cli('index', function($data) {

		$c = \farm\Farm::model()
			->select('id', 'cultivationLngLat')
			->whereCultivationLngLat('!=', NULL)
			->getCollection();

		foreach($c as $e) {

			\farm\Farm::model()->update($e, [
				'cultivationLngLat' => array_reverse($e['cultivationLngLat'])
			]);

		}

	});
?>