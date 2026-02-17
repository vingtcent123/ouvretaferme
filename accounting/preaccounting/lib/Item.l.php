<?php
namespace preaccounting;

Class ItemLib {

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search): \selling\ItemModel {

		$salesId = SaleLib::getPaidSaleIdsForPeriod($eFarm, $search->get('from'), $search->get('to'));

		// Articles pour lesquels il manque un numéro de compte
		return \selling\Item::model()
			->join(\selling\Sale::model(), 'm1.sale = m2.id')
			->join(\selling\Product::model(), 'm1.product = m3.id', 'LEFT')
			->where('m1.farm = '.$eFarm['id'])
			->where('m2.id IN ('.join(', ', $salesId).')', if: count($salesId) > 0)
			->where('m3.id IS NULL')
			->where('m1.account IS NULL')
		;

	}
	public static function countForAccountingCheck(\farm\Farm $eFarm, \Search $search): int {

		return self::filterForAccountingCheck($eFarm, $search)
      ->select(['count' => new \Sql('COUNT(DISTINCT(m1.name))', 'int')])
			->get()['count'] ?? 0;

	}

	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search, string $type): \Collection {

		return self::filterForAccountingCheck($eFarm, $search, $type)
			->select([
				'name' => new \Sql('DISTINCT(m1.name)'),
			])
			->getCollection();
	}

	public static function getBySaleForFec(\selling\Sale $eSale): \Collection {

		return \selling\Item::model()
			->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
			->whereSale($eSale)
			->getCollection();

	}
}
