<?php
namespace shop;

class Shop extends ShopElement {

	public static function isEmbed(): bool {
		return get_exists('embed');
	}

	public function validateEmbed(): void {

		$this->expects(['embedOnly', 'embedUrl']);

		if(
			$this['embedOnly'] and
			self::isEmbed() === FALSE
		) {
			throw new \RedirectAction($this['embedUrl']);
		}

	}

	public function validateFrequency(): self {

		if($this['opening'] !== \shop\Shop::FREQUENCY) {
			throw new \NotAllowedAction();
		}

		return $this;

	}

	public function validateShare(\farm\Farm $eFarm, string $validatePersonal = 'canRead', string $validateShared = 'canRead'): self {

		if($this->canShare($eFarm, $validatePersonal, $validateShared) === FALSE) {
			throw new \NotAllowedAction();
		}

		return $this;

	}

	public function canShare(\farm\Farm $eFarm, string $validatePersonal = 'canRead', string $validateShared = 'canRead'): bool {

		if(
			$this->empty() or
			$this['shared'] === FALSE
		) {
			return $this->$validatePersonal();
		} else {

			$this->expects(['shared']);

			return (
				($validateShared === 'canRead' and ($this->canRead() or $this->canShareRead($eFarm))) or
				($validateShared === 'canWrite' and $this->canWrite())
			);

		}

	}

	public function isApproximate(): bool {

		$this->expects(['approximate', 'paymentCard']);

		return (
			$this['approximate'] and
			$this['paymentCard'] === FALSE
		);


	}

	public function isSharedAlways(): bool {
		return ($this->isShared() and $this['opening'] === Shop::ALWAYS);
	}

	public function isShared(): bool {

		$this->expects(['shared']);
		return $this['shared'];

	}

	public function isPersonal(): bool {

		$this->expects(['shared']);
		return $this['shared'] === FALSE;

	}

	public function isOpen(): bool {

		$this->expects(['status']);
		return $this['status'] === Shop::OPEN;

	}

	public function isClosed(): bool {

		$this->expects(['status']);
		return $this['status'] === Shop::CLOSED;

	}

	public function isDeleted(): bool {

		$this->expects(['status']);
		return $this['status'] === Shop::DELETED;

	}

	public function canShareRead(\farm\Farm $e): bool {

		return (
			$e->canWrite() and
			\shop\ShareLib::match($this, $e)
		);

	}

	public function canCustomerRead(\selling\Customer $e): bool {

		$this->expects(['limitCustomers']);

		return (
			($this['limitCustomers'] === [] and $this['limitGroups'] === []) or
			$this->canRead() or
			($e->notEmpty() and in_array($e['id'], $this['limitCustomers'])) or
			($e->notEmpty() and array_intersect($e['groups'], $this['limitGroups']))
		);

	}

	public function canRead(): bool {

		$this->expects(['farm', 'shared']);
		return $this['farm']->canSelling();

	}

	public function canCreate(): bool {

		$this->expects(['farm']);

		return (
			$this['farm']->canManage()
		);

	}

	public function canWrite(): bool {

		$this->expects(['farm', 'status']);

		return (
			$this['farm']->canManage() and
			$this['status'] !== Shop::DELETED
		);

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

	public function hasSharedKey(): bool {
		return ($this['sharedHash'] !== NULL);
	}

	public function getSharedKey(): string {

		$this->expects(['id', 'sharedHash']);

		return $this['id'].'-'.$this['sharedHash'];

	}

	public function isSharedKeyExpired(): bool {

		if($this['sharedHashExpiresAt'] === NULL) {
			return FALSE;
		} else {
			return $this['sharedHashExpiresAt'] < currentDate();
		}

	}

	public function getNewSharedHash(): string {
		return bin2hex(random_bytes(6));
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		if(array_intersect($properties, ['paymentCard', 'paymentOffline', 'paymentTransfer'])) {
			$properties[] = 'payment';
		}

		$p
			->setCallback('farm.prepare', function(?string $farm) use($input) {

				$this['farm'] = \farm\FarmLib::getById($farm);

			})
			->setCallback('farm.check', function() use($input) {

				return $this['farm']->notEmpty() and $this['farm']->canManage();

			})
			->setCallback('fqn.prepare', function() use($p) {

				if($p->for === 'update') {
					$this['oldFqn'] = $this['fqn'];
				}

			})
			->setCallback('email.empty', function(?string $email) {

				if($this['shared']) {
					return ($email !== NULL);
				} else {
					return TRUE;
				}


			})
			->setCallback('terms.check', function(?string $terms) {

				return (
					$terms === NULL or
					mb_strlen(strip_tags($terms)) > 0
				);

			})
			->setCallback('commentCaption.prepare', function(?string &$caption) use($p) {

				if($p->isBuilt('comment') === FALSE) {
					return TRUE;
				}

				if($this['comment'] === FALSE) {
					$caption = NULL;
				}

			})
			->setCallback('payment.check', function() {

				$this->expects(['paymentCard', 'paymentOffline', 'paymentTransfer']);

				return (
					$this['paymentCard'] or
					$this['paymentOffline'] or
					$this['paymentTransfer']
				);

			})
			->setCallback('openingFrequency.cast', function(mixed &$value) use ($p){

				if(
					$p->isBuilt('opening') === FALSE or
					$this['opening'] !== Shop::FREQUENCY
				) {
					$value = NULL;
				}

				return TRUE;

			})
			->setCallback('openingDelivery.cast', function(mixed &$value) use ($p) {

				if(
					$p->isBuilt('opening') === FALSE or
					$this['opening'] !== Shop::ALWAYS
				) {
					$value = NULL;
				}

				return TRUE;

			})
			->setCallback('paymentMethod.check', function(\payment\Method $eMethod): bool {

				if($eMethod->empty()) {
					return TRUE;
				}

				$this->expects(['farm', 'hasPayment']);

				if($this['hasPayment']) {
					return FALSE;
				}

				return \payment\MethodLib::isSelectable($this['farm'], $eMethod);

			})
			->setCallback('limitCustomers.prepare', fn(mixed &$customers) => \selling\CustomerLib::buildCollection($this, $customers, FALSE))
			->setCallback('limitGroups.prepare', fn(mixed &$groups) => \selling\CustomerGroupLib::buildCollection($this, $groups, TRUE))
			->setCallback('customColor.light', function(?string $color): bool {

				if($color === NULL) {
					return TRUE;
				}

				$average = array_sum([
					hexdec(substr($color, 1, 2)),
					hexdec(substr($color, 3, 2)),
					hexdec(substr($color, 5, 2)),
				]) / 3;

				return ($average < 127);

			})
			->setCallback('customBackground.light', function(?string $color): bool {

				if($color === NULL) {
					return TRUE;
				}

				$average = array_sum([
					hexdec(substr($color, 1, 2)),
					hexdec(substr($color, 3, 2)),
					hexdec(substr($color, 5, 2)),
				]) / 3;

				return ($average > 225);

			})
			->setCallback('customFont.check', function(?string $customFont): bool {
				return $customFont === NULL or \website\DesignLib::isCustomFont($customFont, \website\WebsiteSetting::CUSTOM_FONTS);
			})
			->setCallback('customTitleFont.check', function(?string $customFont): bool {
				return $customFont === NULL or \website\DesignLib::isCustomFont($customFont, \website\WebsiteSetting::CUSTOM_TITLE_FONTS);
			})
			->setCallback('shopShare.empty', function(?string $value): bool {
				return ($value !== NULL);
			});
	
		parent::build($properties, $input, $p);

	}

}
?>
