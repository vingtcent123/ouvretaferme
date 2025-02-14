<?php
namespace shop;

class Shop extends ShopElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
		];

	}

	public function isOpen(): bool {

		$this->expects(['status']);
		return $this['status'] === Shop::OPEN;

	}

	public function isClosed(): bool {

		$this->expects(['status']);
		return $this['status'] === Shop::CLOSED;

	}

	public function canAccess(\selling\Customer $e): bool {

		$this->expects(['limitCustomers']);

		return (
			$this['limitCustomers'] === [] or
			$this->canRead() or
			($e->notEmpty() and in_array($e['id'], $this['limitCustomers']))
		);

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function countPayments(): int {

		if($this['hasPayment']) {

			return (
				(int)$this['paymentCard'] +
				(int)$this['paymentTransfer'] +
				(int)$this['paymentOffline']
			);

		} else {
			return 0;
		}

	}

	public function getPayments(Point $ePoint): array {

		$payments = [];

		if($ePoint->empty()) {

			if($this['paymentCard']) {
				$payments[] = 'onlineCard';
			}
			if($this['paymentTransfer']) {
				$payments[] = 'transfer';
			}
			if($this['paymentOffline']) {
				$payments[] = 'offline';
			}

		} else {

			if(
				$ePoint['paymentCard'] or
				($ePoint['paymentCard'] === NULL and $this['paymentCard'])
			) {
				$payments[] = 'onlineCard';
			}
			if(
				$ePoint['paymentTransfer'] or
				($ePoint['paymentTransfer'] === NULL and $this['paymentTransfer'])
			) {
				$payments[] = 'transfer';
			}
			if(
				$ePoint['paymentOffline'] or
				($ePoint['paymentOffline'] === NULL and $this['paymentOffline'])
			) {
				$payments[] = 'offline';
			}

		}

		return $payments;

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		if(array_intersect($properties, ['paymentCard', 'paymentOffline', 'paymentTransfer'])) {
			$properties[] = 'payment';
		}

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

			'terms.check' => function(?string $terms) {

				return (
					$terms === NULL or
					mb_strlen(strip_tags($terms)) > 0
				);

			},

			'payment.check' => function() {

				$this->expects(['paymentCard', 'paymentOffline', 'paymentTransfer']);

				return (
					$this['paymentCard'] or
					$this['paymentOffline'] or
					$this['paymentTransfer']
				);

			},

			'limitCustomers.prepare' => function(mixed &$customers): bool {

				$this->expects(['farm']);

				$customers = (array)($customers ?? []);

				$customers = \selling\Customer::model()
					->select('id')
					->whereId('IN', $customers)
					->whereFarm($this['farm'])
					->getColumn('id');

				return TRUE;

			}

		]);

	}

}
?>