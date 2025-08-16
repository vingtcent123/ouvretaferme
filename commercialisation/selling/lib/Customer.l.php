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
				Customer::PRO => ['discount', 'color', 'orderFormEmail', 'deliveryNoteEmail', 'invoiceEmail', 'groups'],
				Customer::PRIVATE => ['discount', 'groups'],
				Customer::COLLECTIVE => ['color'],
			}
		);

	}

	public static function getPropertiesDefault(string $for, string $category): array {

		// Conserver cet ordre est indispensable : 'firstName', 'lastName', 'name'

		return match($category) {

			Customer::PRO => ['category', 'firstName', 'lastName', 'name', 'legalName', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'siret', 'invoiceVat', 'email', 'defaultPaymentMethod', 'phone'],
			Customer::PRIVATE => ['category', 'firstName', 'lastName', 'name', 'email', 'defaultPaymentMethod', 'phone'],
			Customer::COLLECTIVE => match($for) {
				'create' => ['category', 'firstName', 'lastName', 'name'],
				'update' => ['firstName', 'lastName', 'name']
			},
			default => ['category']

		};

	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, ?string $type = NULL, bool $withCollective = TRUE, ?array $properties = []): \Collection {

		if(str_starts_with($query, '#') and ctype_digit(substr($query, 1))) {

			Customer::model()->whereId(substr($query, 1));

		} else if($query !== '') {

			$query = preg_replace('/\s+\/(\s+\w+)+$/i', '', $query);

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
			->whereType($type, if: $type !== NULL)
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

		$search->validateSort(['name', 'firstName', 'lastName']);

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
			->where(fn() => new \Sql('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$search->get('group')['id'].'\')'), if: $search->get('group')->notEmpty())
			->whereName('LIKE', '%'.$search->get('name').'%', if: $search->get('name'))
			->whereEmail('LIKE', '%'.$search->get('email').'%', if: $search->get('email'))
			->sort($search->buildSort([
				'firstName' => fn($direction) => match($direction) {
					SORT_ASC => new \Sql('IF(firstName IS NULL, name, firstName), lastName, id'),
					SORT_DESC => new \Sql('IF(firstName IS NULL, name, firstName) DESC, lastName DESC, id DESC')
				},
				'lastName' => fn($direction) => match($direction) {
					SORT_ASC => new \Sql('IF(lastName IS NULL, name, lastName), firstName, id'),
					SORT_DESC => new \Sql('IF(lastName IS NULL, name, lastName) DESC, firstName DESC, id DESC')
				}
			]))
			->getCollection($position, $number);

		return [$cCustomer, Customer::model()->found()];

	}

	public static function getByGroup(Group $eGroup): \Collection {

		$eGroup->expects(['farm']);

		return Customer::model()
			->select(Customer::getSelection())
			->whereFarm($eGroup['farm'])
			->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$eGroup['id'].'\')')
			->sort(['name' => SORT_ASC])
			->getCollection();

	}

	public static function countByGroup(Group $eGroup): int {

		$eGroup->expects(['farm']);

		return Customer::model()
			->whereFarm($eGroup['farm'])
			->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$eGroup['id'].'\')')
			->count();

	}

	public static function getLimitedByProducts(\Collection $cProduct): \Collection {

		$customers = array_merge(...$cProduct->getColumn('limitCustomers'), ...$cProduct->getColumn('excludeCustomers'));

		return Customer::model()
			->select(Customer::getSelection())
			->whereId('IN', $customers)
			->getCollection(NULL, NULL, 'id');

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

	public static function getByUserAndFarm(\user\User $eUser, \farm\Farm $eFarm, bool $autoCreate = FALSE, ?string $autoCreateType = NULL): Customer {

		if($eUser->empty()) {
			return new \selling\Customer();
		}

		$eCustomer = Customer::model()
			->select(Customer::getSelection())
			->whereUser($eUser)
			->whereFarm($eFarm)
			->get();

		if($eCustomer->empty() and $autoCreate) {
			// Possible problème de DUPLICATE si le customer a été créé entre cette instruction et la précédente
			$eCustomer = \selling\CustomerLib::createFromUser($eUser, $eFarm, $autoCreateType ?? throw new \Exception('Missing type'));
		}

		return $eCustomer;

	}

	public static function getByUserAndFarms(\user\User $eUser, \Collection $cFarm): \Collection {

		return Customer::model()
			->select(Customer::getSelection())
			->whereUser($eUser)
			->whereFarm('IN', $cFarm)
			->getCollection(index: 'farm');

	}

	public static function getForGrid(Product $e): \Collection {

		$e->expects(['farm']);

		$cCustomer = Customer::model()
			->select([
				'id', 'name', 'type',
				'eGrid' => Grid::model()
					->select(['id', 'price', 'priceInitial', 'packaging'])
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
			'firstName' => $eUser['firstName'],
			'lastName' => $eUser['lastName'],
			'email' => $eUser['email'],
			'phone' => $eUser['phone'],
			'type' => $type,
			'destination' => match($type) {
				Customer::PRIVATE => Customer::INDIVIDUAL,
				Customer::PRO => NULL
			},
			'farm' => $eFarm,
			'user' => $eUser
		]);

		Customer::model()->insert($eCustomer);

		if($eCustomer['email'] !== NULL) {
			\mail\ContactLib::autoCreate($eCustomer['farm'], $eCustomer['email']);
		}

		return $eCustomer;

	}

	public static function create(Customer $e): void {

		$e->expects(['farm']);

		// Points de vente
		$e['email'] ??= NULL;

		parent::create($e);

		if($e['email'] !== NULL) {
			\mail\ContactLib::autoCreate($e['farm'], $e['email']);
		}

	}

	public static function getNameFromUser(\user\User $eUser): string {

		$eUser->expects(['firstName', 'lastName']);

		if($eUser['firstName'] === NULL) {
			return $eUser['lastName'];
		} else {
			return $eUser['firstName'].' '.mb_strtoupper($eUser['lastName']);
		}

	}

	public static function associateUser(Customer $e, \user\User $eUser): void {

		$eUser->expects(['email']);

		Customer::model()->update($e, [
			'user' => $eUser,
			'email' => $eUser['email']
		]);

	}

	public static function update(Customer $e, array $properties): void {

		$e->expects(['farm', 'email']);

		if(array_delete($properties, 'category')) {
			$properties[] = 'type';
			$properties[] = 'destination';
		}

		Customer::model()->beginTransaction();

		parent::update($e, $properties);

		if(
			in_array('status', $properties) and
			$e['email'] !== NULL
		) {

			\mail\ContactLib::synchronizeCustomerStatus($e);

		}

		if(in_array('email', $properties)) {
			\mail\ContactLib::synchronizeCustomerEmail($e);
		}

		Customer::model()->commit();

	}

	public static function associateGroup(\Collection $cCustomer, Group $eGroup): void {

		Customer::model()
			->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$eGroup['id'].'\') = 0')
			->whereDestination('!=', Customer::COLLECTIVE, if: $eGroup['type'] === Customer::PRIVATE)
			->whereType($eGroup['type'])
			->whereId('IN', $cCustomer)
			->update([
				'groups' => new \Sql('JSON_ARRAY_INSERT('.Customer::model()->field('groups').', \'$[0]\', '.$eGroup['id'].')')
			]);

	}

	public static function dissociateGroup(\Collection $cCustomer, Group $eGroup): void {

		Customer::model()
			->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$eGroup['id'].'\')')
			->whereId('IN', $cCustomer)
			->whereDestination('!=', Customer::COLLECTIVE)
			->whereType($eGroup['type'])
			->update([
				'groups' => new \Sql(Customer::model()->pdo()->api->jsonRemove('groups', $eGroup['id']))
			]);

	}

	public static function delete(Customer $e): void {

		$e->expects(['id', 'farm']);

		if(
			Sale::model()
				->whereFarm($e['farm'])
				->whereCustomer($e)
				->exists() or
			Invoice::model()
				->whereFarm($e['farm'])
				->whereCustomer($e)
				->exists()
		) {
			Customer::fail('deletedUsed');
			return;
		}

		Customer::model()->beginTransaction();

			Grid::model()
				->whereCustomer($e)
				->delete();

			Customer::model()->delete($e);

			\mail\ContactLib::deleteCustomer($e);

		Customer::model()->commit();

	}

}
?>
