<?php
namespace mail;

class ContactLib extends ContactCrud {

	public static function getByUser(\user\User $eUser): \Collection {

		return Contact::model()
			->select(Contact::getSelection() + [
				'farm' => ['name', 'vignette']
			])
			->whereEmail($eUser['email'])
			->getCollection();

	}

	public static function aggregateByFarm(\farm\Farm $eFarm, \Search $search = new \Search()): array {

		$search->validateSort(['email', 'createdAt']);

		self::applySearch($search);

		return Contact::model()
			->select([
				'count' => new \Sql('COUNT(*)', 'int'),
				'sent1' => new \Sql('SUM(lastSent > NOW() - INTERVAL 1 MONTH)', 'int'),
			])
			->whereFarm($eFarm)
			->get()
			->getArrayCopy();

	}

	public static function getByFarm(\farm\Farm $eFarm, \Search $search = new \Search()): \Collection {

		$search->validateSort(['email', 'createdAt']);

		self::applySearch($search);

		return Contact::model()
			->select(Contact::getSelection())
			->whereFarm($eFarm)
			->sort($search->buildSort())
			->getCollection();

	}

	public static function applySearch(\Search $search): void {

		Contact::model()
			->whereEmail('LIKE', '%'.$search->get('email').'%', if: $search->get('email'));

	}

	public static function get(\farm\Farm $eFarm, string $email, bool $autoCreate = FALSE): Contact {

		$eContact = Contact::model()
			->select(Contact::getSelection())
			->whereFarm($eFarm)
			->whereEmail($email)
			->get();

		if($eContact->empty() and $autoCreate) {
			$eContact = self::autoCreate($eFarm, $email);
		}

		return $eContact;

	}

	public static function getByCustomer(\selling\Customer $eCustomer, bool $autoCreate = FALSE): Contact {

		$eCustomer->expects(['farm', 'email']);

		if($eCustomer['email'] === NULL) {
			return new Contact();
		}

		return self::get($eCustomer['farm'], $eCustomer['email'], $autoCreate);

	}

	public static function getByEmail(Email $eEmail, bool $autoCreate = FALSE): Contact {

		$eEmail->expects(['farm', 'to']);

		return self::get($eEmail['farm'], $eEmail['to'], $autoCreate);

	}

	public static function autoCreate(\farm\Farm $eFarm, string $email): Contact {

		$eContact = new Contact([
			'farm' => $eFarm,
			'email' => $email
		]);

		Contact::model()
			->option('add-ignore')
			->insert($eContact);

		Contact::model()
			->select(ContactElement::getSelection())
			->whereFarm($eFarm)
			->whereEmail($email)
			->get($eContact);

		return $eContact;

	}

	public static function updateOptInByEmail(\farm\Farm $eFarm, string $email, bool $optIn): void {

		Contact::model()->beginTransaction();

			$eContact = self::get($eFarm, $email, autoCreate: $optIn);

			if($eContact->notEmpty()) {

				$eContact['optIn'] = $optIn;

				Contact::model()
					->select('optIn')
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
