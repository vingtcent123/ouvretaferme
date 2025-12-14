<?php
new Page()
	->cli('index', function($data) {

		$c = \map\Zone::model()
			->select('id', 'coordinates')
			->whereCoordinates('!=', NULL)
			->getCollection();

		foreach($c as $e) {

			foreach($e['coordinates'] as $k => $v) {
				$e['coordinates'][$k] = array_reverse($v);
			}

			\map\Zone::model()
				->select('coordinates')
				->update($e);

		}

		$c = \map\Plot::model()
			->select('id', 'coordinates')
			->whereCoordinates('!=', NULL)
			->getCollection();

		foreach($c as $e) {

			foreach($e['coordinates'] as $k => $v) {
				$e['coordinates'][$k] = array_reverse($v);
			}

			\map\Plot::model()
				->select('coordinates')
				->update($e);

		}

	});
?>