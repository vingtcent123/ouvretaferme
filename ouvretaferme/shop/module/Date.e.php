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

	public function canOrder(): bool {

		$this->expects(['status', 'orderStartAt', 'orderEndAt']);

		return (
			$this['status'] === Date::ACTIVE and
			$this['orderStartAt'] <= currentDatetime() and
			currentDatetime() <= $this['orderEndAt']
		);

	}

	public function canOrderSoon(): bool {

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

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$fw = new \FailWatch();

		return parent::build($properties, $input, $callbacks + [

			// End of order must be after start of order.
			'orderEndAt.consistency' => function($orderEndAt): bool {

				$this->expects(['orderStartAt']);

				return $orderEndAt > $this['orderStartAt'];
			},

			// Delivery must be after order.
			'deliveryDate.consistency' => function($deliveryDate) use ($fw): bool {

				if(
					$fw->has('Date::orderEndAt.consistency') or
					$fw->has('Date::orderEndAt.check') or
					$fw->has('Date::deliveryDate.check')
				) { // L'action génère déjà une erreur.
					return TRUE;
				}

				$this->expects(['orderEndAt']);

				return $deliveryDate >= substr($this['orderEndAt'], 0, 10);
			},

			'products.check' => function(mixed $products) use ($input) {

				$this->expects(['shop']);

				$cProductSelling = \selling\Product::model()
					->select(\selling\Product::getSelection())
					->whereId('IN', $products)
					->wherePrivate(TRUE)
					->whereStatus(\selling\Product::ACTIVE)
					->getCollection();

				$cProduct = new \Collection();
				$stocks = cast($input['stock'] ?? [], 'array');

				foreach($cProductSelling as $eProductSelling) {

					$stock = $stocks[$eProductSelling['id']] ?? '';

					$eProduct = new Product([
						'product' => $eProductSelling,
						'stock' => ($stock === '') ? NULL : (int)$stock,
						'shop' => $this['shop'],
					]);

					$cProduct->append($eProduct);

				}

				$this['cProduct'] = $cProduct;

				return $cProduct->notEmpty();

			},

			'points.check' => function(mixed &$points) {

				$this->expects(['shop']);

				$points = Point::model()
					->whereId('IN', $points)
					->whereStatus(Point::ACTIVE)
					->whereShop($this['shop'])
					->getColumn('id');

				return ($points !== []);

			},

		]);

	}
}