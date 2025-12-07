<?php
namespace selling;

class Customer extends CustomerElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => \farm\FarmElement::getSelection(),
			'user' => ['email'],
			'cGroup?' => fn($e) => fn() => \selling\CustomerGroupLib::askByFarm($e['farm'], $e['groups']),
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
			$this['invoiceCountry']->notEmpty() and
			$this['invoiceCountry']['id'] === 75
		);

	}

	public function isBE(): bool {

		return (
			$this->isPro() and
			$this['invoiceCountry']->notEmpty() and
			$this['invoiceCountry']['id'] === 20
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
	public function getDeliveryStreet(): ?string {

		$street = $this['deliveryStreet1'];

		if($this['deliveryStreet2'] !== NULL) {

			$street .= "\n".$this['deliveryStreet2'];

		}

		return $street;

	}

	public function getInvoiceAddress(string $type = 'text'): ?string {

		if($this->hasInvoiceAddress() === FALSE) {
			return NULL;
		}

		$address = $this->getInvoiceStreet()."\n";
		$address .= $this['invoicePostcode'].' '.$this['invoiceCity'];

		if($this['invoiceCountry']->notEmpty()) {
			$address .= "\n".\user\Country::ask($this['invoiceCountry'])['name'];
		}

		return ($type === 'text') ? $address : nl2br(encode($address));

	}

	public function getInvoiceStreet(): ?string {

		$street = $this['invoiceStreet1'];
		if($this['invoiceStreet2'] !== NULL) {
			$street .= "\n".$this['invoiceStreet2'];
		}

		return $street;

	}

	public function hasInvoiceAddress(): bool {
		return ($this['invoiceCity'] !== NULL);
	}

	public function getOptInHash() {
		return hash('sha256', random_bytes(1024));
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

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
			->setCallback('name.empty', function(?string &$name): bool {

				$this->expects(['user']);

				if($this->getCategory() === Customer::PRIVATE) {
					$name = NULL;
					return TRUE;
				}

				return ($name !== NULL);

			})
			->setCallback('name.set', function(?string $name) use($p): bool {

				if($this->getCategory() === Customer::PRIVATE) {

					if($p->isBuilt(['firstName', 'lastName'])) {

						if($this['firstName'] !== NULL and $this['lastName'] !== NULL) {
							$this['name'] = $this['firstName'].' '.mb_strtoupper($this['lastName']);
						} else if($this['lastName'] !== NULL) {
							$this['name'] = mb_strtoupper($this['lastName']);
						} else {
							$this['name'] = $this['firstName'];
						}

					} else {
						return FALSE;
					}

				} else {
					$this['name'] = $name;
				}

				return TRUE;

			})
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
			->setCallback('siret.check', fn(?string &$siret) => \farm\Farm::checkSiret($siret))
			->setCallback('vatNumber.check', fn(?string &$vat) => \farm\Farm::checkVatNumber('selling\Customer', $this, $vat))
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

				};

			});

		parent::build($properties, $input, $p);

	}

}
?>
