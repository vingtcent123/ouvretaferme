<?php
namespace selling;

class PreparationLib {

	public static function start(\farm\Farm $eFarm, string $input) {

		$ids = Sale::model()
			->whereFarm($eFarm)
			->whereId('IN', explode(',', $input))
			->wherePreparationStatus(Sale::CONFIRMED)
			->getColumn('id');

		\farm\FarmerLib::setView('viewSellingPreparing', $eFarm, $ids ?: NULL);

	}

	public static function getRemaining(Sale $eSale): int {

		return Item::model()
			->whereSale($eSale)
			->whereIngredientOf(NULL)
			->wherePrepared(FALSE)
			->count();

	}

	public static function getPreparing(\farm\Farm $eFarm, \selling\Sale $eSaleSelected): ?array {

		$ids = $eFarm->getView('viewSellingPreparing');

		// La vente n'est pas concernée par une préparation de commande
		if(
			 $ids === NULL or
			 in_array($eSaleSelected['id'], $ids) === FALSE
		) {
			return NULL;
		}

		$cSale = self::getSales($ids, $eSaleSelected);

		$eSaleBefore = new \selling\Sale();
		$position = 0;

		foreach($cSale as $eSale) {

			$position++;

			if($eSale['id'] === $eSaleSelected['id']) {

				$cSale->next();
				$eSaleAfter = $cSale->current() ?? new \selling\Sale();

				return [
					'count' => $cSale->count(),
					'position' => $position,
					'before' => $eSaleBefore,
					'after' => $eSaleAfter
				];

			}

			$eSaleBefore = $eSale;

		}

		return NULL;

	}

	public static function getSales(array $ids, Sale $eSaleSelected): \Collection {

		return Sale::model()
			->join(Customer::model(), 'm1.customer = m2.id')
			->select($select ?? Sale::getSelection())
			->where('m1.id', 'IN', $ids)
			->or(
				fn() => $this->where('m1.id', $eSaleSelected),
				fn() => $this->wherePreparationStatus(Sale::CONFIRMED)
			)
			->sort(new \Sql('shopPoint ASC, IF(lastName IS NULL, name, lastName), firstName, m1.id'))
			->getCollection(NULL, NULL, 'id');

	}

}
?>
