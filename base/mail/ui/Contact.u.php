<?php
namespace mail;

class ContactUi {

	public static function p(string $property): \PropertyDescriber {

		$d = Customize::model()->describer($property, [
			'emailOptOut' => s("Opt-out"),
			'emailOptIn' => s("Opt-in"),
		]);

		switch($property) {

			case 'optOut' :
				$d->field = 'yesNo';
				$d->labelAfter = \util\FormUi::info(s("Envoyer des communications par e-mail à ce client"));
				break;

			case 'optIn' :
				$d->labelAfter = \util\FormUi::info(s("Consentement du client pour recevoir des communications par e-mail"));
				break;

		}

		return $d;

	}

}
?>
