<?php
namespace website;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Website::internalDomain.check' => s("Merci de saisir une adresse uniquement composées de caractères alphanumériques et de tirets."),
			'Website::internalDomain.duplicate' => s("Cette adresse est déjà utilisée par une autre ferme."),
			'Website::domain.duplicate' => s("Ce nom de domaine est déjà utilisé par une autre ferme."),
			'Website::farm.duplicate' => s("Vous avez déjà créé un site internet pour votre ferme."),

			'Webpage::url.duplicate' => s("Cette adresse est déjà utilisée par une autre de vos pages."),

			'Menu::webpage.duplicate' => s("Vous avez déjà ajouté cette page au menu."),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Website::created' => s("Le site internet de votre ferme a bien été créé et peut désormais être configuré !"),
			'Website::updated' => s("Le site internet a bien été mis à jour."),
			'Website::deleted' => s("Le site internet a bien été supprimé et n'est plus accessible."),
			'Website::customized' => s("Le style de votre site a bien été enregistré."),

			'Webpage::created' => s("La page a bien été créée et peut désormais être enrichie en textes et images !"),
			'Webpage::updated' => s("La page a bien été mise à jour."),
			'Webpage::contentUpdate' => s("Le nouveau contenu a bien été enregistré."),
			'Webpage::deleted' => s("La page a bien été supprimée et n'existe plus sur le site."),

			'Menu::created' => s("Le menu a bien été mis à jour."),
			'Menu::updated' => s("Le menu a bien été mis à jour."),
			'Menu::deleted' => s("La page a bien été supprimée du menu."),

			'News::created' => s("L'actualité a bien été ajoutée au statut de brouillon."),
			'News::updated' => s("L'actualité a bien été mise à jour."),
			'News::deleted' => s("L'actualité a bien été supprimée."),

			default => NULL

		};

	}

}
?>
