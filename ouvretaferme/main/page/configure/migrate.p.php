<?php
new Page()
	->cli('index', function($data) {

		$c = \user\Country::model()
			->select('id', 'center')
			->getCollection();

		foreach($c as $e) {

			\user\Country::model()->update($e, [
				'center' => array_reverse($e['center'])
			]);

		}

	});
?>