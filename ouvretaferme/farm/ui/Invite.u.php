<?php
namespace farm;

class InviteUi {

	public function __construct() {

	}

	public function check(Invite $eInvite): string {

		if($eInvite->empty()) {

			$h = '<div class="util-block text-center">';
				$h .= '<br/><br/>';
				$h .= '<h2>'.s("Le lien que vous avez utilisé pour activer votre compte a expiré !").'</h2>';
				$h .= '<h4>'.s("Merci de demander à la ferme de vous envoyer un nouveau lien.").'</h4>';
				$h .= '<br/><br/>';
			$h .= '</div>';

			return $h;

		}

		return match($eInvite['type']) {
			Invite::CUSTOMER => $this->checkCustomer($eInvite),
			Invite::FARMER => $this->checkFarmer($eInvite)
		};

	}

	public function checkCustomer(Invite $eInvite): string {

		$eFarm = $eInvite['farm'];

		if($eInvite->isValid() === FALSE) {

			$h = '<div class="util-block text-center">';
				$h .= '<br/><br/>';
				$h .= '<h2>'.s("Le lien que vous avez utilisé pour activer votre compte client a expiré !").'</h2>';
				$h .= '<h4>'.s("Merci de demander à la ferme {farm} de vous envoyer un nouveau lien.", ['farm' => '<b>'.encode($eFarm['name']).'</b>']).'</h4>';
				$h .= '<br/><br/>';
			$h .= '</div>';

			return $h;

		}

		$eUser = \user\ConnectionLib::getOnline();

		if($eUser->empty()) {

			$h = '<div class="util-block text-center">';
				$h .= '<br/><br/>';
				$h .= '<h2>'.s("Vous n'êtes pas connecté sur {siteName} !").'</h2>';
				$h .= '<h4>'.s("Pour créer votre compte client {farm}, veuillez vous connecter sur {siteName} avec l'adresse e-mail {emailCurrent} ou créer un compte si vous n'en disposez pas.", ['farm' => '<b>'.encode($eFarm['name']).'</b>', 'emailCurrent' => '<b>'.encode($eInvite['email']).'</b>']).'</h4>';
				$h .= '<div>';
					$h .= '<a href="/user/signUp?invite='.$eInvite['key'].'" class="btn btn-secondary">'.s("Créer un compte").'</a> ';
					$h .= '<a href="/user/log:form?invite='.$eInvite['key'].'" class="btn btn-outline-secondary">'.s("Me connecter").'</a>';
				$h .= '</div>';
				$h .= '<br/><br/>';
			$h .= '</div>';

			return $h;

		}

		if($eUser['email'] !== $eInvite['email']) {

			$h = '<div class="util-block text-center">';
				$h .= '<br/><br/>';
				$h .= '<h2>'.s("Vous devez être connecté sur {siteName} avec {emailExpected} pour activer votre compte client ! Vous êtes actuellement connecté avec l'adresse e-mail {emailCurrent}.", ['emailExpected' => '<b>'.encode($eInvite['email']).'</b>', 'emailCurrent' => encode($eUser['email'])]).'</h2>';
				$h .= '<h4>'.s("Merci de vous déconnecter de {siteName} et de vous reconnecter avec la bonne adresse e-mail, ou bien de demander à la ferme {farm} de vous envoyer un lien sur {emailCurrent}}.", ['farm' => '<b>'.encode($eFarm['name']).'</b>', 'emailCurrent' => encode($eUser['email'])]).'</h4>';
				$h .= '<br/><br/>';
			$h .= '</div>';

			return $h;

		}

		return $this->checkDefault();

	}

	public function checkFarmer(Invite $eInvite): string {

		$eFarm = $eInvite['farm'];

		if($eInvite->isValid() === FALSE) {

			$h = '<div class="util-block text-center">';
				$h .= '<br/><br/>';
				$h .= '<h2>'.s("Le lien que vous avez utilisé pour rejoindre l'équipe de la ferme a expiré !").'</h2>';
				$h .= '<h4>'.s("Merci de demander à la ferme {farm} de vous envoyer un nouveau lien.", ['farm' => '<b>'.encode($eFarm['name']).'</b>']).'</h4>';
				$h .= '<br/><br/>';
			$h .= '</div>';

			return $h;

		}

		if($eInvite['farmer']['user']->notEmpty()) {

			$h = '<div class="util-block text-center">';
				$h .= '<br/><br/>';
				$h .= '<h2>'.s("Vous avez été invité sur {siteName} !").'</h2>';
				$h .= '<h4>'.s("Pour rejoindre l'équipe de la ferme {farm}, veuillez accepter cette invitation en choisissant un mot de passe qui vous permettra de vous connecter sur le site.", ['farm' => '<b>'.encode($eFarm['name']).'</b>']).'</h4>';
				$h .= '<br/><br/>';
			$h .= '</div>';

			$h .= $this->signUp($eInvite);

			return $h;

		}

		$eUser = \user\ConnectionLib::getOnline();

		if($eUser->empty()) {

			$h = '<div class="util-block text-center">';
				$h .= '<br/><br/>';
				$h .= '<h2>'.s("Vous n'êtes pas connecté sur {siteName} !").'</h2>';
				$h .= '<h4>'.s("Pour rejoindre l'équipe de la ferme {farm}, veuillez vous connecter sur {siteName} avec l'adresse e-mail {emailCurrent} ou créer un compte si vous n'en disposez pas.", ['farm' => '<b>'.encode($eFarm['name']).'</b>', 'emailCurrent' => '<b>'.encode($eInvite['email']).'</b>']).'</h4>';
				$h .= '<div>';
					$h .= '<a href="/user/signUp?invite='.$eInvite['key'].'" class="btn btn-secondary">'.s("Créer un compte").'</a> ';
					$h .= '<a href="/user/log:form?invite='.$eInvite['key'].'" class="btn btn-outline-secondary">'.s("Me connecter").'</a>';
				$h .= '</div>';
				$h .= '<br/><br/>';
			$h .= '</div>';

			return $h;

		}

		if($eUser['email'] !== $eInvite['email']) {

			$h = '<div class="util-block text-center">';
				$h .= '<br/><br/>';
				$h .= '<h2>'.s("Vous devez être connecté sur {siteName} avec {emailExpected} pour rejoindre l'équipe de la ferme ! Vous êtes actuellement connecté avec l'adresse e-mail {emailCurrent}.", ['emailExpected' => '<b>'.encode($eInvite['email']).'</b>', 'emailCurrent' => encode($eUser['email'])]).'</h2>';
				$h .= '<h4>'.s("Merci de vous déconnecter de {siteName} et de vous reconnecter avec la bonne adresse e-mail, ou bien de demander à la ferme {farm} de vous envoyer un lien sur {emailCurrent}}.", ['farm' => '<b>'.encode($eFarm['name']).'</b>', 'emailCurrent' => encode($eUser['email'])]).'</h4>';
				$h .= '<br/><br/>';
			$h .= '</div>';

			return $h;

		}

		return $this->checkDefault();

	}
	public function signUp(Invite $e): string {

		$form = new \util\FormUi([
			'firstColumnSize' => 40
		]);

		$h = $form->openAjax('/farm/invite:doAcceptUser', ['autocomplete' => 'off']);

		$h .= $form->hidden('key', $e['key']);

		$eUser = new \user\User([
			'email' => $e['email']
		]);

		$h .= $form->dynamicGroup($eUser, 'email');

		$h .= $form->group(
			s("Votre mot de passe"),
			$form->password('password', NULL, ['placeholder' => s("Mot de passe")])
		);

		$h .= $form->group(
			s("Retapez le mot de passe"),
			$form->password('passwordBis')
		);

		$h .= $form->group(
			content: $form->submit(s("S'inscrire"))
		);

		$h .= $form->close();

		return $h;

	}

	protected function checkDefault(): string {

		$h = '<div class="util-block text-center">';
			$h .= '<br/><br/>';
			$h .= '<h2>'.s("Vous ne pouvez pas accepter l'invitation pour le moment.").'</h2>';
			$h .= '<h4>'.s("Merci de réessayer ultérieurement...").'</h4>';
			$h .= '<br/><br/>';
		$h .= '</div>';

		return $h;

	}

	public function accept(Invite $eInvite): string {

		$h = '<div class="util-block text-center">';
			$h .= '<br/><br/>';

			switch($eInvite['type']) {

				case Invite::CUSTOMER :
					$h .= '<h2>'.s("Votre compte client {farm} a été activé !", ['farm' => '<b>'.encode($eInvite['farm']['name']).'</b>']).'</h2>';
					$h .= '<h4>'.s("Vous avez désormais accès à l'ensemble des fonctionnalités.").'</h4>';
					$h .= '<div>';
						$h .= '<a href="/" class="btn btn-secondary">'.s("Consulter mon compte client").'</a> ';
					$h .= '</div>';
					break;

				case Invite::FARMER :
					$h .= '<h2>'.s("Votre compte {farm} a été activé !", ['farm' => '<b>'.encode($eInvite['farm']['name']).'</b>']).'</h2>';
					$h .= '<h4>'.s("Vous avez désormais accès à l'ensemble des fonctionnalités.").'</h4>';
					$h .= '<div>';
						$h .= '<a href="/" class="btn btn-secondary">'.s("Découvrir la ferme").'</a> ';
					$h .= '</div>';
					break;

			}

			$h .= '<br/><br/>';
		$h .= '</div>';

		return $h;

	}

	public function createCustomer(\selling\Customer $eCustomer): \Panel {

		$form = new \util\FormUi();

		$eInvite = new Invite();

		$h = '';

		$h .= $form->openAjax('/farm/invite:doCreateCustomer');

			$h .= $form->hidden('customer', $eCustomer['id']);

			$description = '<div class="util-block-help">';
				$description .= '<p>'.s("En invitant un client à créer un compte client sur {siteName}, vous lui permettrez d'accéder aux données le concernant :").'</p>';
				$description .= '<ul>';
					$description .= '<li>'.s("Ses commandes passées et futures").'</li>';
					$description .= '<li>'.s("Ses données personnelles (numéro de téléphone, adresse de livraison...)").'</li>';
				$description .= '</ul>';
				$description .= '<p>'.s("Pour permettre à ce client de créer son compte, saisissez son adresse e-mail. Il recevra un e-mail lui donnant les instructions à suivre, et devra les réaliser dans un délai de trois jours.").'</p>';
			$description .= '</div>';

			$h .= $form->group(content: $description);
			$h .= $form->group(s("Nom du client"), '<b>'.encode($eCustomer['name']).'</b>');
			$h .= $form->dynamicGroup($eInvite, 'email');

			$h .= $form->group(
				content: $form->submit(s("Inviter le client"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Inviter un client à créer un compte"),
			body: $h
		);

	}

	/**
	 * Invitation à créer son compte
	 */
	public static function getInviteMail(Invite $e): array {

		return match($e['type']) {
			Invite::CUSTOMER => self::getInviteCustomerMail($e),
			Invite::FARMER => self::getInviteFarmerMail($e)
		};

	}

	public static function getInviteCustomerMail(Invite $e): array {

		$urlHash = \Lime::getUrl().'/farm/invite:check?key='.$e['key'];

		$title = s("{farm} vous invite à créer votre compte client !", ['farm' => $e['farm']['name']]);

		$text = s("Bonjour,

La ferme {farm} vous invite à créer votre compte client sur {siteName}.
Ce compte client vous permettra de consulter l'historique de vos commandes.

Utilisez le lien suivant dans votre navigateur pour activer votre compte client :
{url}

À bientôt,
L'équipe {siteName}", ['farm' => $e['farm']['name'], 'email' => $e['email'], 'url' => $urlHash]);


		return [
			$title,
			$text
		];

	}

	public static function getInviteFarmerMail(Invite $e): array {

		$urlHash = \Lime::getUrl().'/farm/invite:check?key='.$e['key'];

		$title = s("{farm} vous invite à rejoindre son équipe !", ['farm' => $e['farm']['name']]);

		$text = s("Bonjour,

La ferme {farm} vous invite à créer un compte {siteName} pour rejoindre l'équipe de la ferme.

Utilisez le lien suivant dans votre navigateur pour activer votre compte :
{url}

À bientôt,
L'équipe {siteName}", ['farm' => $e['farm']['name'], 'email' => $e['email'], 'url' => $urlHash]);


		return [
			$title,
			$text
		];

	}

	/**
	 * Invitation à créer son compte
	 */
	public static function getAcceptMail(Invite $e): array {

		return match($e['type']) {
			Invite::CUSTOMER => self::getAcceptCustomerMail($e),
			Invite::FARMER => self::getAcceptFarmerMail($e)
		};

	}

	public static function getAcceptCustomerMail(Invite $e): array {

		$title = s("Votre compte client {farm} a été créé !", ['farm' => $e['farm']['name']]);

		$text = s("Bonjour,

Vous avez accepté l'invitation à créer votre compte client {farm} sur {siteName}.

Vous pouvez accéder à toutes les informations vous concernant en utilisant le lien suivant :
{url}

À bientôt,
L'équipe {siteName}", ['farm' => $e['farm']['name'], 'email' => $e['email'], 'url' => \Lime::getUrl()]);


		return [
			$title,
			$text
		];

	}

	public static function getAcceptFarmerMail(Invite $e): array {

		$title = s("Vous avez rejoint l'équipe de {farm} !", ['farm' => $e['farm']['name']]);

		$text = s("Bonjour,

Vous avez accepté l'invitation à rejoindre l'équipe de {farm} avec succès sur {siteName}.

Vous pouvez accéder à la page de la ferme en utilisant le lien suivant :
{url}

À bientôt,
L'équipe {siteName}", ['farm' => $e['farm']['name'], 'email' => $e['email'], 'url' => \Lime::getUrl()]);


		return [
			$title,
			$text
		];

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Invite::model()->describer($property, [
			'email' => s("Adresse e-mail"),
		]);

		return $d;

	}

}
?>
