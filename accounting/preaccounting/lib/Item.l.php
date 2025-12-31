<?php
namespace preaccounting;

Class ItemLib {

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search, string $type): \selling\ItemModel {

		return \selling\Item::model()
			->join(\selling\Sale::model(), 'm1.sale = m2.id', 'LEFT')
			->join(\selling\Product::model(), 'm1.product = m3.id', 'LEFT')
			->where('m1.farm = '.$eFarm['id'])
			->where('m2.invoice IS NOT NULL', if: $type === 'invoice')
			->where('m2.invoice IS NULL', if: $type === 'sale')
			->where('m2.deliveredAt BETWEEN '.\selling\Item::model()->format($search->get('from')).' AND '.\selling\Item::model()->format($search->get('to')))
			->where('m3.id IS NULL OR m3.privateAccount IS NULL')
			->where('m1.account IS NULL')
		;

	}
	public static function countForAccountingCheck(\farm\Farm $eFarm, \Search $search, string $type): int {

		return self::filterForAccountingCheck($eFarm, $search, $type)->count();

	}

	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search, string $type): \Collection {

		return self::filterForAccountingCheck($eFarm, $search, $type)
			->select([
				'name' => new \Sql('DISTINCT(m1.name)'),
			])
			->getCollection();
	}

}
