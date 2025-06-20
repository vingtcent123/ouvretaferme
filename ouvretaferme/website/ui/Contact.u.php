<?php
namespace website;

class ContactUi {

	public function getForm(Website $eWebsite): string {

		$form = new \util\FormUi();

		if($eWebsite['farm']->selling()['legalEmail'] === NULL) {
			return '<div class="util-box-danger">'.s("Le formulaire de contact ne peut pas être affiché car le producteur n'a pas renseigné d'adresse e-mail pour sa ferme.").'</div>';
		}

		$h = $form->openAjax(WebsiteUi::url($eWebsite, '/:doContact'), ['id' => 'website-contact']);

			$h .= $form->dynamicGroups(new Contact(), ['name', 'email', 'title', 'content']);

			$h .= $form->group(content: $form->submit(s("Envoyer le message")));

		$h .= $form->close();

		return $h;

	}

	public static function getUserEmail(Contact $eContact): array {

		$title = s("Votre message pour {farm} a bien été envoyé !", ['farm' => $eContact['farm']['name']]);

		$content = s("Bonjour,

Votre message a bien été envoyé.
Voici pour rappel ce que vous avez écrit.

Objet : <b>{title}</b>

Message :

{message}", ['title' => encode($eContact['title']), 'message' => encode($eContact['content'])]);

		return \mail\DesignUi::format($eContact['farm'], $title, $content);

	}

	public static function getFarmEmail(Contact $eContact): array {

		$title = s("Vous avez reçu un message de {name} sur votre site internet !", ['name' => $eContact['name']]);

		$content = s("Bonjour,

{name} a laissé un message sur le site internet de votre ferme.

- Adresse e-mail : <b>{email}</b>
- Objet : <b>{title}</b>

Contenu du message :

{message}

Bonne réception,
L'équipe {siteName}", ['name' => encode($eContact['name']), 'email' => encode($eContact['email']), 'title' => encode($eContact['title']), 'message' => encode($eContact['content'])]);

		return \mail\DesignUi::format($eContact['farm'], $title, $content, encapsulate: FALSE);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Contact::model()->describer($property, [
			'title' => s("Objet de votre message"),
			'name' => s("Votre nom"),
			'email' => s("Votre adresse e-mail"),
			'content' => s("Votre message"),
		]);

		switch($property) {

		}

		return $d;

	}

}
?>
