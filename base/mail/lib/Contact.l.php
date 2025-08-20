<?php
namespace mail;

class ContactLib extends ContactCrud {

	public static function getPropertiesCreate(): array {

		return ['email'];

	}

	public static function getByUser(\user\User $eUser): \Collection {

		return Contact::model()
			->select(Contact::getSelection() + [
				'farm' => ['name', 'vignette']
			])
			->whereEmail($eUser['email'])
			->getCollection();

	}

	public static function getSearch(\farm\Farm $eFarm, array $data = []): \Search {

		return new \Search([
			'source' => var_filter($data['source'] ?? NULL, '?string'),
			'email' => var_filter($data['email'] ?? NULL),
			'shop' => var_filter($data['shop'] ?? NULL, 'shop\Shop'),
			'group' => var_filter($data['group'] ?? NULL, 'selling\Group'),
			'optIn' => var_filter($data['optIn'] ?? NULL, '?string'),
			'newsletter' => var_filter($data['newsletter'] ?? NULL, '?string'),
			'category' => var_filter($data['category'] ?? NULL, [\selling\Customer::PRIVATE, \selling\Customer::PRO]),
			'cShop' => \shop\ShopLib::getByFarm($eFarm)
		], var_filter($data['sort'] ?? NULL, default: 'createdAt-'));

	}

	public static function registerNewsletter(Contact $e): void {

		self::updateOptInByEmail($e['farm'], $e['email'], TRUE);

		$farmEmail = $e['farm']['legalEmail'];

		if($farmEmail === NULL) {
			throw new \Exception('Missing farm email');
		}

		\farm\Farm::model()
			->select(\farm\FarmElement::getSelection())
			->get($e['farm']);

		new \mail\SendLib()
			->setTo($farmEmail)
			->setReplyTo($e['email'])
			->setContent(...\website\NewsletterUi::getFarmEmail($e))
			->send();


	}

	public static function countByFarm(\farm\Farm $eFarm, \Search $search = new \Search()): int {

		self::applySearch($search);

		return Contact::model()
			->where('m1.farm', $eFarm)
			->count();

	}

	public static function getByFarm(\farm\Farm $eFarm, ?int $page = NULL, bool $withCustomer = FALSE, \Search $search = new \Search()): \Collection {

		$search->validateSort(['email', 'createdAt', 'lastSent', 'sent', 'delivered', 'opened', 'blocked'], 'email');

		self::applySearch($search);

		$selection = Contact::getSelection();

		if($withCustomer) {
			$selection['cCustomer'] = \selling\Customer::model()
				->select(\selling\CustomerElement::getSelection())
				->whereFarm($eFarm)
				->delegateCollection('email', propertyParent: 'email');
		}

		$number = ($page === NULL) ? NULL : 100;
		$position = ($page === NULL) ? NULL : $page * $number;

		return Contact::model()
			->select($selection)
			->where('m1.farm', $eFarm)
			->sort($search->buildSort([
				'createdAt' => fn($direction) => match($direction) {
					SORT_ASC => new \Sql('m1.createdAt'),
					SORT_DESC => new \Sql('m1.createdAt DESC')
				},
				'email' => fn($direction) => match($direction) {
					SORT_ASC => new \Sql('m1.email'),
					SORT_DESC => new \Sql('m1.email DESC')
				},
				'blocked' => fn($direction) => match($direction) {
					SORT_ASC => new \Sql('failed + spam'),
					SORT_DESC => new \Sql('failed + spam DESC')
				},
			]))
			->getCollection($position, $number);

	}

	public static function getByCampaign(Campaign $eCampaign, bool $withCustomer = FALSE): \Collection {

		$eCampaign->expects(['farm', 'source']);

		$search = self::getSearch($eCampaign['farm']);
		$search->set('export', TRUE);

		switch($eCampaign['source']) {

			case Campaign::SHOP :
				$search->set('shop', $eCampaign['sourceShop']);
				break;

			case Campaign::GROUP :
				$search->set('group', $eCampaign['sourceGroup']);
				break;

			case Campaign::PERIOD :
				$search->set('period', $eCampaign['sourcePeriod']);
				break;

			default :
				return new \Collection();

		}

		return self::getByFarm($eCampaign['farm'], withCustomer: $withCustomer, search: $search);

	}

	public static function getByEmails(\farm\Farm $eFarm, array $emails, bool $withCustomer): \Collection {

		self::applyExport();

		Contact::model()->whereEmail('IN', $emails);

		return self::getByFarm($eFarm, withCustomer: $withCustomer);

	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm): \Collection {

		return Contact::model()
			->select([
				'email',
				'cCustomer' => \selling\Customer::model()
					->select('firstName', 'lastName', 'name', 'destination', 'type')
					->whereFarm($eFarm)
					->delegateCollection('email', propertyParent: 'email')
			])
			->join(\selling\Customer::model(), 'm1.email = m2.email AND m1.farm = m2.farm', 'LEFT')
			->where('m1.farm', $eFarm)
			->or(
				fn() => $this->where('m1.email', 'LIKE', '%'.$query.'%'),
				fn() => $this->where('m2.name', 'LIKE', '%'.$query.'%'),
				if: $query !== ''
			)
			->group(new \Sql('m1.email'))
			->sort(new \Sql('m1.email ASC'))
			->getCollection();

	}

	public static function synchronizeCustomerStatus(\selling\Customer $eCustomer): void {

		$active = \selling\Customer::model()
			->whereFarm($eCustomer['farm'])
			->whereEmail($eCustomer['email'])
			->whereStatus(\selling\Customer::ACTIVE)
			->exists();

		Contact::model()
			->whereFarm($eCustomer['farm'])
			->whereEmail($eCustomer['email'])
			->update([
				'activeCustomer' => $active
			]);

	}

	public static function synchronizeCustomerEmail(\selling\Customer $eCustomer): void {

		$eCustomer->expects(['email', 'oldEmail']);

		if($eCustomer['email'] === $eCustomer['oldEmail']) {
			return;
		}

		// On désactive l'ancien contact
		if($eCustomer['oldEmail'] !== NULL) {

			self::deleteCustomer(new \selling\Customer([
				'farm' => $eCustomer['farm'],
				'email' => $eCustomer['oldEmail']
			]));

		}

		// On recrée un contact
		\mail\ContactLib::autoCreate($eCustomer['farm'], $eCustomer['email']);

	}

	public static function deleteCustomer(\selling\Customer $eCustomer): void {

		$eCustomer->expects(['farm', 'email']);

		$exists = \selling\Customer::model()
			->whereFarm($eCustomer['farm'])
			->whereEmail($eCustomer['email'])
			->exists();

		if($exists === FALSE) {

			Contact::model()
				->whereFarm($eCustomer['farm'])
				->whereEmail($eCustomer['email'])
				->update([
					'active' => FALSE,
					'activeCustomer' => NULL
				]);

		}

	}

	public static function applySearch(\Search $search): void {

		if($search->empty()) {
			return;
		}

		Contact::model()
			->where('m1.email', 'LIKE', '%'.$search->get('email').'%', if: $search->get('email'))
			->whereOptIn(FALSE, if: $search->get('optIn') === 'no');

		switch($search->get('newsletter')) {

			case 'yes' :
				Contact::model()
					->whereNewsletter(TRUE)
					->or(
						fn() => $this->whereOptIn(TRUE),
						fn() => $this->whereOptIn(NULL)
					);

				break;

			case 'no' :
				Contact::model()
					->or(
						fn() => $this->whereNewsletter(FALSE),
						fn() => $this->whereOptIn(FALSE)
					);
				break;

		}

		if($search->get('export')) {

			self::applyExport();

		}

		if($search->get('category')) {

			Contact::model()
				->join(\selling\Customer::model(), 'm1.email = m2.email AND m1.farm = m2.farm')
				->where('m2.type', $search->get('category'));

		}

		if($search->get('shop')->notEmpty()) {

			$emails = \selling\Sale::model()
            ->select([
					'customer' => ['email']
				])
            ->whereShop($search->get('shop'))
            ->wherePreparationStatus(\selling\Sale::DELIVERED)
            ->group('customer')
            ->getColumn('customer')
				->getColumn('email');

			Contact::model()->whereEmail('IN', $emails);

		} else if($search->get('group')->notEmpty()) {

			Contact::model()
				->join(\selling\Customer::model(), 'm1.email = m2.email AND m1.farm = m2.farm')
				->where('JSON_CONTAINS(m2.groups, \''.$search->get('group')['id'].'\')');

		} else if($search->get('period')) {

			$emails = \selling\Sale::model()
            ->select([
					'customer' => ['type', 'email']
				])
				->whereOrigin('IN', [\selling\Sale::SALE_MARKET, \selling\Sale::SALE])
				->whereCustomer('!=', NULL)
			  	->whereDeliveredAt('>', new \Sql('NOW() - INTERVAL '.(int)$search->get('period').' MONTH'))
            ->wherePreparationStatus(\selling\Sale::DELIVERED)
            ->group('customer')
            ->getColumn('customer')
		  		->filter(fn($eCustomer) => $eCustomer['type'] === \selling\Customer::PRIVATE)
		  		->getColumn('email');

			Contact::model()->whereEmail('IN', $emails);

		}

	}

	public static function applyExport(): void {

		Contact::model()
			->or(
				fn() => $this->whereOptIn(TRUE),
				fn() => $this->whereOptIn(NULL)
			)
			->whereActive(TRUE)
			->or(
				fn() => $this->whereActiveCustomer(TRUE),
				fn() => $this->whereActiveCustomer(NULL)
			);

	}

	public static function get(\farm\Farm $eFarm, string $email, bool $autoCreate = FALSE, ?\Closure $autoCreateCallback = NULL): Contact {

		$eContact = Contact::model()
			->select(Contact::getSelection())
			->whereFarm($eFarm)
			->whereEmail($email)
			->get();

		if($eContact->empty() and $autoCreate) {
			$eContact = self::autoCreate($eFarm, $email, $autoCreateCallback);
		}

		return $eContact;

	}

	public static function getByCustomer(\selling\Customer $eCustomer, bool $autoCreate = FALSE, ?\Closure $autoCreateCallback = NULL): Contact {

		$eCustomer->expects(['farm', 'email']);

		if($eCustomer['email'] === NULL) {
			return new Contact();
		}

		return self::get($eCustomer['farm'], $eCustomer['email'], $autoCreate, $autoCreateCallback);

	}

	public static function getByEmail(Email $eEmail, bool $autoCreate = FALSE, ?\Closure $autoCreateCallback = NULL): Contact {

		$eEmail->expects(['farm', 'to']);

		return self::get($eEmail['farm'], $eEmail['to'], $autoCreate, $autoCreateCallback);

	}

	public static function autoCreate(\farm\Farm $eFarm, string $email, ?\Closure $callback = NULL): Contact {

		$eContact = new Contact([
			'farm' => $eFarm,
			'email' => $email
		]);

		if($callback) {
			$callback($eContact);
		}

		Contact::model()
			->option('add-ignore')
			->insert($eContact);

		Contact::model()
			->select(ContactElement::getSelection())
			->whereFarm($eFarm)
			->whereEmail($email)
			->get($eContact);

		$customers = \selling\Customer::model()
			->select([
				'count' => new \Sql('COUNT(*)', 'int'),
				'countValid' => new \Sql('SUM(status = "'.\selling\Customer::ACTIVE.'")', 'int')
			])
			->whereFarm($eFarm)
			->whereEmail($email)
			->get();

		if($customers['count'] === 0) {

			if($eContact['activeCustomer'] !== NULL) {

				$eContact['activeCustomer'] = NULL;

				Contact::model()
					->select('activeCustomer')
					->update($eContact);

			}

		} else {

			if($customers['countValid'] > 0) {

				if($eContact['activeCustomer'] !== TRUE) {

					$eContact['activeCustomer'] = TRUE;

					Contact::model()
						->select('activeCustomer')
						->update($eContact);

				}

			} else {

				if($eContact['activeCustomer'] !== FALSE) {

					$eContact['activeCustomer'] = FALSE;

					Contact::model()
						->select('activeCustomer')
						->update($eContact);

				}

			}

		}

		return $eContact;

	}

	public static function create(Contact $e): void {

		try {
			parent::create($e);
		} catch(\DuplicateException) {
			Contact::fail('email.duplicate');
		}

	}

	public static function createFromNewsletter(Contact $e): void {

		try {
			parent::create($e);
		} catch(\DuplicateException) {
			Contact::fail('email.duplicate');
		}

	}

	public static function updateOptInByEmail(\farm\Farm $eFarm, string $email, bool $optIn): void {

		Contact::model()->beginTransaction();

			$eContact = self::get($eFarm, $email, autoCreate: $optIn);

			if($eContact->notEmpty()) {

				$eContact['optIn'] = $optIn;
				$eContact['newsletter'] = $optIn;

				Contact::model()
					->select('optIn', 'newsletter')
					->update($eContact);

			}

		Contact::model()->commit();

	}

	public static function updateOptIn(\user\User $eUser, array $contacts): void {

		Contact::model()->beginTransaction();

		foreach($contacts as $farm => $optIn) {

			Contact::model()
				->whereEmail($eUser['email'])
				->whereFarm($farm)
				->update([
					'optIn' => (bool)$optIn
				]);

		}

		Contact::model()->commit();

	}

	public static function updateEmailStatus(Email $eEmail): void {

		$eEmail->expects(['id', 'contact', 'status']);

		$eContact = $eEmail['contact'];

		if($eContact->empty()) {
			return;
		}

		Contact::model()->beginTransaction();

			switch($eEmail['status']) {

				case Email::SENT :
					Contact::model()->update($eContact, [
						'sent' => new \Sql('sent + 1'),
						'lastSent' => new \Sql('NOW()'),
						'lastEmail' => $eEmail
					]);
					break;

				case Email::DELIVERED :
					Contact::model()->update($eContact, [
						'delivered' => new \Sql('delivered + 1'),
						'lastDelivered' => new \Sql('NOW()')
					]);
					break;

				case Email::OPENED :
					Contact::model()->update($eContact, [
						'opened' => new \Sql('opened + 1'),
						'lastOpened' => new \Sql('NOW()')
					]);
					break;

				case Email::ERROR_SPAM :
					Contact::model()->update($eContact, [
						'spam' => new \Sql('spam + 1'),
						'lastSpam' => new \Sql('NOW()')
					]);
					break;

				case Email::ERROR_BOUNCE :
				case Email::ERROR_BLOCKED :
				case Email::ERROR_INVALID :
					Contact::model()->update($eContact, [
						'failed' => new \Sql('failed + 1'),
						'lastFailed' => new \Sql('NOW()')
					]);
					break;

			}

		Contact::model()->commit();

	}

}
?>
