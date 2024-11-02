<?php
namespace shop;

class PointLib extends PointCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Point $e) {

			return match($e['type']) {
				Point::PLACE => ['name', 'description', 'place', 'address'],
				Point::HOME => ['name', 'zone']
			};

		};

	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Point $e) {

			$options = ['orderMin', 'shipping', 'shippingUntil', 'paymentOffline', 'paymentTransfer'];

			if($e['stripe']->notEmpty()) {
				$options[] = 'paymentCard';
			}

			return array_merge($options, match($e['type']) {
				Point::PLACE => ['name', 'description', 'place', 'address'],
				Point::HOME => ['name', 'zone']
			});

		};

	}

	public static function getSelected(Shop $eShop, \Collection $ccPoint, \selling\Customer $eCustomer, \selling\Sale $eSale): Point {

		$cPointPlace = ($ccPoint[Point::PLACE] ?? new \Collection());
		$cPointHome = ($ccPoint[Point::HOME] ?? new \Collection());

		if($eSale->notEmpty()) {
			return $ccPoint->find(fn($ePoint) => $eSale['shopPoint']->notEmpty() and $ePoint['id'] === $eSale['shopPoint']['id'], depth: 2, limit: 1, default: new Point());
		} else {

			// On tente de sélectionner le point choisi lors de la précédente commande sur la même boutique
			$ePointLast = $eCustomer->notEmpty() ? \selling\Sale::model()
				->whereShop($eShop)
				->whereCustomer($eCustomer)
				->where('deliveredAt BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()')
				->sort(['deliveredAt' => SORT_DESC])
				->getValue('shopPoint', new Point()) : new Point();

			if($ePointLast->notEmpty()) {

				try {
					return $ccPoint->find(fn($ePoint) => $ePoint['id'] === $ePointLast['id'], depth: 2, limit: 1, default: fn() => throw new \Exception());
				} catch(\Exception) {
					// Pas de point trouvé
				}

			}

			if($cPointPlace->count() === 1 and $cPointHome->empty()) {
				return $cPointPlace->first();
			} else if($cPointHome->count() === 1 and $cPointPlace->empty()) {
				return $cPointHome->first();
			} else {
				return new Point();
			}

		}

	}

	public static function getByDate(Date $eDate): \Collection {

		$eDate->expects(['points']);

		return Point::model()
			->select(Point::getSelection())
			->whereId('IN', $eDate['points'])
			->sort([
				'zone' => SORT_ASC,
				'name' => SORT_ASC,
				'type' => SORT_ASC,
				'place' => SORT_ASC
			])
			->getCollection(index: ['type', NULL]);

	}

	public static function getByFarm(\farm\Farm $eFarm): \Collection {

		return Point::model()
			->select(Point::getSelection())
			->whereStatus(Point::ACTIVE)
			->whereFarm($eFarm)
			->sort([
				'status' => SORT_ASC,
				'zone' => SORT_ASC,
				'name' => SORT_ASC,
				'type' => SORT_ASC,
				'place' => SORT_ASC
			])
			->getCollection(index: ['type', NULL]);

	}

	public static function getUsedByFarm(\farm\Farm $eFarm): array {

		$cDate = Date::model()
			->select(['shop', 'points'])
			->whereFarm($eFarm)
			->whereDeliveryDate('>', new \Sql('NOW() - INTERVAL 1 MONTH'))
			->getCollection();

		$points = [];

		foreach($cDate as $eDate) {

			$eShop = $eDate['shop'];

			foreach($eDate['points'] as $point) {
				$points[$point] ??= [];
				$points[$point][] = $eShop['id'];
			}

		}

		array_walk($points, fn(&$value) => $value = array_unique($value));

		return $points;

	}

	public static function create(Point $e): void {

		$e->expects(['farm', 'type']);

		try {
			Point::model()->insert($e);
		} catch(\DuplicateException) {
			Point::fail('name.duplicate');
		}

	}

	public static function delete(Point $ePoint): void {

		$ePoint->expects(['farm']);

		if(
			// Une date a déjà été créée avec ce point
			Date::model()
				->whereFarm($ePoint['farm'])
				->where('JSON_CONTAINS(points, \''.$ePoint['id'].'\')')
				->exists() or
			// Une vente a déjà été créée avec ce point
			\selling\Sale::model()
				->whereFarm($ePoint['farm'])
				->whereShopPoint($ePoint)
				->exists()
		) {

			Point::model()->update($ePoint, [
				'status' => Point::DELETED,
			]);

		} else {
			Point::model()->delete($ePoint);
		}


	}

}
