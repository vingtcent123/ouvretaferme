<?php
namespace asset;

Class RecognitionLib extends RecognitionCrud {

	public static function sumByGrant(Asset $eGrant): float {

		return (Recognition::model()
			->select(['sum' => new \Sql('SUM(amount)', 'float')])
			->whereGrant($eGrant)
			->get()['sum'] ?? 0);

	}

	public static function saveByValues(array $values): void {

		$eRecognition = new Recognition($values);
		Recognition::model()->insert($eRecognition);

	}

}
