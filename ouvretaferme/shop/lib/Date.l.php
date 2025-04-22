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
				'source',
				'catalogs',
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
				'deliveryDate', 'description',
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

		switch($e['source']) {

			case Date::DIRECT :

				$e->expects(['cProduct']);

				$e['catalogs'] = NULL;

				break;

		}

		Date::model()->beginTransaction();

			Date::model()->insert($e);

			if($e['source'] === Date::DIRECT) {

				foreach($e['cProduct'] as $eProduct) {
					$eProduct['date'] = $e;
					$eProduct['type'] = $e['type'];
					$eProduct['farm'] = $e['farm'];
				}

				ProductLib::createCollection($e, $e['cProduct']);

			}

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

	public static function updateCatalog(Date $eDate, Catalog $eCatalog, bool $status): void {

		$catalogs = $eDate['catalogs'];

		if($status) {
			if(in_array($eCatalog['id'], $catalogs) === FALSE) {
				$catalogs[] = $eCatalog['id'];
			}
		} else {
			array_delete($catalogs, $eCatalog['id']);
		}

		if($catalogs !== $eDate['catalogs']) {

			Date::model()->update($eDate, [
				'catalogs' => $catalogs,
			]);

		}

	}

	public static function delete(Date $eDate): void {

		if(\selling\Sale::model()
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
			->whereDeliveryDate('>=', new \Sql('NOW()'))
			->sort([
				'isOrderable' => SORT_DESC,
				'deliveryDate' => SORT_ASC
			])
			->getCollection(index: 'id');

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
				return $cDate->count() > 8 ? $cDate->slice(0, 8, preserveKeys: TRUE) : $cDate;
			}
		}

	}

	public static function getFutureByShop(Shop $eShop): Date|\Collection {

		return Date::model()
			->select(Date::getSelection())
			->whereShop($eShop)
			->whereStatus(Date::ACTIVE)
			->where('orderEndAt > NOW()')
			->sort([
				'deliveryDate' => SORT_ASC
			])
			->getCollection(index: 'id');

	}

	public static function sendEndEmail(): void {

		$cDate = Date::model()
			->select(Date::getSelection() + [
				'shop' => ['name', 'shared', 'email', 'emailEndDate'],
				'farm' => ['banner']
			])
			->where('orderEndAt BETWEEN NOW() - INTERVAL 6 HOUR AND NOW() - INTERVAL 10 MINUTE')
			->where('orderEndAt != orderEndEmailedAt OR orderEndEmailedAt IS NULL')
			->getCollection();

		foreach($cDate as $eDate) {

			if(
				$eDate['shop']['emailEndDate'] === FALSE or
				Date::model()->update($eDate, [
					'orderEndEmailedAt' => $eDate['orderEndAt']
				]) === 0
			) {
				continue;
			}

			$sales = \selling\Sale::model()
				->select([
					'number' => new \Sql('COUNT(*)', 'int'),
					'priceExcludingVat' => new \Sql('SUM(priceExcludingVat)', 'float'),
					'priceIncludingVat' => new \Sql('SUM(priceIncludingVat)', 'float')
				])
				->whereShopDate($eDate)
				->wherePreparationStatus('IN', [\selling\Sale::CONFIRMED, \selling\Sale::PREPARED, \selling\Sale::DELIVERED])
				->get()
				->getArrayCopy();

			$cItem = \selling\ItemLib::getSummaryByDate($eDate);

			$email = $eDate['shop']['email'] ?? $eDate['farm']->selling()['legalEmail'];

			new \mail\MailLib()
				->addTo($email)
				->setContent(...MailUi::getOrderEnd($eDate, $sales, $cItem))
				->send('user');

		}

	}

}
