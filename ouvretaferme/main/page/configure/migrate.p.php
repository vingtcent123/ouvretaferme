<?php
new Page()
	->cli('index', function($data) {

		$c = \payment\StripeFarm::model()
			->select()
			->getCollection();

		foreach($c as $e) {


		}

	});
?>