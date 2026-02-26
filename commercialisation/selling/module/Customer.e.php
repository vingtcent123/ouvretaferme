<?php
namespace selling;

class Customer extends CustomerElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => \farm\FarmElement::getSelection(),
			'user' => ['email'],
			'cGroup?' => fn($e) => fn() => \selling\CustomerGroupLib::askCollection($e['groups'], $e['farm']),
		];

	}

	public function getLegalName(): string {
		return ($this['legalName'] ?? $this['name']);
	}

	public function getName(): ?string {

		if($this->empty()) {
			return s("Anonyme");
		} else {
			return $this['name'];
		}

	}

	public function getFullElectronicAddress(): ?string {

		if($this['electronicScheme'] and $this['electronicAddress']) {
			return $this['electronicScheme'].':'.$this['electronicAddress'];
		}

		return NULL;

	}

	public function calculateNumber(): ?string {

		return SellingSetting::CUSTOMER.$this['document'];

	}

	public function getCategory(): string {

		$this->expects(['type', 'destination']);

		if($this['destination'] === Customer::COLLECTIVE) {
			return Customer::COLLECTIVE;
		} else {
			return $this['type'];
		}

	}

	public function getTextCategory(bool $short = FALSE): string {

		return match($this->getCategory()) {
			Customer::COLLECTIVE => $short ? s("Point de vente") : s("Point de vente pour les particuliers"),
			Customer::PRIVATE => $short ? s("Particulier") : s("Client particulier"),
			Customer::PRO => $short ? s("Professionnel") : s("Client professionnel")
		};

	}

	public function acceptInvoice(): bool {

		return (
			$this->notEmpty() and
			$this['destination'] !== Customer::COLLECTIVE
		);

	}
	public function acceptCreateElectronicInvoice(): bool {

		$this->expects(['type']);

		if($this['type'] !== Customer::PRO) {
			return TRUE;
		}

		$this->expects(['vatNumber', 'siret', 'electronicAddress']);

		return (
			$this->hasInvoiceAddress() and
			$this['vatNumber'] !== NULL and
			$this['siret'] !== NULL and
			$this['electronicAddress'] !== NULL
		);

	}

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canManage(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function canDelete(): bool {
		return $this->canManage();
	}

	public static function validateCreateSale(\Collection $cCustomer, \farm\Farm $eFarm): void {

		if($cCustomer->empty()) {
			return;
		}

		$cCustomer->validateProperty('farm', $eFarm);

		$type = $cCustomer->first()['type'];

		foreach($cCustomer as $eCustomer) {

			if(
				$cCustomer->count() > 1 and
				$eCustomer->isCollective()
			) {
				throw new \FailAction('selling\Sale::customer.typeCollective');
			}

			if($eCustomer['type'] !== $type) {
				throw new \FailAction('selling\Sale::customer.typeConsistency');
			}

		}

	}

	public function hasVatCountry(): bool {

		return (
			$this->isPro() and
			$this['invoiceCountry']->notEmpty()
		);

	}

	public function isFR(): bool {

		return (
			$this->isPro() and
			$this['invoiceCountry']->isFR() or
			($this['invoiceCountry']->empty() and $this['deliveryCountry']->isFR())
		);

	}

	public function isBE(): bool {

		return (
			$this->isPro() and
			$this['invoiceCountry']->isBE() or
			($this['invoiceCountry']->empty() and $this['deliveryCountry']->isBE())
		);

	}

	public function isPro(): bool {
		$this->expects(['type']);
		return $this['type'] === Customer::PRO;
	}

	public function isPrivate(): bool {
		$this->expects(['type']);
		return $this['type'] === Customer::PRIVATE;
	}

	public function isIndividual(): bool {
		$this->expects(['destination']);
		return $this['destination'] === Customer::INDIVIDUAL;
	}

	public function isCollective(): bool {
		$this->expects(['destination']);
		return $this['destination'] === Customer::COLLECTIVE;
	}

	public function hasAddress(): bool {
		return (
			$this->hasInvoiceAddress() or
			$this->hasDeliveryAddress()
		);
	}

	public function getBestInvoiceAddress(string $type = 'text'): ?string {

		if($this->hasInvoiceAddress()) {
			return $this->getInvoiceAddress($type);
		} else if($this->hasDeliveryAddress()) {
			return $this->getDeliveryAddress($type);
		} else {
			return NULL;
		}

	}

	public function hasInvoiceAddress(): bool {
		return ($this->exists() and $this['invoiceCity'] !== NULL);
	}

	public function getInvoiceAddress(string $type = 'text'): ?string {

		if($this->hasInvoiceAddress() === FALSE) {
			return NULL;
		}

		$address = $this['invoiceStreet1']."\n";
		if($this['invoiceStreet2'] !== NULL) {
			$address .= $this['invoiceStreet2']."\n";
		}

		$address .= $this['invoicePostcode'].' '.$this['invoiceCity'];

		if($this['invoiceCountry']->notEmpty()) {
			$address .= "\n".\user\Country::ask($this['invoiceCountry'])['name'];
		}

		return ($type === 'text') ? $address : nl2br(encode($address));

	}

	public function hasDeliveryAddress(): bool {
		return ($this->exists() and $this['deliveryCity'] !== NULL);
	}

	public function getDeliveryAddress(string $type = 'text'): ?string {

		if($this->hasDeliveryAddress() === FALSE) {
			return NULL;
		}

		$address = $this['deliveryStreet1']."\n";
		if($this['deliveryStreet2'] !== NULL) {
			$address .= $this['deliveryStreet2']."\n";
		}

		$address .= $this['deliveryPostcode'].' '.$this['deliveryCity'];

		if($this['deliveryCountry']->notEmpty()) {
			$address .= "\n".\user\Country::ask($this['deliveryCountry'])['name'];
		}

		return ($type === 'text') ? $address : nl2br(encode($address));

	}

	public function getOptInHash() {
		return hash('sha256', random_bytes(1024));
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		\user\User::propertyAddress('delivery', $properties);
		\user\User::propertyAddress('invoice', $properties);

		if(array_intersect(['electronicScheme', 'electronicAddress'], $properties)) {
			$properties[] = 'fullElectronicAddress';
		}

		$p
			->setCallback('firstName.empty', function(?string &$firstName): bool {

				$this->expects(['user']);

				if($this->getCategory() !== Customer::PRIVATE) {
					$firstName = NULL;
					return TRUE;
				}

				if($this['user']->notEmpty()) {
					return ($firstName !== NULL);
				} else {
					return TRUE;
				}


			})
			->setCallback('lastName.empty', function(?string &$lastName) use($p): bool {

				$this->expects(['user']);

				if($this->getCategory() !== Customer::PRIVATE) {
					$lastName = NULL;
					return TRUE;
				}

				if($this['user']->notEmpty()) {
					return ($lastName !== NULL);
				} else {

					if($p->isBuilt('firstName') === FALSE) {
						return TRUE;
					} else {
						return ($lastName !== NULL or $this['firstName'] !== NULL);
					}

				}

			})
			->setCallback('email.save', function() use ($p): bool {

				if($p->for === 'update') {
					$this->expects(['email']);
					$this['oldEmail'] = $this['email'];
				}

				return TRUE;

			})
			->setCallback('commercialName.empty', function(?string &$name): bool {

				if($this->getCategory() !== Customer::PRO) {
					$name = NULL;
					return TRUE;
				}

				return ($name !== NULL);

			})
			->setCallback('contactName.empty', function(?string &$name): bool {

				$this->expects(['user']);

				if($this->getCategory() !== Customer::PRO) {
					$name = NULL;
				}

				return TRUE;

			})
			->setCallback('name.empty', function(?string &$name): bool {

				$this->expects(['user']);

				if($this->getCategory() !== Customer::COLLECTIVE) {
					$name = NULL;
					return TRUE;
				}

				return ($name !== NULL);

			})
			->setCallback('invoiceAddress.empty', fn() => \user\User::buildAddress('invoice', $this))
			->setCallback('deliveryAddress.empty', fn() => \user\User::buildAddress('delivery', $this))
			->setCallback('invoiceCountry.check', function(\user\Country $eCountry): bool {

				return (
					$eCountry->empty() or
					\user\Country::model()->exists($eCountry)
				);

			})
			->setCallback('deliveryCountry.check', function(\user\Country $eCountry): bool {

				return (
					$eCountry->empty() or
					\user\Country::model()->exists($eCountry)
				);

			})
			->setCallback('category.user', function(?string $category) use($p): bool {

				return match($p->for) {
					'create' => TRUE,
					'update' => ($category !== Customer::COLLECTIVE)
				};

			})
			->setCallback('phone.check', function(?string $phone): bool {

				return (
					$phone === NULL or
					preg_match('/^[0-9 \+]+$/si', $phone) > 0
				);

			})
			->setCallback('siret.check', function(?string &$siret) {
				if(\pdp\PdpLib::isActive($this['farm'])) {
					return $siret !== NULL and \farm\Farm::checkSiret($siret);
				}
				return \farm\Farm::checkSiret($siret);
			})
			->setCallback('vatNumber.check', fn(?string &$vat) => \farm\Farm::checkVatNumber('selling\Customer', $this, $vat, \pdp\PdpLib::isActive($this['farm']) === FALSE))
			->setCallback('defaultPaymentMethod.check', function(\payment\Method $eMethod): bool {

				if($eMethod->empty()) {
					return TRUE;
				}

				$this->expects(['farm']);

				return \payment\MethodLib::isSelectable($this['farm'], $eMethod);

			})
			->setCallback('groups.check', function(mixed &$groups) use($p): bool {

				$this->expects(['farm']);

				$groups = \selling\CustomerGroup::model()
					->whereId('IN', (array)($groups ?? []))
					->whereFarm($this['farm'])
					->whereType($this['type'])
					->getColumn('id');

				return TRUE;

			})
			->setCallback('category.set', function(?string $category) use($p): bool {

				$this['category'] = $category;

				switch($category) {

					case Customer::PRIVATE :
						$this['type'] = Customer::PRIVATE;
						$this['destination'] = Customer::INDIVIDUAL;
						return TRUE;

					case Customer::COLLECTIVE :
						$this['type'] = Customer::PRIVATE;
						$this['destination'] = Customer::COLLECTIVE;
						return TRUE;

					case Customer::PRO :
						$this['type'] = Customer::PRO;
						$this['destination'] = NULL;
						return TRUE;

					default :
						return FALSE;

				}

			})
			->setCallback('electronicScheme.check', function(?string $electronicScheme) use ($p): bool {

				if($p->isBuilt('type') === FALSE or $this['type'] === Customer::PRIVATE or \pdp\PdpLib::isActive($this['farm']) === FALSE) {
					return TRUE;
				}

				if($electronicScheme === NULL) {
					return FALSE;
				}

				return \pdp\Address::checkScheme($electronicScheme, $this['invoiceCountry']);

			})
			->setCallback('electronicAddress.check', function(?string $electronicAddress) use ($p): bool {

				if($p->isBuilt('electronicScheme') === FALSE or $this['type'] === Customer::PRIVATE or \pdp\PdpLib::isActive($this['farm']) === FALSE) {
					return TRUE;
				}

				if($p->isBuilt('siret') === FALSE) {
					return TRUE;
				}

				if($electronicAddress === NULL) {
					return FALSE;
				}
				return \pdp\Address::checkElectronicAddress($electronicAddress, $this['siret']);

			})
			->setCallback('fullElectronicAddress.check', function() use($p) {

				if($p->isBuilt('electronicScheme') === FALSE and $p->isBuilt(('electronicAddress')) === FALSE) {
					return TRUE;
				}

				return $p->isBuilt('electronicScheme') and $p->isBuilt(('electronicAddress')) ;

			})
		;

		parent::build($properties, $input, $p);

	}

}
?>
