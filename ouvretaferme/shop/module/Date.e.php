<?php
namespace shop;

class Date extends DateElement {

	public static function getSelection(): array {
		return Date::model()->getProperties() + [
			'isOrderable' => new \Sql('orderStartAt < NOW() and orderEndAt > NOW()', 'bool'),
			'isDeliverable' => new \Sql('deliveryDate = CURDATE()', 'bool'),
			'isSoonOpen' => new \Sql('orderStartAt > NOW()', 'bool'),
		];
	}

	public function getTaxes(): string {
		return \selling\CustomerUi::getTaxes($this['type']);
	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function canRemote(): bool {
		return $this->canRead() or GET('key') === \Setting::get('selling\remoteKey');
	}

	public function acceptUserCreateSale(): bool {

		$this->expects(['shop' => ['shared']]);

		return $this['shop']['shared'] === FALSE;

	}

	public function acceptOrder(): bool {

		$this->expects(['status', 'orderStartAt', 'orderEndAt']);

		return (
			$this['status'] === Date::ACTIVE and
			$this['orderStartAt'] <= currentDatetime() and
			currentDatetime() <= $this['orderEndAt']
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

	public function isCatalog(): bool {
		return $this['source'] === Date::CATALOG;
	}

	public function isDirect(): bool {
		return $this['source'] === Date::DIRECT;
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

				if(in_array($status, [Date::ACTIVE, Date::CLOSED])) {
					return TRUE;
				}

				if($status) {
					$status = Date::ACTIVE;
				} else {
					$status = Date::CLOSED;
				}

				return TRUE;

			})
			// End of order must be after start of order.
			->setCallback('orderEndAt.consistency', function($orderEndAt) use($p): bool {

				$p->expectsBuilt('orderStartAt');

				return $orderEndAt > $this['orderStartAt'];
			})
			// Delivery must be after order.
			->setCallback('deliveryDate.consistency', function($deliveryDate) use($fw): bool {

				if(
					$fw->has('Date::orderEndAt.consistency') or
					$fw->has('Date::orderEndAt.check') or
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