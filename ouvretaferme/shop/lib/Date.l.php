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

		$isValid = 'preparationStatus IN (\''.\selling\Sale::CONFIRMED.'\', \''.\selling\Sale::PREPARED.'\', \''.\selling\Sale::DELIVERED.'\')';

		return \selling\Sale::model()
				->select([
					'count' => new \Sql('COUNT(*)', 'int'),
					'countValid' => new \Sql('SUM('.$isValid.')', 'int'),
					'amountIncludingVat' => new \Sql('SUM(priceIncludingVat)'),
					'amountValidIncludingVat' => new \Sql('SUM(IF('.$isValid.', priceIncludingVat, 0))'),
					'amountValidExcludingVat' => new \Sql('SUM(IF('.$isValid.', priceExcludingVat, 0))'),
				]);

	}

	public static function getRelativeSales(Date $e, \selling\Sale $eSaleSelected): ?array {

		$cSale = \selling\SaleLib::getByDate($e, [\selling\Sale::CONFIRMED, \selling\Sale::PREPARED, \selling\Sale::DELIVERED], eFarm: $eSaleSelected['farm'], select: [
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
				'catalogs' => array_merge($catalogs),
			]);

		}

	}

	public static function deleteCatalogsByShop(Shop $eShop, \Collection $cCatalog): void {

		foreach($cCatalog as $eCatalog) {

			Date::model()
				->whereShop($eShop)
				->where('JSON_CONTAINS(catalogs, \''.$eCatalog['id'].'\')')
				->update([
					'catalogs' => new \Sql(\series\Task::model()->pdo()->api->jsonRemove('catalogs', $eCatalog['id']))
				]);

		}

	}

	public static function delete(Date $eDate): void {

		if(\selling\Sale::model()
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
			->whereDeliveryDate('>=', new \Sql('CURRENT_DATE'))
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
				->whereDeliveryDate('<',  new \Sql('CURRENT_DATE'))
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

	public static function end(): void {

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

			if($eDate['shop']['shared']) {

				self::sendEndEmail($eDate, new \farm\Farm(), $eDate['shop']['email']);

				$cShare = \shop\ShareLib::getByShop($eDate['shop']);

				foreach($cShare as $eShare) {

					$to = $eShare['farm']->selling()['legalEmail'];
					self::sendEndEmail($eDate, $eShare['farm'], $to);

				}

			} else {

				$to = $eDate['shop']['email'] ?? $eDate['farm']->selling()['legalEmail'];
				self::sendEndEmail($eDate, $eDate['farm'], $to);

			}

		}

	}

	public static function sendEndEmail(Date $eDate, \farm\Farm $eFarm, string $to): void {

			$sales = \selling\Sale::model()
				->select([
					'number' => new \Sql('COUNT(*)', 'int'),
					'priceExcludingVat' => new \Sql('SUM(priceExcludingVat)', 'float'),
					'priceIncludingVat' => new \Sql('SUM(priceIncludingVat)', 'float')
				])
				->whereShopDate($eDate)
				->whereFarm($eFarm, if: $eFarm->notEmpty())
				->wherePreparationStatus('IN', [\selling\Sale::CONFIRMED, \selling\Sale::PREPARED, \selling\Sale::DELIVERED])
				->get()
				->getArrayCopy();

			$cItem = \selling\ItemLib::getSummaryByDate($eFarm, $eDate);

			new \mail\MailLib()
				->addTo($to)
				->setContent(...MailUi::getOrderEnd($eDate, $sales, $cItem))
				->send('user');

	}

	public static function finish(): void {

		$cDate = Date::model()
			->select(Date::getSelection() + [
				'shop' => ['shared']
			])
			->whereDeliveryDate('<', new \Sql('CURRENT_DATE'))
			->whereStatus('!=', Date::CLOSED)
			->getCollection();

		foreach($cDate as $eDate) {

			Date::model()->beginTransaction();

				$affected = Date::model()
					->whereStatus('!=', Date::CLOSED)
					->update($eDate, [
						'status' => Date::CLOSED
					]);

				if($affected > 0) {

					$cSale = \selling\Sale::model()
						->select(\selling\Sale::getSelection())
						->whereShopDate($eDate)
						->wherePreparationStatus(\selling\Sale::BASKET)
						->getCollection();

					\selling\SaleLib::updatePreparationStatusCollection($cSale, \selling\Sale::CANCELED);

				}

			Date::model()->commit();

		}

	}

}
