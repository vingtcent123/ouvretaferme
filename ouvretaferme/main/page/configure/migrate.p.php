<?php
new Page()
	->cli('index', function($data) {

		$l = \util\CsvLib::parseCsv('/home/vincent/Documents/a.csv', ',');

		foreach($l as [$c, $lat, $lon]) {

			\user\Country::model()
				->whereCode($c)
				->update([
					'center' => [$lat, $lon]
				]);

		}

	});
?>