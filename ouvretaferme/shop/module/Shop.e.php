<?php
namespace shop;

class Shop extends ShopElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'vignette', 'url', 'status'],
			'ccPoint' => Point::model()
				->select(Point::getSelection())
				->whereStatus(Point::ACTIVE)
				->sort([
					'zone' => SORT_ASC,
					'name' => SORT_ASC,
					'type' => SORT_ASC,
					'place' => SORT_ASC
				])
				->delegateCollection('shop', index: ['type', 'id'])
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

	public function getPayments(Point $ePoint): array {

		$payments = [];

		if($this['paymentCard']) {
			$payments[] = 'onlineCard';
		}
		if($this['paymentSepaDebit']) {
			$payments[] = 'onlineSepaDebit';
		}
		if(
			$ePoint['paymentOnlineOnly'] === FALSE and
			$this['paymentOnlineOnly'] === FALSE
		) {
			$payments[] = 'offline';
		}

		return $payments;

	}

	public function hasOnlinePayment(): bool {

		return (
			$this['paymentSepaDebit'] or
			$this['paymentCard']
		);

	}

	public function hasOfflinePayment(): array {

		return ($this['paymentOnlineOnly'] === FALSE);

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'farm.prepare' => function(?string $farm) use ($input) {

				$this['farm'] = \farm\FarmLib::getById($farm);

			},

			'farm.check' => function() use ($input) {

				return $this['farm']->notEmpty() and $this['farm']->canManage();

			},

			'fqn.prepare' => function() use ($for) {

				if($for === 'update') {
					$this['oldFqn'] = $this['fqn'];
				}

			},

			'paymentOnlineOnly.prepare' => function(bool &$paymentOnlineOnly) {

				$this->expects(['paymentCard']);

				if(
					$this['paymentCard'] === FALSE
				) {
					$paymentOnlineOnly = FALSE;
				}

			},

			'terms.check' => function(?string $terms) {

				return (
					$terms === NULL or
					mb_strlen(strip_tags($terms)) > 0
				);


			}

		]);

	}

}
?>