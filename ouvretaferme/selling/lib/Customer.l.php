<?php
namespace selling;

class CustomerLib extends CustomerCrud {

	public static function getPropertiesCreate(): \Closure {

		return fn() => CustomerLib::getPropertiesDefault('create', POST('category'));

	}

	public static function getPropertiesUpdate(): \Closure {

		return fn(Customer $e) => array_merge(
			CustomerLib::getPropertiesDefault(
				'update',
				POST('category', default: ($e['destination'] === Customer::COLLECTIVE ? Customer::COLLECTIVE : $e['type']))
			),
			match($e->getCategory()) {
				Customer::PRO => ['discount', 'color', 'emailOptOut'],
				Customer::PRIVATE => ['discount', 'emailOptOut'],
				Customer::COLLECTIVE => ['color'],
			}
		);

	}

	public static function getPropertiesDefault(string $for, string $category): array {

		return match($category) {

			Customer::PRO => ['name', 'category', 'legalName', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'email', 'phone'],
			Customer::PRIVATE => ['name', 'category', 'email', 'phone'],
			Customer::COLLECTIVE => match($for) {
				'create' => ['name', 'category'],
				'update' => ['name']
			},
			default => ['category']

		};

	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, bool $withCollective = TRUE, ?array $properties = []): \Collection {

		if(str_starts_with($query, '#') and ctype_digit(substr($query, 1))) {

			Customer::model()->whereId(substr($query, 1));

		} else if($query !== '') {

			Customer::model()
				->where('
					name LIKE '.Customer::model()->format('%'.$query.'%').'
				')
				->sort([
					new \Sql('
						IF(
							name LIKE '.Customer::model()->format($query.'%').',
							5,
							IF(name LIKE '.Customer::model()->format('%'.$query.'%').', 4, 0)
						) DESC'),
					'name' => SORT_ASC
				]);

		} else {
			Customer::model()->sort('name');
		}

		if($withCollective === FALSE) {
			Customer::model()
				->or(
					fn() => $this->whereDestination(NULL),
					fn() => $this->whereDestination(Customer::INDIVIDUAL)
				);
		}

		return Customer::model()
			->select($properties ?: Customer::getSelection())
			->whereFarm($eFarm)
			->whereStatus(Customer::ACTIVE)
			->getCollection();

	}

	public static function getByFarm(\farm\Farm $eFarm, bool $selectPrices = FALSE, bool $selectSales = FALSE, bool $selectInvite = FALSE, int $page = 0, \Search $search = new \Search()): array {

		if($selectPrices) {
			Customer::model()
				->select([
					'prices' => Grid::model()
						->group(['customer'])
						->delegateProperty('customer', new \Sql('COUNT(*)', 'int'), fn($value) => $value ?? 0)
				]);
		}

		if($selectSales) {
			Customer::model()
				->select([
					'eSaleTotal' => Sale::model()
						->select([
							'customer',
							'year' => new \Sql('SUM(IF(EXTRACT(YEAR FROM deliveredAt) = '.date('Y').', priceExcludingVat, 0))', 'float'),
							'yearBefore' => new \Sql('SUM(IF(EXTRACT(YEAR FROM deliveredAt) = '.(date('Y') - 1).', priceExcludingVat, 0))', 'float'),
						])
						->wherePreparationStatus(Sale::DELIVERED)
						->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), 'IN', [(int)date('Y'), (int)date('Y') - 1])
						->group(['customer'])
						->delegateElement('customer')
				]);
		}

		if($selectInvite) {
			Customer::model()
				->select([
					'invite' => \farm\Invite::model()
						->select(\farm\Invite::getSelection())
						->whereExpiresAt('>=', new \Sql('CURDATE()'))
						->delegateElement('customer')
				]);
		}

		$search->validateSort(['name']);

		switch($search->get('category')) {

			case Customer::PRO :
				Customer::model()->whereType(Customer::PRO);
				break;

			case Customer::PRIVATE :
				Customer::model()
					->whereType(Customer::PRIVATE)
					->whereDestination(Customer::INDIVIDUAL);
				break;

			case Customer::COLLECTIVE :
				Customer::model()
					->whereType(Customer::PRIVATE)
					->whereDestination(Customer::COLLECTIVE);
				break;

		}

		$number = 100;
		$position = $page * $number;

		$cCustomer = Customer::model()
			->select(Customer::getSelection())
			->option('count')
			->whereFarm($eFarm)
			->whereName('LIKE', '%'.$search->get('name').'%', if: $search->get('name'))
			->whereEmail('LIKE', '%'.$search->get('email').'%', if: $search->get('email'))
			->sort($search->buildSort())
			->getCollection($position, $number);

		return [$cCustomer, Customer::model()->found()];

	}

	public static function getPrivateByUser(\user\User $eUser): \Collection {

		return Customer::model()
			->select(Customer::getSelection())
			->whereUser($eUser)
			->whereType(Customer::PRIVATE)
			->getCollection(NULL, NULL, 'id');

	}

	public static function getProByUser(\user\User $eUser): \Collection {

		return Customer::model()
			->select(Customer::getSelection())
			->select([
				'nSale' => Sale::model()
					->wherePreparationStatus('NOT IN', [Sale::DRAFT, Sale::CANCELED])
					->group('customer')
					->delegateProperty('customer', new \Sql('COUNT(*)', 'int'))
			])
			->whereUser($eUser)
			->whereType(Customer::PRO)
			->getCollection();

	}

	public static function countByUser(\user\User $eUser): int {

		return Customer::model()
			->whereUser($eUser)
			->count();

	}

	public static function getByUser(\user\User $eUser): \Collection {

		return Customer::model()
			->select(Customer::getSelection())
			->whereUser($eUser)
			->getCollection()
			->sort(['user' => ['farm']]);

	}

	public static function getByUserAndFarm(\user\User $eUser, \farm\Farm $eFarm): Customer {

		return Customer::model()
			->select(Customer::getSelection())
			->whereUser($eUser)
			->whereFarm($eFarm)
			->get();

	}

	public static function getForGrid(Product $e): \Collection {

		$e->expects(['farm']);

		$cCustomer = Customer::model()
			->select([
				'id', 'name', 'type',
				'eGrid' => Grid::model()
					->select(['id', 'price', 'packaging'])
					->whereProduct($e)
					->delegateElement('customer')
			])
			->whereFarm($e['farm'])
			->whereStatus(Customer::ACTIVE)
			->whereType(Customer::PRO)
			->sort(['name' => SORT_ASC])
			->getCollection();

		return $cCustomer;

	}

	public static function createFromUser(\user\User $eUser, \farm\Farm $eFarm, string $type): Customer {

		$eUser->expects(['email']);

		$eCustomer = new Customer([
			'name' => self::getNameFromUser($eUser),
			'email' => $eUser['email'],
			'type' => $type,
			'destination' => match($type) {
				Customer::PRIVATE => Customer::INDIVIDUAL,
				Customer::PRO => NULL
			},
			'farm' => $eFarm,
			'user' => $eUser
		]);

		Customer::model()->insert($eCustomer);

		return $eCustomer;

	}

	public static function getNameFromUser(\user\User $eUser): string {

		$eUser->expects(['firstName', 'lastName']);

		if($eUser['firstName'] === NULL) {
			return $eUser['lastName'];
		} else {
			return $eUser['firstName'].' '.$eUser['lastName'];
		}

	}

	public static function associateUser(Customer $e, \user\User $eUser): void {

		Customer::model()->update($e, [
			'user' => $eUser
		]);

	}

	public static function update(Customer $e, array $properties): void {

		if(array_delete($properties, 'category')) {
			$properties[] = 'type';
			$properties[] = 'destination';
		}

		Customer::model()->beginTransaction();

		parent::update($e, $properties);

		Customer::model()->commit();

	}

	public static function updateOptIn(\user\User $eUser, $customers): void {

		Customer::model()->beginTransaction();

		foreach($customers as $customer => $optIn) {

			$optIn = Customer::filter($optIn, 'emailOptIn', NULL);

			if($optIn !== NULL) {
				Customer::model()
					->whereUser($eUser)
					->whereId($customer)
					->update([
						'emailOptIn' => $optIn
					]);
			}

		}

		Customer::model()->commit();

	}

	public static function updateOptInByEmail(\farm\Farm $eFarm, string $email, bool $optIn): void {

		Customer::model()->beginTransaction();

		$eUser = \user\UserLib::getByEmail($email);

		if($eUser->notEmpty()) {

			Customer::model()
				->whereFarm($eFarm)
				->whereUser($eUser)
				->whereStatus(Customer::ACTIVE)
				->update([
					'emailOptIn' => $optIn
				]);


		}

		Customer::model()->commit();

	}

	public static function delete(Customer $e): void {

		$e->expects(['id']);

		if(Sale::model()
			->whereCustomer($e)
			->exists()) {
			Customer::fail('deletedUsed');
			return;
		}

		Customer::model()->beginTransaction();

		Customer::model()->delete($e);

		Customer::model()->commit();

	}

}
?>
