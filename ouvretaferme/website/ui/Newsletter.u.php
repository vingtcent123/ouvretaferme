<?php
namespace website;

class NewsletterUi {

	public function getForm(Website $eWebsite, bool $readOnly = FALSE): string {

		$form = new \util\FormUi();

		if($eWebsite['farm']['legalEmail'] === NULL) {
			return '<div class="util-box-danger">'.s("Le formulaire d'inscription à la lettre d'informations ne peut pas être affiché car le producteur n'a pas renseigné d'adresse e-mail pour sa ferme.").'</div>';
		}

		$h = $form->openAjax(WebsiteUi::url($eWebsite, '/:doNewsletter'), ['id' => 'website-newsletter']);

			$h .= $form->dynamicGroups(new Contact(), ['email']);

			if($readOnly) {
				$h .= $form->group(content: '<span class="btn btn-primary disabled">'.s("Valider l'inscription").'</span>');
			} else {
				$h .= $form->group(content: $form->submit(s("Valider l'inscription")));
			}

		$h .= $form->close();

		return $h;

	}

	public static function getFarmEmail(\mail\Contact $eContact): array {

		$title = s("Nouvelle inscription à votre lettre d'information sur votre site internet !");

		$content = s("Bonjour,

Une personne intéressée par votre lettre d'information s'est inscrite avec l'adresse e-mail :
<b>{email}</b>

Bonne réception,
L'équipe {siteName}", ['email' => encode($eContact['email'])]);

		return \mail\DesignUi::format($eContact['farm'], $title, $content, encapsulate: FALSE);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Contact::model()->describer($property, [
			'email' => s("Votre adresse e-mail"),
		]);

		switch($property) {

		}

		return $d;

	}

}
?>
