<?php
namespace user;

class UserUi {

	use \Notifiable;

	public function __construct() {
		\Asset::css('user', 'user.css');
	}

	public static function name(User $eUser): string {

		$eUser->expects(['firstName', 'lastName']);

		if($eUser['firstName'] === NULL) {
			return encode($eUser['lastName']);
		} else {
			return encode($eUser['firstName']).' '.encode($eUser['lastName']);
		}

	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE): void {

		$d->prepend = \Asset::icon('person-fill');
		$d->field = 'autocomplete';

		$d->placeholder = s("Tapez un nom ou une adresse e-mail...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = '/user/search:query';
		$d->autocompleteResults = function(User $e) {
			return self::getAutocomplete($e);
		};

	}

	public static function getAutocomplete(User $eUser): array {

		$item = self::getVignette($eUser, '2.5rem');
		$item .= '<span>'.\user\UserUi::name($eUser).'</span>';

		return [
			'value' => $eUser['id'],
			'itemText' => ($eUser['firstName'] === NULL) ? $eUser['lastName'] : $eUser['firstName'].' '.$eUser['lastName'],
			'itemHtml' => $item
		];

	}
	
	/**
	 * Get a login form
	 *
	 * @return string
	 */
	public function logInBasic(?string $email = NULL): string {

		$redirect = REQUEST('redirect');

		$form = new \util\FormUi();

		$h = '<div class="login-form">';

		$h .= $form->openAjax('/user/log:in');

			$h .= $form->hidden('redirect', $redirect);

			$h .= $form->group(
				s("Adresse e-mail"),
				$form->email('login', $email)
			);

			$h .= $form->group(
				s("Mot de passe"),
				$form->password('password')
			);


			$h .= $form->group(
				NULL,
				$form->checkbox('remember', 1, ['checked' => TRUE, 'callbackLabel' => fn($input) => $input.' '.s("Se souvenir de moi")]),
				['class' => 'login-remember']
			);

			$submit = '<div class="login-submit">';
				$submit .= $form->submit(s("Se connecter"));
				$submit .= '<div class="login-forgotten-password">
					<a href="/user/log:forgottenPassword">'.s("Mot de passe oublié ?").'</a>
				</div>';
			$submit .= '</div>';

			$h .= $form->group(content: $submit);

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	/**
	 * Groups all the social signup forms
	 *
	 * @return string
	 */
	public function signUp(User $e, Role $eRole, ?string $redirect = NULL): string {

		$form = new \util\FormUi([
			'firstColumnSize' => 40
		]);

		$h = $form->openAjax('/user/signUp:doCreate', ['autocomplete' => 'off']);

			$h .= implode('', self::notify('signUpForm', $form));

			$h .= $form->hidden('redirect', $redirect);

			$h .= $form->hidden('role', $eRole['id']);

			$h .= $form->dynamicGroups($e, ['firstName', 'lastName', 'email']);

			$h .= $form->group(
				s("Votre mot de passe"),
				$form->password('password', NULL, ['placeholder' => s("Mot de passe")])
			);

			$h .= $form->group(
				s("Retapez le mot de passe"),
				$form->password('passwordBis')
			);

			if($eRole['fqn'] === 'farmer') {

				$h .= $form->group(
					s("J'accepte les <link>conditions d'utilisation du service</link>", ['link' => '<a href="/presentation/service" target="_blank">']),
					$form->inputCheckbox('tos', 1, ['id' => 'tos'])
				);

			} else {
				$h .= $form->hidden('tos', 1);
			}

			$h .= $form->group(
				content: $form->submit(s("S'inscrire"))
			);

		$h .= $form->close();

		return $h;

	}

	/**
	 * Get a forgotten password form
	 *
	 */
	public function forgottenPassword(): string {

		$form = new \util\FormUi();

		$h = '<div class="util-info">'.s("Si vous avez oublié votre mot de passe, indiquez l'adresse e-mail avec laquelle vous vous êtes inscrit sur {siteName}.
Vous recevrez alors un e-mail contenant un lien vous permettant d'en choisir un nouveau.").'</div>';

		$h .= $form->openAjax('/user/forgotten:do');

		$h .= $form->group(
			s("Votre adresse e-mail"),
			$form->email('email')
		);

		$h .= $form->group(
			content: $form->submit(s("Recevoir les instructions par e-mail"))
		);

		$h .= $form->close();

		return $h;
	}

	/**
	 * Change personal data
	 */
	public function update(User $eUser): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/user/update:doUpdate');

		$h .= $form->group(
			self::p('email')->label,
			$form->inputGroup(
				$form->addon(\Asset::icon('envelope-fill')).
				'<div class="form-control disabled">'.encode($eUser['email']).'</div>'.
				'<a href="/user/settings:updateEmail"" class="btn btn-primary">'.\Asset::icon('pencil-fill').'</a>'
			)
		);
		$h .= $form->dynamicGroups($eUser, ['firstName', 'lastName', 'phone']);
		$h .= $form->addressGroup(s("Adresse"), NULL, $eUser);

		$h .= $form->group(
			content: $form->submit(s("Modifier"), ['class' => 'btn btn-primary'])
		);

		$h .= $form->close();

		return $h;

	}


	/**
	 * Change an email
	 */
	public function updateEmail(User $eUser): string {

		if($eUser['bounce'] === TRUE) {
			$h = '<div class="util-warning">'.s("Le dernier e-mail que nous avons tenté de vous adresser a été rejeté, car votre adresse e-mail semble incorrecte. Veuillez s'il vous plait la mettre à jour avant de continuer sur {siteName}.").'</div>';
		} else {
			$h = '';
		}
		$form = new \util\FormUi();

		$h .= $form->openAjax('/user/update:doEmail');

		$h .= $form->group(
			s("Nouvelle adresse e-mail"),
			$form->email('email', $eUser['email'])
		);

		if($eUser['bounce']) {
			$h .= '<br/>';
			$h .= '<p class="color-danger">'.\Asset::icon('exclamation-triangle-fill').'&nbsp;'.s("Cette adresse e-mail ne fonctionne pas, merci de renseigner une nouvelle adresse e-mail.").'</p>';
		}

		$h .= $form->group(
			content: $form->submit(s("Modifier mon e-mail"), ['class' => 'btn btn-primary'])
		);

		$h .= $form->close();

		if($eUser['email'] !== NULL and $eUser['bounce'] === FALSE) {

			$isVerified = MailLib::isVerified($eUser);

			$h .= '<br/><br/>';

			$h .= '<div class="util-block-requirement">';

			if($isVerified) {
				$h .= '<p class="color-success">'.\Asset::icon('check').'&nbsp;'.s("Adresse e-mail validée").'</p>';
			} else {
				$h .= $this->sendConfirmationMail($eUser);
			}

			$h .= '</div>';

		}

		return $h;

	}

	/**
	 * Send my confirmation email again
	 */
	protected function sendConfirmationMail(User $eUser): string {

		$h = '<p class="color-warning">';
			$h .= \Asset::icon('exclamation-triangle-fill').'&nbsp;'.s("Vous n'avez pas encore vérifié votre adresse e-mail !");
		$h .= '</p>';

		$h .= '<a data-ajax="/mail/verify:doSend" class="btn btn-warning">'.s("Recevoir le mail de confirmation").'</a>';

		return $h;

	}


	/**
	 * Change a password
	 */
	public function updatePassword(User $eUser, string $hash = NULL, string $email = NULL): string {

		$form = new \util\FormUi();

		if($hash === NULL) {
			$url = '/user/update:doPassword';
		} else {
			$url = '/user/forgotten:doReset';
		}

		$h = $form->openAjax($url);

		$passwordText1 = s("Nouveau mot de passe");
		$passwordText2 = s("Encore mon nouveau mot de passe");
		$textButton = s("Modifier mon mot de passe");

		if($hash !== NULL and $email !== NULL) {

			$h .= $form->hidden('hash', $hash);
			$h .= $form->hidden('email', $email);

		} else if($eUser['canUpdate']['hasPassword'] === FALSE) {

			$type = first($eUser['canUpdate']['cUserAuth'])['type'];
			$h .= '<div class="util-info">'.
				s("En créant un mot de passe vous pourrez vous connecter sur {siteName} sans passer par {social}, en utilisant directement directement l'e-mail et le mot de passe que vous aurez fournis.", ['social' => ucfirst($type)]).
			'</div>';
			$passwordText1 = s("Mon mot de passe");
			$passwordText2 = s("Encore mon mot de passe");

			$textButton = s("Créer mon mot de passe");

			if($eUser['email'] === NULL) {

				$h .= $form->group(
					s("Mon adresse e-mail"),
					$form->email('email')
				);

			} else {

				$h .= $form->group(
					s("Mon adresse e-mail"),
					'<u>'.encode($eUser['email']).'</u>'
				);
				$h .= $form->hidden('email', $eUser['email']);

			}

		} else {

			$h .= $form->group(
				s("Mot de passe actuel"),
				$form->password('passwordOld')
			);

		}

		$h .= $form->group(
			$passwordText1,
			$form->password('password')
		);

		$h .= $form->group(
			$passwordText2,
			$form->password('passwordBis')
		);

		$h .= $form->group(
			content: $form->submit($textButton, ['class' => 'btn btn-primary'])
		);

		$h .= $form->close();

		return $h;

	}

	public function logOutExternal(User $eUser): string {

		if($eUser['email']) {
			$connected = s("Vous êtes connecté sur le compte de {user}", ['user' => encode($eUser['email'])]);
		} else {
			$connected = s("Vous êtes connecté sur le compte de l'utilisateur #{id}", ['id' => $eUser['id']]);
		}

		$h = '<div class="logout-external">';
			$h .= $connected;
			$h .= '<br/>';
			$h .= '<a data-ajax="/user/log:doLogoutExternal">'.s("Revenir sur votre compte").'</a>';
		$h .= '</div>';

		return $h;

	}

	public static function getVignette(User $eUser, string $size): string {

		$class = 'media-circle-view';
		$style = '';

		$ui = new \media\UserVignetteUi();

		if($eUser->empty()) {

			$class .= ' media-vignette-default '.$class;
			$style .= 'background-color: #EEE;';
			$content = '@';
			$title = '';

		} else {

			$eUser->expects(['id', 'vignette', 'firstName', 'lastName']);

			if($eUser['vignette'] !== NULL) {

				$format = $ui->convertToFormat($size);

				$style .= 'background-image: url('.$ui->getUrlByElement($eUser, $format).');';
				$class .= ' media-vignette-image';

				$content = '';

			} else {

				list(
					$color,
					$content
				) = self::getDefault($eUser);

				$class .= ' media-vignette-default';
				$style .= 'background-color:'.$color.';';

			}

			$title = self::name($eUser);

		}

		return '<div class="'.$class.'" style="'.$ui->getSquareCss($size).'; '.$style.'" title="'.encode($title).'">'.$content.'</div>';

	}

	/**
	 * Get the subject the text and html body of the email to reset the password mail.
	 *
	 * @param string $hash
	 * @param string $email
	 *
	 * @return [string, string, string]
	 */
	public static function getForgottenPasswordMail(string $hash, string $email): array {

		$title = s("Réinitialisez votre mot de passe sur {siteName} !");

		$text = s("Bonjour,

Vous recevez ce message parce que vous avez utilisé cette adresse e-mail pour vous inscrire sur {siteName} et que vous souhaitez réinitialiser votre mot de passe.
Utilisez le lien suivant dans votre navigateur pour réinitialiser votre mot de passe :
{url}

Attention, ce lien n'est valable que pendant 24 heures, après quoi il ne sera plus utilisable.

L'équipe", ['url' => self::getForgottenPasswordLink($hash, $email)]);


		return [
			$title,
			$text
		];
	}

	public static function getForgottenPasswordLink(string $hash, string $email): string {
		return \Lime::getUrl().'/user/forgotten:set?hash='.$hash.'&email='.urlencode($email);
	}

	/**
	 * Mail to verify the email address after
	 * - the user asked to receive the confirmation email
	 * - the user changed his email address
	 *
	 */
	public static function getVerifyMail(User $eUser, string $hash, bool $change): array {

		$urlHash = \Lime::getUrl().'/mail/verify:check?hash='.$hash;

		if($change === TRUE) {

			$title = s("Confirmez votre nouvelle adresse e-mail sur {siteName} !");

			$text = s("Bonjour,

Vous recevez ce message parce que vous avez choisi l'adresse {email} sur {siteName}.

Utilisez le lien suivant dans votre navigateur pour confirmer votre e-mail :
{url}

L'équipe", ['email' => encode($eUser['email']), 'url' => $urlHash]);

		} else {

			$title = s("Confirmez votre adresse e-mail sur {siteName} !");

			$text = s("Bonjour,

Vous recevez ce message parce que vous avez demandé à confirmer votre adresse e-mail sur {siteName}.

Utilisez le lien suivant dans votre navigateur pour le faire :
{url}

L'équipe", ['url' => $urlHash]);

		}

		return [
			$title,
			$text
		];

	}

	/**
	 * Get the subject the text and html body of the email confirmation mail.
	 *
	 * @param string $hash
	 * @return array
	 */
	public static function getSignUpMail(User $eUser): array {

		$notify = self::notify('emailSignUp', $eUser);

		if($notify) {
			return first($notify);
		}

		$title = s("Bienvenue sur {siteName} !");

		$text = s("Bonjour,

{how}
Vous avez maintenant accès à toutes les fonctionnalités du site.

{url}

À tout de suite sur {siteName},
L'équipe", ['how' => self::getSignUpType($eUser), 'url' => \Lime::getUrl()]);

		return [
			$title,
			$text
		];

	}

	/**
	 * Send my confirmation email again
	 */
	public static function getSignUpType(User $eUser): string {

		$eUser->expects([
			'email',
			'auth' => ['type']
		]);

		switch($eUser['auth']['type']) {

			case UserAuth::IMAP :
				return s("Vous venez de vous inscrire sur {siteName} en utilisant un compte IMAP !");

			case UserAuth::BASIC :
				return s("Vous venez de vous inscrire sur {siteName} avec votre adresse e-mail {value}.", ['value' => encode($eUser['email'])]);

		}

	}

	public static function getCloseMail(): array {

		$title = s("Fermeture de votre compte {siteName}");

		$text = s("Bonjour,

Vous recevez ce message parce que vous avez décidé de fermer votre compte sur {siteName}.
Nous avons bien enregistré votre demande.

Vous avez encore 10 jours pour changer d'avis. Passé ce délai, vos données seront définitivement supprimées. Pensez à faire une sauvegarde si vous souhaitez les conserver.

L'équipe");

		return [
			$title,
			$text,
		];
	}

	protected static function getDefault(User $eUser): array {

		$colors = [
			'#606ec9',
			'#9dd53a',
			'#f0b7a1',
			'#c4c960',
			'#cc6163',
			'#cb60b3',
			'#b361cc',
			'#60c4c9',
			'#8829fb',
			'#c9a460',
			'#08c08c',
			'#3585de',
			'#b3a6de',
			'#cea17f'
		];

		if($eUser['firstName'] === NULL) {
			$letters = mb_substr($eUser['lastName'], 0, 2);
		} else {
			$letters = mb_substr($eUser['firstName'], 0, 1).mb_substr($eUser['lastName'], 0, 1);
		}

		return [
			$colors[crc32($eUser['id']) % count($colors)],
			mb_strtoupper($letters)
		];
	}

	public static function p(string $property): \PropertyDescriber {

		$d = User::model()->describer($property, [
			'role' => s("Profil"),
			'email' => s("Adresse e-mail"),
			'phone' => s("Numéro de téléphone"),
			'lastName' => s("Nom"),
			'firstName' => s("Prénom"),
			'birthdate' => s("Date de naissance"),
			'street' => s("Adresse"),
			'postcode' => s("Code postal"),
			'city' => s("Ville"),
		]);

		switch($property) {

			case 'role' :
				$d->field = function(\util\FormUi $form, User $e) {

					$cRole = $e['cRole'] ?? $e->expects(['cRole']);

					$h = '<div class="input-group user-field-role">';

					foreach($cRole as $eRole) {
						$h .= '<label class="btn">';
							$h .= $form->inputRadio('role', $eRole['id']);
							$h .= '<div>';
								if($eRole['emoji']) {
									$h .= '<p class="fs-3">'.encode($eRole['emoji']).'</p>';
								}
								$h .= encode($eRole['name']);
							$h .= '</div>';
						$h .= '</label>';
					}

					$h .= '</div>';

					return $h;

				};
				$d->values = fn(User $e) => $e['cRole'] ?? $e->expects(['cRole']);
				$d->attributes['mandatory'] = TRUE;
				break;

			case 'birthdate' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

			case 'firstName' :
			case 'lastName' :
			case 'email' :
			case 'phone' :
				$d->placeholder = $d->label;
				break;

		}

		return $d;

	}

}
?>
