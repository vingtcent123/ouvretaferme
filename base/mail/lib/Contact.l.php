<?php
namespace mail;

class ContactLib extends ContactCrud {

	public static function getByCustomer(\selling\Customer $eCustomer): Contact {

		$eCustomer->expects(['farm', 'email']);

		return Contact::model()
			->select(Contact::getSelection())
			->whereFarm($eCustomer['farm'])
			->whereEmail($eCustomer['email'])
			->get();

	}

	public static function getByEmail(Email $eEmail, bool $autoCreate = FALSE): Contact {

		$eEmail->expects(['farm', 'to']);

		if($autoCreate) {
			Email::model()->beginTransaction();
		}

		$eContact = Contact::model()
			->select(Contact::getSelection())
			->whereFarm($eEmail['farm'])
			->whereEmail($eEmail['to'])
			->get();

		if($autoCreate) {

			if($eContact->empty()) {
				$eContact = self::createByEmail($eEmail);
			}

			Email::model()->commit();

		}

		return $eContact;

	}

	public static function createByEmail(Email $eEmail): Contact {

		$eEmail->expects(['farm', 'to']);

		$eContact = new Contact([
			'farm' => $eEmail['farm'],
			'email' => $eEmail['to']
		]);

		Contact::model()
			->option('add-ignore')
			->insert($eContact);

		return $eContact;

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
