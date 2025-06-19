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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		if(array_intersect($properties, ['paymentCard', 'paymentOffline', 'paymentTransfer'])) {
			$properties[] = 'payment';
		}

		$p
			->setCallback('name.notNull', function(?string $name) {
				return ($name !== NULL);
			})
			->setCallback('place.notNull', function(?string $place) {
				return ($place !== NULL);
			})
			->setCallback('address.notNull', function(?string $address) {
				return ($address !== NULL);
			})
			->setCallback('zone.notNull', function(?string $zone) {
				return ($zone !== NULL);
			})
			->setCallback('payment.check', function() {

				$this->expects(['paymentCard', 'paymentOffline', 'paymentTransfer']);

				return (
					$this['paymentCard'] !== FALSE or
					$this['paymentOffline'] !== FALSE or
					$this['paymentTransfer'] !== FALSE
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>