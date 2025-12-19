<?php
namespace preaccounting;

Class ProductLib {
	
	private static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search, bool $searchProblems = TRUE): \selling\ProductModel {

		if($searchProblems) {
			\selling\Product::model()->wherePrivateAccount(NULL);
		} else {
			\selling\Product::model()->where('privateAccount IS NOT NULL');
		}
		return \selling\Product::model()
			->join(\selling\Item::model(), 'm1.id = m2.product', 'LEFT')
			->where('m1.farm = '.$eFarm['id'])
			->where('m2.product IS NOT NULL')
			->where('m1.status != '.\selling\Product::model()->format(\selling\Product::DELETED))
			->where('m2.deliveredAt BETWEEN '.\selling\Item::model()->format($search->get('from')).' AND '.\selling\Item::model()->format($search->get('to')));

	}
	/**
	 * Gets all the products linked to a sale but without any account
	 */
	public static function countForAccountingCheck(\farm\Farm $eFarm, \Search $search, bool $searchProblems = TRUE): int {

		return (self::filterForAccountingCheck($eFarm, $search, $searchProblems)
			->select(['count' => new \Sql('COUNT(DISTINCT(m1.id))', 'int')])
			->get()['count'] ?? 0);

	}

	/**
	 * Gets all the products linked to a sale but without any account
	 */
	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search): array {

		$cCategories = \selling\CategoryLib::getByFarm($eFarm);

		$cProduct = self::filterForAccountingCheck($eFarm, $search)
			->select(['category', 'count' => new \Sql('COUNT(DISTINCT(m1.id))')])
			->group('category')
			->getCollection(NULL, NULL, 'category');
		$productsByCategory = [];
		foreach($cProduct as $eProduct) {
			$productsByCategory[$eProduct['category']['id'] ?? 0] = $eProduct['count'];
		}

		$nToCheck = self::countForAccountingCheck($eFarm, $search);

		$nVerified = (self::filterForAccountingCheck($eFarm, $search, FALSE)
			->select(['count' => new \Sql('COUNT(DISTINCT(m1.id))', 'int')])
			->get()['count'] ?? 0);

		if(get_exists('tab')) {

			\session\SessionLib::set('preAccountingProductTab', GET('tab'));
			$tab = GET('tab', 'string', '');

		} else {

			try {
				$tab = \session\SessionLib::get('preAccountingProductTab');
			} catch(\Exception) {
				$tab = \selling\CategoryLib::getById(first(array_keys($productsByCategory)));
			}

		}

		if($tab === 'items') {

			return [$nToCheck, $nVerified, new \Collection(), $cCategories, $productsByCategory];

		} else if(is_string($tab)) {

			if(in_array((int)$tab, array_keys($productsByCategory)) === FALSE) {

				$tab = first(array_keys($productsByCategory));
			}

			$tab = \selling\CategoryLib::getById($tab);

		} else {

			if(
				($tab->empty() and isset($productsByCategory[0]) === FALSE) or
				($tab->notEmpty() and in_array($tab['id'], array_keys($productsByCategory)) === FALSE)
			) {
				$tab = first(array_keys($productsByCategory));
				$tab = \selling\CategoryLib::getById($tab);
			}

		}

		$search->set('tab', $tab);
		\session\SessionLib::set('preAccountingProductTab', $tab);

		$cProduct = self::filterForAccountingCheck($eFarm, $search)
			->select([
				'id' => new \Sql('DISTINCT(m1.id)'), 'name' => new \Sql('m1.name'),
				'proAccount' => ['id', 'class', 'description'], 'privateAccount' => ['id', 'class', 'description'],
				'category',
				'vignette', 'unprocessedPlant' => ['fqn', 'vignette'], 'profile', 'farm', 'status', 'unprocessedVariety', 'mixedFrozen', 'quality', 'additional', 'origin',
			])
			->whereCategory($tab)
			->sort(['m1.name' => SORT_ASC])
			->getCollection();

		return [$nToCheck, $nVerified, $cProduct, $cCategories, $productsByCategory];

	}
	
}
