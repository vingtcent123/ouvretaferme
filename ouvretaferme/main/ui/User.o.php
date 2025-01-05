<?php
namespace main;

class UserObserverUi {

	public static function signUpFormBottom(\util\FormUi $form, \user\Role $eRole) {

		if($eRole['fqn'] === 'farmer') {

			return $form->group(
				s("J'accepte les <link>conditions d'utilisation du service</link>", ['link' => '<a href="/presentation/service" target="_blank">']),
				$form->inputCheckbox('tos', 1, ['id' => 'tos'])
			);

		} else {
			return $form->hidden('tos', 1);
		}

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
