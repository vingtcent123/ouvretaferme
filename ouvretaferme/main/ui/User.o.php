<?php
namespace main;

class UserObserverUi {

	public static function signUpForm(\util\FormUi $form, \user\User $eUser, \user\Role $eRole) {

		$h = '';

		switch($eRole['fqn']) {

			case 'farmer' :
				$h .=  $form->hidden('type', \user\User::PRIVATE);
				$h .= $form->dynamicGroups($eUser, ['firstName', 'lastName', 'email', 'invoiceCountry']);
				break;

			default :
				$h .= $form->dynamicGroup($eUser, 'type');
				$h .= $form->dynamicGroups($eUser, ['firstName', 'lastName', 'email', 'invoiceCountry']);
				break;

		}


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

			$h .= '<div id="user-signup-company">';

				$h .= '<h3 class="mt-2 mb-2">'.s("Ma société").'</h3>';

				$h .= $form->dynamicGroup($eUser, 'siret');
				$h .= $form->dynamicGroup($eUser, 'legalName');
				$h .= $form->addressGroup(s("Adresse de facturation"), 'invoice', $eUser);

			$h .= '</div>';

			$h .= $form->hidden('tos', 1);

		}

		return $h;

	}

	public static function emailSignUp(\user\User $eUser) {

		$title = s("Bienvenue sur {siteName} !");

		$role = match($eUser['role']['fqn']) {

			'customer' => s("Vous pouvez désormais commander en ligne les produits de vos producteurs locaux préférés !"),
			'farmer' => s("Vous pouvez désormais créer la page de votre ferme pour commencer à utiliser le service !"),

			default => ''

		};

		$text = s("Bonjour,

{how}
{role}

{url}

À tout de suite sur {siteName},
L'équipe", ['how' => \user\UserUi::getSignUpType($eUser), 'role' => $role, 'url' => \Lime::getUrl()]);

		return [
			$title,
			$text
		];

	}

}
?>
