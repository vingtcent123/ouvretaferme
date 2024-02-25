<?php
namespace farm;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Farm::disabled' => s("Vous avez désactivé cette fonctionnalité sur votre ferme."),
			'Farm::demo.delete' => s("Vous ne pouvez pas supprimer la démo !"),
			'Farm::name.check' => s("Merci de renseigner le nom de la ferme !"),
			'Farm::startedAt.check' => s("Merci de renseigner l'année de création de la ferme !"),
			'Farm::place.check' => s("Veuillez sélectionner une ville dans le menu déroulant."),
			'Farm::place.required' => s("Merci de renseigner la ville du siège d'exploitation de la ferme."),
			'Farm::defaultBedWidth.size' => s("La largeur de planche par défaut ne peut pas être inférieure à 5 cm."),
			'Farmer::demo.write' => s("Vous ne pouvez pas modifier l'équipe sur la démo !"),
			'Farmer::user.check' => s("Vous n'avez pas sélectionné d'utilisateur."),
			'Farmer::email.check' => s("Cette adresse e-mail est invalide."),
			'Farmer::email.duplicate' => s("Il y a déjà un utilisateur rattaché à votre ferme avec cette adresse e-mail..."),
			'Farmer::deleteGhost' => s("Vous ne pouvez pas supprimer un utilisateur créé spécifiquement pour la ferme."),
			'Farmer::deleteItself' => s("Vous ne pouvez pas vous sortir vous-même de la ferme."),

			'Action::deleteMandatory' => s("Cette intervention ne peut pas être supprimée car elle est indispensable au bon fonctionnement du site."),
			'Action::deleteUsed' => s("Vous ne pouvez pas supprimer une action qui est déjà utilisée sur les itinéraires techniques ou les séries..."),

			'Category::deleteMandatory' => s("Cette catégorie ne peut pas être supprimée car elle est indispensable au bon fonctionnement du site."),
			'Category::deleteUsed' => s("Vous ne pouvez pas supprimer une catégorie à laquelle sont toujours affectées des interventions..."),
			'Category::limitReached' => s("Vous avez atteint la limite de catégories que vous pouvez créer !"),

			'Supplier::deleteUsed' => s("Vous ne pouvez pas supprimer ce fournisseur de semences qui est actuellement utilisé sur un ou plusieurs variétés..."),

			'Tool::name.duplicate' => s("Vous avez déjà créé un élément de même nom."),
			'Tool::deleteUsed' => s("Vous ne pouvez pas supprimer un élément qui est déjà utilisé sur les itinéraires techniques ou les séries..."),

			'Invite::email.duplicate' => s("Une invitation a déjà été lancée pour cette adresse e-mail..."),
			'Invite::email.duplicateCustomer' => s("Cette adresse e-mail est déjà utilisée pour un autre client de votre ferme..."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Action::created' => s("L'intervention a bien été créée."),
			'Action::updated' => s("L'intervention a bien été mise à jour."),
			'Action::deleted' => s("L'intervention a bien été supprimée."),

			'Category::created' => s("La catégorie a bien été créée."),
			'Category::updated' => s("La catégorie a bien été mise à jour."),
			'Category::deleted' => s("La catégorie a bien été supprimée."),

			'Tool::created' => s("L'élément a bien été créé."),
			'Tool::updated' => s("L'élément a bien été mis à jour."),
			'Tool::deleted' => s("L'élément a bien été supprimé."),

			'Supplier::created' => s("Le fournisseur a bien été créé."),
			'Supplier::updated' => s("Le fournisseur a bien été mis à jour."),
			'Supplier::deleted' => s("Le fournisseur a bien été supprimé."),

			'Farm.created' => s("La ferme a bien été créée, à vous de jouer !"),
			'Farm.updated' => s("La ferme a bien été mise à jour !"),
			'Farm.updatedRotation' => s("La configuration des rotations de culture a bien été mise à jour !"),
			'Farm.updatedFeatures' => s("Les fonctionnalités activées et désactivées pour votre fermes ont bien été mises à jour !"),
			'Farm.closed' => s("La ferme a bien été supprimée !"),

			'Farmer::userCreated' => s("L'utilisateur a bien été créé et peut désormais être ajouté dans l'équipe de la ferme !"),
			'Farmer::userUpdated' => s("L'utilisateur a bien été mis à jour !"),
			'Farmer::userDeleted' => s("L'utilisateur a bien été supprimé !"),
			'Farmer::created' => s("L'utilisateur a bien été ajouté à l'équipe de la ferme !"),
			'Farmer::deleted' => s("L'utilisateur a bien été retiré de l'équipe de la ferme !"),

			'Invite::customerCreated' => s("Le client a bien été invité à créer un compte client sur {siteName} !"),
			'Invite::extended' => s("L'invitation a bien été prolongée et un e-mail avec un nouveau lien a été envoyé à la personne !"),
			'Invite::deleted' => s("L'invitation à rejoindre la ferme a bien été supprimée !"),

			default => null

		};


	}

}
?>
