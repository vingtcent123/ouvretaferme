<?php
namespace asset;

Class ReversalLib extends ReversalCrud {

	public static function sumByGrant(Asset $eGrant): float {

		return (Reversal::model()
			->select(['sum' => new \Sql('SUM(amount)', 'float')])
			->whereGrant($eGrant)
			->get()['sum'] ?? 0);

	}

	public static function saveByValues(array $values): void {

		$eReversal = new Reversal($values);
		Reversal::model()->insert($eReversal);

	}

}
