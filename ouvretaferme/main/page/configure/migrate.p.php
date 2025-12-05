<?php
new Page()
	->cli('index', function($data) {

		$c = \payment\StripeFarm::model()
			->select([
				'id',
				'aSK' => new Sql('apiSecretKey'),
				'wSK' => new Sql('webhookSecretKey'),
			])
			->getCollection();

		foreach($c as $e) {

			\payment\StripeFarm::model()->update($e, [
				'apiSecretKey' => $e['aSK'],
				'webhookSecretKey' => $e['wSK'],
			]);

		}

	});
?>