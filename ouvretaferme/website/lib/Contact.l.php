<?php
namespace website;

class ContactLib extends ContactCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'email', 'title', 'content'];
	}

	public static function create(Contact $e): void {

		$farmEmail = $e['farm']['legalEmail'];

		if($farmEmail === NULL) {
			throw new \Exception('Missing farm email');
		}

		parent::create($e);

		\farm\Farm::model()
			->select(\farm\FarmElement::getSelection())
			->get($e['farm']);

		new \mail\MailLib()
			->setFarm($e['farm'])
			->setFromName($e['farm']['name'])
			->setReplyTo($farmEmail)
			->setTo($e['email'])
			->setContent(...ContactUi::getUserEmail($e))
			->send();

		new \mail\MailLib()
			->setTo($farmEmail)
			->setReplyTo($e['email'])
			->setContent(...ContactUi::getFarmEmail($e))
			->send();


	}

}
?>
