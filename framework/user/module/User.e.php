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

	public function getAddress(): ?string {

		if($this->hasAddress() === FALSE) {
			return NULL;
		}

		$address = $this['invoiceStreet1']."\n";
		if($this['invoiceStreet2'] !== NULL) {
			$address .= $this['invoiceStreet2']."\n";
		}
		$address .= $this['invoicePostcode'].' '.$this['invoiceCity'];

		return $address;

	}

	public function hasAddress(): bool {
		return ($this['invoiceCity'] !== NULL);
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

		$address = count(array_intersect($properties, ['invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity']));

		if($address === 4) {
			$properties[] = 'address';
		} else if($address > 0) {
			throw new \Exception('Invalid address build');
		}

		$p
			->setCallback('birthdate.future', function(?string $date): bool {

				if($date === NULL) {
					return TRUE;
				}

				return (\util\DateLib::compare($date, currentDate()) < 0);

			})
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
			->setCallback('country.check', function($eCountry): bool {

				return (
					$eCountry->empty() === FALSE and
					Country::model()->exists($eCountry)
				);

			})
			->setCallback('firstName.empty', function(?string $firstName): bool {

				$this->expects(['visibility']);

				return (
					$this['visibility'] == User::PRIVATE or
					$firstName !== NULL
				);

			})
			->setCallback('address.empty', function(): bool {

				if($this['invoiceStreet1'] === NULL and $this['invoiceStreet2'] === NULL and $this['invoicePostcode'] === NULL and $this['invoiceCity'] === NULL) {
					return TRUE;
				}

				$fw = new \FailWatch();

				if($this['invoiceStreet1'] === NULL) {
					User::fail('invoiceStreet1.check');
				}

				if($this['invoicePostcode'] === NULL) {
					User::fail('invoicePostcode.check');
				}

				if($this['invoiceCity'] === NULL) {
					User::fail('invoiceCity.check');
				}

				return $fw->ok();

			})
			->setCallback('invoiceAddressMandatory.check', function(): bool {

				return (
					$this['invoiceStreet1'] !== NULL and
					$this['invoicePostcode'] !== NULL and
					$this['invoiceCity'] !== NULL
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
			->setCallback('country.check', function(Country $eCountry) use($auth) {
				return Country::model()->exists($eCountry);
			})
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
	
}
?>
