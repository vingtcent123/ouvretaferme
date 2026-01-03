<?php
namespace preaccounting;

Class ProductLib {
	
	private static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search, bool $searchProblems = TRUE): \selling\ProductModel {

		if($searchProblems) {
			\selling\Product::model()->wherePrivateAccount(NULL);
		} else {
			\selling\Product::model()->where('privateAccount IS NOT NULL');
		}

		if($search->get('name')) {
			\selling\Product::model()->whereName('LIKE', '%'.$search->get('name').'%');
		}

		if($search->get('profile')) {
			\selling\Product::model()->where('m1.profile = '.\selling\Product::model()->format($search->get('profile')));
		}

		if($search->get('plant')) {
			$cPlant = \plant\PlantLib::getFromQuery($search->get('plant'), $eFarm);
			\selling\Product::model()->whereUnprocessedPlant('IN', $cPlant);
		}

		// Fonctionnel uniquement si la méthode est toujours appelée pour la même ferme dans la même instance
		static $ids = \selling\Item::model()
			->whereFarm($eFarm)
			->option('index-force', ['farm', 'deliveredAt'])
			->whereDeliveredAt('BETWEEN', new \Sql(\selling\Item::model()->format($search->get('from')).' AND '.\selling\Item::model()->format($search->get('to'))))
			->whereStatus(\selling\Sale::DELIVERED)
			->whereProduct('!=', NULL)
			->getColumn(new \Sql('DISTINCT product', 'int'));

		return \selling\Product::model()
			->whereId('IN', $ids)
			->whereStatus('!=', \selling\Product::DELETED);

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
	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search, int $nItemToCheck): array {

		$cCategories = \selling\CategoryLib::getByFarm($eFarm, index: 'id');

		$cProduct = self::filterForAccountingCheck($eFarm, $search)
			->select(['category', 'count' => new \Sql('COUNT(DISTINCT(m1.id))')])
			->group('category')
			->getCollection(NULL, NULL, 'category');
		$productsByCategory = [];
		foreach($cProduct as $eProduct) {
			$productsByCategory[$eProduct['category']['id'] ?? 0] = $eProduct['count'];
		}

		if(get_exists('tab')) {

			\session\SessionLib::set('preAccountingProductTab', GET('tab'));
			$tab = GET('tab', 'string', '');

		} else {

			try {
				$tab = \session\SessionLib::get('preAccountingProductTab');
			} catch(\Exception) {
				$tab = \selling\CategoryLib::getById(first(array_keys($productsByCategory)));
				\session\SessionLib::set('preAccountingProductTab', $tab);
			}

		}

		if($tab !== 'items' and empty($productsByCategory)) {
			$tab = 'items';
			\session\SessionLib::set('preAccountingProductTab', 'items');
		}

		if($tab === 'items' and $nItemToCheck > 0) {

			return [new \Collection(), $cCategories, $productsByCategory];

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

		return [$cProduct, $cCategories, $productsByCategory];

	}
	
}
