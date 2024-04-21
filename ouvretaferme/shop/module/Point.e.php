<?php
namespace shop;

class Point extends PointElement {

	public function isActive(): bool {

		return ($this['status'] === Point::ACTIVE);

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		if(array_intersect($properties, ['paymentCard', 'paymentOffline', 'paymentTransfer'])) {
			$properties[] = 'payment';
		}

		return parent::build($properties, $input, $callbacks + [

			'name.notNull' => function(?string $name) {
				return ($name !== NULL);
			},

			'place.notNull' => function(?string $place) {
				return ($place !== NULL);
			},

			'address.notNull' => function(?string $address) {
				return ($address !== NULL);
			},

			'zone.notNull' => function(?string $zone) {
				return ($zone !== NULL);
			},

			'payment.check' => function() {

				$this->expects(['paymentCard', 'paymentOffline', 'paymentTransfer']);

				return (
					$this['paymentCard'] !== FALSE or
					$this['paymentOffline'] !== FALSE or
					$this['paymentTransfer'] !== FALSE
				);

			}

		]);

	}

}
?>