<?php
namespace user;

class User extends UserElement {

	public function getName(): string {

		$this->expects(['firstName', 'lastName']);

		if($this['firstName'] === NULL) {
			return encode($this['lastName']);
		} else {
			return encode($this['firstName']).' '.encode($this['lastName']);
		}

	}

	public function isOnline(): bool {

		$eUserOnline = \user\ConnectionLib::getOnline();

		return (
			$eUserOnline->notEmpty() and
			$this->notEmpty() and
			$this['id'] === $eUserOnline['id']
		);
	}

	public function isRole(string|array $roles): bool {

		if($this->empty()) {
			return FALSE;
		}

		$this->expects([
			'role' => ['fqn']
		]);

		$roles = (array)$roles;

		return in_array($this['role']['fqn'], $roles);

	}

	public function isAdmin(): bool {
		return $this->isRole('admin');
	}

	public function checkIsAdmin(): bool {
		if($this->isAdmin()) {
			return TRUE;
		}
		throw new \DisabledPage('admin');
	}

	public function isFarmer(): bool {
		return $this->isRole('admin') or $this->isRole('farmer');
	}

	public function checkIsFarmer(): bool {
		if($this->isFarmer()) {
			return TRUE;
		}
		throw new \DisabledPage('farmer');
	}

	public function isCustomer(): bool {
		return $this->isRole('admin') or $this->isRole('farmer') or $this->isRole('customer');
	}

	public function checkIsCustomer(): bool {
		if($this->isCustomer()) {
			return TRUE;
		}
		throw new \DisabledPage('customer');
	}

	public function active(): bool {
		return ($this['status'] === User::ACTIVE);
	}

	public function hasAddress(): bool {
		return (
			$this->hasInvoiceAddress() or
			$this->hasDeliveryAddress()
		);
	}

	public function getBestInvoiceAddress(): ?string {

		if($this->hasInvoiceAddress()) {
			return $this->getInvoiceAddress();
		} else if($this->hasDeliveryAddress()) {
			return $this->getDeliveryAddress();
		} else {
			return NULL;
		}

	}

	public function getDeliveryAddress(): ?string {

		if($this->hasDeliveryAddress() === FALSE) {
			return NULL;
		}

		$address = $this['deliveryStreet1']."\n";
		if($this['deliveryStreet2'] !== NULL) {
			$address .= $this['deliveryStreet2']."\n";
		}
		$address .= $this['deliveryPostcode'].' '.$this['deliveryCity'];

		return $address;

	}

	public function hasDeliveryAddress(): bool {
		return $this['deliveryCity'] !== NULL;
	}

	public function getInvoiceAddress(): ?string {

		if($this->hasInvoiceAddress() === FALSE) {
			return NULL;
		}

		$address = $this['invoiceStreet1']."\n";
		if($this['invoiceStreet2'] !== NULL) {
			$address .= $this['invoiceStreet2']."\n";
		}
		$address .= $this['invoicePostcode'].' '.$this['invoiceCity'];

		return $address;

	}

	public function hasInvoiceAddress(): bool {
		return $this['invoiceCity'] !== NULL;
	}

	public function copyDeliveryAddress(\Element $e, array &$properties = []): void {

		$e->merge([
			'deliveryStreet1' => $this['deliveryStreet1'],
			'deliveryStreet2' => $this['deliveryStreet2'],
			'deliveryPostcode' => $this['deliveryPostcode'],
			'deliveryCity' => $this['deliveryCity'],
			'deliveryCountry' => $this['deliveryCountry'],
		]);

		$properties = array_merge($properties, ['deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity', 'deliveryCountry']);

	}

	public function copyInvoiceAddress(\Element $e, array &$properties = []): void {

		$e->merge([
			'invoiceStreet1' => $this['invoiceStreet1'],
			'invoiceStreet2' => $this['invoiceStreet2'],
			'invoicePostcode' => $this['invoicePostcode'],
			'invoiceCity' => $this['invoiceCity'],
			'invoiceCountry' => $this['invoiceCountry'],
		]);

		$properties = array_merge($properties, ['invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'invoiceCountry']);

	}

	public function isPrivate(): bool {
		return ($this['visibility'] === User::PRIVATE);
	}

	public function isPublic(): bool {
		return ($this['visibility'] === User::PUBLIC);
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$emailKey = array_search('email', $properties);

		if($emailKey !== FALSE) {
			$this->buildEmail(UserAuth::BASIC, $input);
			unset($properties[$emailKey]);
		}

		self::propertyAddress('delivery', $properties);
		self::propertyAddress('invoice', $properties);

		$p
			->setCallback('siret.prepare', function(?string &$siret) use($p): void {

				if($p->isBuilt('deliveryCountry') === FALSE) {
					return;
				}

				$this->expects(['type', 'deliveryCountry']);

				if(
					$this['type'] === User::PRIVATE or
					$this['deliveryCountry']->isFR() === FALSE
				) {
					$siret = NULL;
				}

			})
			->setCallback('siret.check', fn(?string &$siret) => \farm\Farm::checkSiret($siret))
			->setCallback('phone.empty', function(?string $phone): bool {

				$this->expects(['visibility']);

				return (
					$this['visibility'] == User::PRIVATE or
					$phone !== NULL
				);

			})
			->setCallback('role.check', function(Role $eRole): bool {

				return (
					Role::model()
						->select('fqn')
						->get($eRole) and (
						in_array($eRole['fqn'], UserSetting::$signUpRoles) or // Allowed role
						ConnectionLib::getOnline()->isAdmin() // Admin guy
					)
				);

			})
			->setCallback('deliveryCountry.check', function($eCountry): bool {

				return Country::model()->exists($eCountry);

			})
			->setCallback('invoiceCountry.check', function($eCountry): bool {

				return Country::model()->exists($eCountry);

			})
			->setCallback('firstName.prepare', function(?string $firstName): bool {

				$this->expects(['visibility']);

				return (
					$this['visibility'] == User::PRIVATE or
					$firstName !== NULL
				);

			})
			->setCallback('legalName.prepare', function(?string &$legalName): void {

				$this->expects(['type']);

				if($this['type'] === User::PRIVATE) {
					$legalName = NULL;
				}

			})
			->setCallback('invoiceAddress.empty', fn() => \user\User::buildAddress('invoice', $this))
			->setCallback('deliveryAddress.empty', fn() => \user\User::buildAddress('delivery', $this))
			->setCallback('deliveryAddressMandatory.check', function() use ($p): bool {

				return (
					$this['deliveryStreet1'] !== NULL and
					$this['deliveryPostcode'] !== NULL and
					$this['deliveryCity'] !== NULL
				);

			});

		parent::build($properties, $input, $p);

	}

	/**
	 * Check if a user can be created with basic authentication using the given input
	 * - email: user[email] *
	 */
	public function buildEmail(string $auth, array $input): bool {

		$this->add([
			'auth' => new UserAuth()
		]);

		$fw = new \FailWatch;
		
		$p = new \Properties()
			->setCallback('email.auth', function($email) use($auth) {
				return ($auth === UserAuth::BASIC);
			})
			->setCallback('email.empty', function($email) use($auth) {
				return ($email !== NULL);
			})
			->setCallback('email.duplicate', function($email) use($auth) {

				// User did not change his email address
				if(
					$this->offsetExists('email') and
					$this->offsetGet('email') === $email
				) {
					return TRUE;
				}

				// Checks that email is not already used
				return (User::model()
						->whereEmail($email)
						->exists() === FALSE);
			});

		parent::build(['email'], $input, $p);

		if($fw->ok()) {

			$this['auth']['login'] = $this['email'];
			return TRUE;

		} else {
			return FALSE;
		}

	}
	
	public static function propertyAddress(string $type, array &$properties): void {

		$address = count(array_intersect($properties, [$type.'Street1', $type.'Street2', $type.'Postcode', $type.'City']));

		if($address === 4) {
			$properties[] = $type.'Address';
		} else if($address > 0) {
			throw new \Exception('Invalid address build');
		}

	}

	public static function buildAddress(string $type, \Element $e): bool {

		if($e[$type.'Street1'] === NULL and $e[$type.'Street2'] === NULL and $e[$type.'Postcode'] === NULL and $e[$type.'City'] === NULL) {
			return TRUE;
		}

		$fw = new \FailWatch();

		if($e[$type.'Street1'] === NULL) {
			$e->getModule()::fail($type.'Street1.check');
		}

		if($e[$type.'Postcode'] === NULL) {
			$e->getModule()::fail($type.'Postcode.check');
		}

		if($e[$type.'City'] === NULL) {
			$e->getModule()::fail($type.'City.check');
		}

		return $fw->ok();

	}
	
}
?>
