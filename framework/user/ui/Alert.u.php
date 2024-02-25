<?php
namespace user;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {


			'User::demo.write' => s("Vous ne pouvez pas faire de telles bêtises sur le site de démo !"),
			'User::connectionBanned' => fn($eBan) => (new BanUi())->getConnectionBanned($eBan),
			'User::signUpBanned' => fn($eBan) => (new BanUi())->getSignUpBanned($eBan),
			'User::tos.accepted' => s("Veuillez accepter les conditions générales d'utilisation."),
			'User::connectionInvalid' => s("Impossible de se connecter, car vous avez sans doute saisi un mauvais identifiant ou mot de passe."),
			'User::phone.empty' => s("Veuillez indiquer un numéro de téléphone"),
			'User::firstName.empty' => s("Veuillez indiquer votre prénom"),
			'User::firstName.check' => s("Veuillez indiquer votre prénom"),
			'User::lastName.check' => s("Veuillez indiquer votre nom"),
			'User::country.check' => s("Veuillez indiquer votre pays"),
			'User::birthdate.check' => s("La date de naissance n'est pas correcte"),
			'User::gender.check' => s("Votre sexe n'existe pas"),
			'User::hashConnectedWrongAccount'=> s("Veuillez vous déconnecter de ce compte pour pouvoir confirmer votre adresse e-mail."),
			'User::email.check' => s("L'adresse e-mail n'est pas correcte"),
			'User::email.auth' => s("Vous ne pouvez pas changer d'adresse e-mail car vous n'êtes pas en authentification standard."),
			'User::email.empty' => s("Saisissez votre adresse e-mail ici !"),
			'User::email.duplicate' => s("Vous ne pouvez pas utiliser cette adresse e-mail, car elle a déjà utilisée pour s'inscrire sur {siteName} (<link>mot de passe oublié ?</link>).", ['link' => '<a href="/user/log:forgottenPassword">']),
			'User::address.empty' => s("Vous devez saisir au moins la première ligne de l'adresse, un code postal et une ville pour que votre adresse soit complète !"),
			'User::addressMandatory.check' => s("Merci de saisir votre adresse !"),
			'User::invalidHash' => s("Désolé, ce code de confirmation n'est pas valide."),
			'User::internal' => s("Une erreur interne est survenue."),

			'UserAuth::password.match' => s("Vous avez entré deux mots de passe différents"),
			'UserAuth::password.check' => p("Votre mot de passe doit contenir au minimum {value} caractère", "Votre mot de passe doit contenir au minimum {value} caractères", \Setting::get('passwordSizeMin')),
			'UserAuth::passwordOld.invalid' => s("Votre mot de passe actuel n'est pas correct"),


			default => NULL

		};


	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'User::welcomeCreate'=> s("Bienvenue sur {siteName}, votre compte a bien été créé !"),
			'User::welcome' => s("Vous êtes maintenant connecté sur {siteName} !"),
			'User::bye' => s("Vous êtes maintenant déconnecté de {siteName}."),
			'User::adminUpdated' => s("L'utilisateur a bien bien été mis à jour."),
			'User::updated' => s("Vos informations personnelles ont bien été mises à jour."),
			'User::emailUpdated' => s("Votre adresse e-mail a bien été mise à jour."),
			'User::passwordUpdated' => s("Votre mot de passe a bien été mis à jour."),
			'User::passwordReset' => s("Votre mot de passe a bien été réinitialisé, vous pouvez maintenant vous connecter avec votre adresse e-mail et ce nouveau mot de passe !"),
			'User::forgottenPasswordSend' => s("Un e-mail avec un lien vient de vous être envoyé."),
			'User::invalidLinkForgot'=> s("Le lien pour réinitialiser le mot de passe n'est plus valide."),
			default => NULL

		};


	}

}
?>
