<?php
namespace shop;

class DateLib extends DateCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Date $eDate) {

			$eDate->expects([
				'shop' => ['hasPoint']
			]);

			$properties = [
				'orderStartAt', 'orderEndAt',
				'deliveryDate',
				'productsList',
			];

			if($eDate['shop']['hasPoint']) {
				$properties[] = 'points';
			}

			return $properties;

		};

	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Date $eDate) {

			$eDate->expects([
				'shop' => ['hasPoint']
			]);

			$properties = [
				'orderStartAt', 'orderEndAt',
				'deliveryDate',
			];

			if(
				$eDate['shop']['hasPoint'] and
				$eDate->isExpired() === FALSE
			) {
				$properties[] = 'points';
			}

			return $properties;

		};

	}

	public static function getByShop(Shop $eShop): \Collection {

		$cDate = Date::model()
			->select(Date::model()->getProperties())
			->select([
				'sales' => self::countSales()
					->group('shopDate')
					->delegateArray('shopDate', callback: fn($value) => $value ?? ['count' => 0, 'countValid' => 0, 'amount' => 0.0])

			])
			->whereShop($eShop)
			->sort(['deliveryDate' => SORT_DESC])
			->getCollection(NULL, NULL, 'id');

		return $cDate;

	}

	public static function applySales(Date $eDate): void {

		$sales = self::countSales()
			->whereShopDate($eDate)
			->get()
			->getArrayCopy();

		if($sales['count'] === 0) {
			$sales = ['count' => 0, 'countValid' => 0, 'amount' => 0.0];
		}

		$eDate['sales'] = $sales;

	}

	public static function countSales(): \selling\SaleModel {

		return \selling\Sale::model()
				->select([
					'count' => new \Sql('COUNT(*)', 'int'),
					'countValid' => new \Sql('SUM(preparationStatus != \''.\selling\Sale::BASKET.'\')', 'int'),
					'amountIncludingVat' => new \Sql('SUM(priceIncludingVat)'),
					'amountValidIncludingVat' => new \Sql('SUM(IF(preparationStatus != \''.\selling\Sale::BASKET.'\', priceIncludingVat, 0))'),
					'amountValidExcludingVat' => new \Sql('SUM(IF(preparationStatus != \''.\selling\Sale::BASKET.'\', priceExcludingVat, 0))'),
				])
				->whereFrom(\selling\Sale::SHOP)
				->wherePreparationStatus('in', [\selling\Sale::BASKET, \selling\Sale::CONFIRMED, \selling\Sale::PREPARED, \selling\Sale::DELIVERED]);

	}

	public static function getRelativeSales(Date $e, \selling\Sale $eSaleSelected): ?array {

		$cSale = \selling\SaleLib::getByDate($e, select: [
			'id', 'document',
			'customer' => ['type', 'name']
		]);

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

	public static function create(Date $e): void {

		$e->expects(['cProduct']);

		Date::model()->beginTransaction();

		Date::model()->insert($e);

		foreach($e['cProduct'] as $eProduct) {
			$eProduct['date'] = $e;
		}

		ProductLib::createCollection($e['cProduct']);

		Shop::model()->commit();

	}

	public static function updatePoint(Date $eDate, Point $ePoint, bool $status): void {

		$points = $eDate['points'];

		if($status) {
			if(in_array($ePoint['id'], $points) === FALSE) {
				$points[] = $ePoint['id'];
			}
		} else {
			array_delete($points, $ePoint['id']);
		}

		if($points === []) {
			Date::fail('points.check');
			return;
		}

		if($points !== $eDate['points']) {

			Date::model()->update($eDate, [
				'points' => $points,
			]);

		}

	}

	public static function delete(Date $eDate): void {

		if(\selling\Sale::model()
			->whereFrom(\selling\Sale::SHOP)
			->wherePreparationStatus('in', [\selling\Sale::BASKET, \selling\Sale::CONFIRMED, \selling\Sale::PREPARED, \selling\Sale::DELIVERED])
			->whereShopDate($eDate)
			->exists()) {
			throw new \NotExpectedAction('Existing sales.');
		}

		Date::model()->beginTransaction();

		if($eDate->isDirect()) {

			Product::model()
				->whereDate($eDate)
				->delete();

		}

		$cSale = \selling\SaleLib::getByDate($eDate, NULL);

		if($cSale->notEmpty()) {

			\selling\Sale::model()
				->whereId('IN', $cSale)
				->update([
					'shop' => new Shop(),
					'shopDate' => new Date(),
				]);

			\selling\Item::model()
				->whereSale('IN', $cSale)
				->update([
					'shop' => new Shop(),
					'shopDate' => new Date(),
				]);

		}

		Date::model()->delete($eDate);

		Date::model()->commit();

	}

	public static function getMostRelevantByShop(Shop $eShop, bool $one = FALSE, bool $withSales = FALSE): Date|\Collection {

		$select = Date::getSelection();

		if($withSales) {

			$select['sales'] = self::countSales()
				->group('shopDate')
				->delegateArray('shopDate', callback: fn($value) => $value ?? ['count' => 0, 'countValid' => 0, 'amount' => 0.0]);

		}

		$cDate = Date::model()
			->select($select)
			->whereShop($eShop)
			->whereStatus(Date::ACTIVE)
			->whereDeliveryDate('>',  new \Sql('NOW()'))
			->whereProducts('>', 0)
			->sort([
				'isOrderable' => SORT_DESC,
				'deliveryDate' => SORT_ASC
			])
			->getCollection();

		// Pas de date ouverte ou à venir : chercher la dernière
		if($cDate->empty()) {

			$eDatePast = Date::model()
				->select($select)
				->whereShop($eShop)
				->whereDeliveryDate('<',  new \Sql('NOW()'))
				->sort(['deliveryDate' => SORT_DESC])
				->get();

			if($eDatePast->notEmpty()) {
				$cDate[$eDatePast['id']] = $eDatePast;
			}

		}

		if($one) {
			return $cDate->empty() ? new Date() : $cDate->first();
		} else {
			if($cDate->empty()) {
				return new \Collection();
			} else {
				return $cDate->count() > 8 ? $cDate->slice(0, 8) : $cDate;
			}
		}

	}

	public static function recalculate(Date $e): void {

		$e['products'] = ProductLib::countByDate($e);

		Date::model()
			->select('products')
			->update($e);

	}

}
