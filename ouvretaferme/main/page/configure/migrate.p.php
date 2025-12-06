<?php
new Page()
	->cli('index', function($data) {

		$l = \util\CsvLib::parseCsv('/tmp/a.csv', ',');

		$u = [];

		foreach($l as [$n, $c]) {

			if(
				\user\Country::model()
					->whereCode($c)
					->update([
						'name' => $n
					]) > 0
			) {
				$u[] = $c;
				echo $c.': UPDATED'."\n";
			} else if(
				\user\Country::model()
					->whereCode($c)
					->whereName($n)
					->exists()
			) {
				$u[] = $c;
			}

		}

		dd(\user\Country::model()->select(\user\Country::getSelection())->whereCode('NOT IN', $u)->getCollection());

	});
?>