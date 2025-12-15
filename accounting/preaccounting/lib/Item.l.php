<?php
namespace preaccounting;

Class ItemLib {

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search, bool $searchProblems = TRUE): \selling\ItemModel {

		if($searchProblems) {
			\selling\Item::model()->whereAccount(NULL);
		} else {
			\selling\Item::model()->where('account IS NOT NULL');
		}

		return \selling\Item::model()
			->join(\selling\Sale::model(), 'm1.sale = m2.id')
			->whereProduct(NULL)
			->where('m2.id IS NOT NULL')
			->where('m2.profile IN ('.\selling\Sale::model()->format(\selling\Sale::SALE).', '.\selling\Sale::model()->format(\selling\Sale::SALE_MARKET).')')
			->where('m1.farm = '.$eFarm['id'])
			->where('m2.deliveredAt BETWEEN '.\selling\Item::model()->format($search->get('from')).' AND '.\selling\Item::model()->format($search->get('to')));

	}
	public static function countForAccountingCheck(\farm\Farm $eFarm, \Search $search): int {

		return self::filterForAccountingCheck($eFarm, $search)->count();

	}

	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search): array {

		$nToCheck = self::countForAccountingCheck($eFarm, $search);
		$nVerified = self::filterForAccountingCheck($eFarm, $search, FALSE)->count();

		$cItem = self::filterForAccountingCheck($eFarm, $search)
			->select([
				'id', 'name',
				'customer' => ['name', 'type', 'destination'],
				'sale' => ['id', 'document', 'deliveredAt', 'preparationStatus', 'taxes', 'hasVat', 'priceIncludingVat', 'priceExcludingVat'],
			])
			->group(['sale', 'm1.id'])
			->getCollection(NULL, NULL, ['sale', NULL]);


		return [$nToCheck, $nVerified, $cItem];
	}

}
