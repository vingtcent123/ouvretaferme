<?php
namespace mail;

class ContactLib {

	public static function updateContact(Email $eEmail): void {

		$eEmail->expects(['id', 'farm', 'to', 'status']);

		if($eEmail['farm']->empty()) {
			return;
		}

		Contact::model()->beginTransaction();

			$eContact = Contact::model()
				->select(Contact::getSelection())
				->whereFarm($eEmail['farm'])
				->whereEmail($eEmail['to'])
				->get();

			if($eContact->empty()) {

				$eContact = new Contact([
					'farm' => $eEmail['farm'],
					'email' => $eEmail['to']
				]);

				Contact::model()->insert($eContact);

			}

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
