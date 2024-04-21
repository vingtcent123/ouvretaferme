<?php
namespace shop;

class PointLib extends PointCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Point $e) {

			return match($e['type']) {
				Point::PLACE => ['name', 'description', 'place', 'address'],
				Point::HOME => ['zone']
			};

		};

	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Point $e) {

			$options = ['orderMin', 'shipping', 'shippingUntil', 'paymentOffline', 'paymentTransfer'];

			if($e['shop']['stripe']->notEmpty()) {
				$options[] = 'paymentCard';
			}

			return array_merge($options, match($e['type']) {
				Point::PLACE => ['name', 'description', 'place', 'address'],
				Point::HOME => ['zone']
			});

		};

	}

	public static function getSelected(Shop $eShop, \Collection $ccPoint, \selling\Customer $eCustomer, \selling\Sale $eSale): Point {

		$cPointPlace = ($ccPoint[Point::PLACE] ?? new \Collection());
		$cPointHome = ($ccPoint[Point::HOME] ?? new \Collection());

		if($eSale->notEmpty()) {
			return $ccPoint->find(fn($ePoint) => $ePoint['id'] === $eSale['shopPoint']['id'], depth: 2, limit: 1, default: new Point());
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

	public static function hasType(Shop $eShop, string $type): bool {

		return Point::model()
			->whereShop($eShop)
			->whereType($type)
			->whereStatus(Point::ACTIVE)
			->exists();

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

	public static function getByShop(Shop $eShop, bool $onlyActive = TRUE): \Collection {

		return Point::model()
			->select(Point::getSelection())
			->whereStatus(Point::ACTIVE, if: $onlyActive)
			->whereShop($eShop)
			->sort([
				'status' => SORT_DESC,
				'zone' => SORT_ASC,
				'name' => SORT_ASC,
				'type' => SORT_ASC,
				'place' => SORT_ASC
			])
			->getCollection(index: ['type', NULL]);

	}

	public static function create(Point $e): void {

		$e->expects(['shop', 'type']);

		try {
			Point::model()->insert($e);
		} catch(\DuplicateException) {
			Point::fail('name.duplicate');
		}

	}

	public static function deleteByShop(Shop $e): void {

		$points = Point::model()
			->whereShop($e)
			->getColumn('id');

		if($points) {

			\selling\Sale::model()
				->whereFarm($e['farm'])
				->whereShopPoint('IN', $points)
				->update([
					'shopPoint' => new Point()
				]);

			Point::model()
				->whereId('IN', $points)
				->delete();

		}

	}

	public static function delete(Point $ePoint): void {

		$ePoint->expects(['shop']);

		if(Date::model()
			->whereShop($ePoint['shop'])
			->where('JSON_CONTAINS(points, \''.$ePoint['id'].'\')')
			->exists()) {
			Point::fail('deletedDateUsed');
			return;
		}

		if(\selling\Sale::model()
			->whereShop($ePoint['shop'])
			->whereShopPoint($ePoint)
			->exists()) {
			Point::fail('deletedSaleUsed');
			return;
		}

		Point::model()->delete($ePoint);

	}

}
