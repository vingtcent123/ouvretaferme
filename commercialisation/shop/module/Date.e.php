<?php
namespace shop;

class Date extends DateElement {

	public static function getSelection(): array {
		return Date::model()->getProperties() + [
			'isOrderable' => new \Sql('(orderStartAt < NOW() OR orderStartAt IS NULL) AND (orderEndAt > NOW() OR orderEndAt IS NULL)', 'bool'),
			'isDeliverable' => new \Sql('deliveryDate = CURDATE()', 'bool'),
			'isSoonOpen' => new \Sql('orderStartAt > NOW()', 'bool'),
		];
	}

	public function getTaxes(): string {
		return \selling\CustomerUi::getTaxes($this['type']);
	}

	public function canRead(): bool {

		$this->expects(['shop']);
		return $this['shop']->canRead();

	}

	public function canCreate(): bool {

		$this->expects(['farm']);

		return $this['farm']->canManage();

	}

	public function canWrite(): bool {

		$this->expects(['farm', 'status']);

		return (
			$this['status'] !== Date::CLOSED and
			$this['farm']->canManage()
		);

	}

	public function acceptCreateSale(): bool {

		return (
			($this['deliveryDate'] === NULL or $this->isPast() === FALSE) and
			$this->acceptNotShared()
		);

	}

	public function acceptNotShared(): bool {

		$this->expects(['shop' => ['shared']]);

		return $this['shop']['shared'] === FALSE;

	}

	public function acceptSaleUpdatePreparationStatus(): bool {

		return (
			$this['deliveryDate'] === NULL or
			$this->acceptOrder() === FALSE
		);

	}

	public function acceptOrder(): bool {

		$this->expects(['status', 'orderStartAt', 'orderEndAt']);

		return (
			$this['status'] === Date::ACTIVE and
			($this['orderStartAt'] === NULL or $this['orderStartAt'] <= currentDatetime()) and
			($this['orderEndAt'] === NULL or currentDatetime() <= $this['orderEndAt'])
		);

	}

	public function acceptOrderSoon(): bool {

		$this->expects(['status', 'orderStartAt']);

		return (
			$this['status'] === Date::ACTIVE and
			currentDatetime() < $this['orderStartAt']
		);

	}

	public function isOrderSoonExpired(): bool {
		return (
			date('Y-m-d H:i:s', time() + 1800) > $this['orderEndAt'] and
			date('Y-m-d H:i:s') <= $this['orderEndAt']
		);
	}

	public function isExpired(): bool {
		return date('Y-m-d H:i:s') > $this['orderEndAt'];
	}

	public function isPast(): bool {
		return ($this['deliveryDate'] !== NULL and currentDate() > $this['deliveryDate']);
	}

	public function isCatalog(): bool {
		return $this['source'] === Date::CATALOG;
	}

	public function isDirect(): bool {
		return $this['source'] === Date::DIRECT;
	}

	public function acceptCustomerCancel(): bool {
		return ($this['orderEndAt'] !== NULL and $this['orderEndAt'] > currentDatetime());
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$fw = new \FailWatch();

		$p
			->setCallback('source.prepare', function(?string &$source): bool {

				if($this['shop']['shared']) {
					$source = \shop\Date::CATALOG;
				}

				return TRUE;

			})
			->setCallback('status.prepare', function(mixed &$status): bool {

				if(in_array($status, [Date::ACTIVE, Date::INACTIVE])) {
					return TRUE;
				}

				if($status) {
					$status = Date::ACTIVE;
				} else {
					$status = Date::INACTIVE;
				}

				return TRUE;

			})
			->setCallback('orderStartAt.prepare', function(mixed &$value) use ($p) {
				return match($p->for) {
					'create' => ($value !== NULL),
					'update' => TRUE
				};
			})
			// End of order must be after start of order.
			->setCallback('orderEndAt.prepare', function(mixed &$value) use ($p) {
				return match($p->for) {
					'create' => ($value !== NULL),
					'update' => TRUE
				};
			})
			->setCallback('orderEndAt.consistency', function(mixed $orderEndAt) use($p): bool {

				$p->expectsBuilt('orderStartAt');

				return (
					$this['orderStartAt'] === NULL or
					$orderEndAt === NULL or
					$orderEndAt > $this['orderStartAt']
				);
			})
			// Delivery must be after order.
			->setCallback('deliveryDate.prepare', function(mixed &$value) use ($p) {
				return ($value !== NULL);
			})
			->setCallback('deliveryDate.consistency', function($deliveryDate) use($p, $fw): bool {

				if(
					$p->isBuilt('orderEndAt') === FALSE or
					$fw->has('Date::deliveryDate.prepare') or
					$fw->has('Date::deliveryDate.check')
				) { // L'action génère déjà une erreur.
					return TRUE;
				}

				$this->expects(['orderEndAt']);

				return $deliveryDate >= substr($this['orderEndAt'], 0, 10);
			})
			->setCallback('catalogs.check', function(?array &$catalogs) use($input, $p) {

				$p->expectsBuilt('source');

				$this->expects(['farm', 'type']);

				if($this['source'] !== Date::CATALOG) {
					return TRUE;
				}

				if(
					$catalogs === NULL or
					count($catalogs) === 0
				) {
					return FALSE;
				}

				$cCatalog = \shop\CatalogLib::getForShop($this['shop'], $this['type'], onlyIds: $catalogs);

				if($cCatalog->count() !== count($catalogs)) {
					return FALSE;
				} else {
					$catalogs = $cCatalog->getIds();
					return TRUE;
				}

			})
			->setCallback('productsList.check', function() use($input, $p) {

				$p->expectsBuilt('source');

				if($this['source'] !== Date::DIRECT) {
					return TRUE;
				}

				$products = POST('products', 'array', []);
				$cProductSelling = \selling\ProductLib::getForSale($this['farm'], $this['shop']['type'], $products);

				$this['cProduct'] = ProductLib::prepareCollection($this, $cProductSelling, $products, $input);

				return TRUE;

			})
			->setCallback('points.check', function(mixed &$points) {

				$this->expects(['farm']);

				$points = Point::model()
					->whereId('IN', $points)
					->whereStatus(Point::ACTIVE)
					->whereFarm($this['farm'])
					->getColumn('id');

				return ($points !== []);

			});
		
		parent::build($properties, $input, $p);

	}
}
