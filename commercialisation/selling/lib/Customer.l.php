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

		// Conserver cet ordre est indispensable : 'firstName', 'lastName', 'commercialName', 'name'
		$properties = ['firstName', 'lastName', 'commercialName', 'name', 'legalName'];
		$propertiesAddress = ['invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'invoiceCountry', 'deliveryStreet1', 'deliveryStreet2', 'deliveryPostcode', 'deliveryCity', 'deliveryCountry'];

		return match($category) {

			Customer::PRO => array_merge(['category'], $properties, $propertiesAddress, ['siret',  'vatNumber', 'email', 'defaultPaymentMethod', 'phone', 'contactName'], \pdp\PdpLib::isActive(new \farm\Farm()) ? ['electronicScheme', 'electronicAddress'] : []),
			Customer::PRIVATE => array_merge(['category'], $properties, $propertiesAddress, ['email', 'defaultPaymentMethod', 'phone']),
			Customer::COLLECTIVE => match($for) {
				'create' => array_merge(['category'], $properties),
				'update' => $properties
			},
			default => ['category']

		};

	}

	public static function getForRestrictions(array $customers): \Collection {
		return self::getByIds($customers, sort: ['lastName' => SORT_ASC, 'firstName' => SORT_ASC]);
	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, ?string $type = NULL, ?string $destination = NULL, ?array $properties = []): \Collection {

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

		switch($destination) {

			case Customer::INDIVIDUAL :
				Customer::model()
					->or(
						fn() => $this->whereDestination(NULL),
						fn() => $this->whereDestination(Customer::INDIVIDUAL)
					);
				break;

			case Customer::COLLECTIVE :
				Customer::model()->whereDestination(Customer::COLLECTIVE);
				break;

		}

		return Customer::model()
			->select($properties ?: Customer::getSelection())
			->whereFarm($eFarm)
			->whereType($type, if: $type !== NULL)
			->whereStatus(Customer::ACTIVE)
			->getCollection();

	}

	public static function getByFarm(\farm\Farm $eFarm, bool $selectPrices = FALSE, bool $selectSales = FALSE, bool $selectInvite = FALSE, int $page = 0, \Search $search = new \Search()): \Collection {

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

		$search->validateSort(['id', 'name', 'firstName', 'lastName']);

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

		return $cCustomer;

	}

	public static function getByGroup(CustomerGroup $eCustomerGroup): \Collection {

		$eCustomerGroup->expects(['farm']);

		return Customer::model()
			->select(Customer::getSelection())
			->whereFarm($eCustomerGroup['farm'])
			->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$eCustomerGroup['id'].'\')')
			->sort(['name' => SORT_ASC])
			->getCollection();

	}

	public static function countByGroup(CustomerGroup $eCustomerGroup): int {

		$eCustomerGroup->expects(['farm']);

		return Customer::model()
			->whereFarm($eCustomerGroup['farm'])
			->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$eCustomerGroup['id'].'\')')
			->count();

	}

	public static function getRestrictedByCollection(\Collection $c): \Collection {

		$customers = array_merge(...$c->getColumn('limitCustomers'), ...$c->getColumn('excludeCustomers'));

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

	public static function getBySiret(\farm\Farm $eFarm, string $siret): Customer {

		return Customer::model()
			->select(Customer::getSelection())
			->whereSiret($siret)
			->whereFarm($eFarm)
			->get();

	}
	public static function getByUserAndFarm(\user\User $eUser, \farm\Farm $eFarm, bool $autoCreate = FALSE): Customer {

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
			$eCustomer = \selling\CustomerLib::createFromUser($eUser, $eFarm);
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

	public static function createFromUser(\user\User $eUser, \farm\Farm $eFarm): Customer {

		$eUser->expects(['email']);

		$eCustomer = new Customer([
			'type' => $eUser['type'],
			'email' => $eUser['email'],
			'phone' => $eUser['phone'],
			'destination' => match($eUser['type']) {
				Customer::PRIVATE => Customer::INDIVIDUAL,
				Customer::PRO => NULL
			},
			'farm' => $eFarm,
			'user' => $eUser
		]);

		switch($eUser['type']) {

			case \user\User::PRIVATE :
				$eCustomer->merge([
					'firstName' => $eUser['firstName'],
					'lastName' => $eUser['lastName'],
				]);
				break;

			case \user\User::PRO :
				$eCustomer->merge([
					'commercialName' => $eUser['legalName'],
					'contactName' => self::getNameFromUser($eUser),
					'siret' => $eUser['siret'],
				]);
				$eUser->copyDeliveryAddress($eCustomer);
				$eUser->copyInvoiceAddress($eCustomer);
				break;

		}

		self::create($eCustomer);

		return $eCustomer;

	}

	public static function create(Customer $e): void {

		$e->expects(['farm']);

		// Points de vente
		$e['email'] ??= NULL;
		self::fillName($e);

		$e['document'] = \farm\ConfigurationLib::getNextDocumentCustomers($e['farm']);
		$e['number'] = $e->calculateNumber();

		parent::create($e);

		if($e['email'] !== NULL) {
			\mail\ContactLib::autoCreate($e['farm'], $e['email']);
		}

	}

	public static function fillName(Customer $e): void {
		
		if($e->getCategory() === Customer::PRIVATE) {

			$e->expects(['firstName', 'lastName']);

			if($e['firstName'] !== NULL and $e['lastName'] !== NULL) {
				$e['name'] = $e['firstName'].' '.mb_strtoupper($e['lastName']);
			} else if($e['lastName'] !== NULL) {
				$e['name'] = mb_strtoupper($e['lastName']);
			} else {
				$e['name'] = $e['firstName'];
			}

		} else if($e->getCategory() === Customer::PRO) {

			$e->expects(['commercialName']);

			$e['name'] = $e['commercialName'];
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

		if(count(array_intersect($properties, ['firstName', 'lastName', 'name', 'commercialName', 'legalName'])) > 0) {
			$properties[] = 'name';
			self::fillName($e);
		}

		if(array_delete($properties, 'category')) {
			$properties[] = 'type';
			$properties[] = 'destination';
		}

		if(
			in_array('deliveryCountry', $properties) and
			$e->hasInvoiceAddress() === FALSE
		) {

			$e['invoiceCountry'] = $e['deliveryCountry'];
			$properties[] = 'invoiceCountry';

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

		if(
			$e['farm']->hasAccounting() and
			(in_array('vatNumber', $properties) or in_array('siret', $properties))
		) {
			\farm\FarmLib::connectDatabase($e['farm']);
			\account\ThirdPartyLib::updateByCustomer($e);
		}

		Customer::model()->commit();

	}

	public static function associateGroup(\Collection $cCustomer, CustomerGroup $eCustomerGroup): void {

		Customer::model()
			->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$eCustomerGroup['id'].'\') = 0')
			->whereDestination('!=', Customer::COLLECTIVE, if: $eCustomerGroup['type'] === Customer::PRIVATE)
			->whereType($eCustomerGroup['type'])
			->whereId('IN', $cCustomer)
			->update([
				'groups' => new \Sql('JSON_ARRAY_INSERT('.Customer::model()->field('groups').', \'$[0]\', '.$eCustomerGroup['id'].')')
			]);

	}

	public static function dissociateGroup(\Collection $cCustomer, CustomerGroup $eCustomerGroup): void {

		Customer::model()
			->where('JSON_CONTAINS('.Customer::model()->field('groups').', \''.$eCustomerGroup['id'].'\')')
			->whereId('IN', $cCustomer)
			->whereDestination('!=', Customer::COLLECTIVE)
			->whereType($eCustomerGroup['type'])
			->update([
				'groups' => new \Sql(Customer::model()->pdo()->api->jsonRemove('groups', $eCustomerGroup['id']))
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
				->exists() or
			Sale::model()
				->whereShopSharedCustomer($e)
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

	public static function buildCollection(\Element $e, mixed &$customers, bool $checkType): bool {

		$e->expects(['farm']);

		if($checkType) {
			$e->expects(['type']);
		}

		$customers = \selling\Customer::model()
			->select('id')
			->whereId('IN', (array)($customers ?? []))
			->whereFarm($e['farm'])
			->whereType(fn() => $e['type'], if: $checkType)
			->getColumn('id');

		return TRUE;

	}

}
?>
