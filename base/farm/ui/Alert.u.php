<?php
namespace farm;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn, array $options): mixed {

		return match($fqn) {

			'Configuration::error' => s("Il y a des erreurs à corriger sur le formulaire."),
			'Configuration::creditPrefix.fqn' => s("Ne doit pas finir par un chiffre, et contenir uniquement des caractères alphanumériques ou un tiret"),
			'Configuration::invoicePrefix.fqn' => s("Ne doit pas finir par un chiffre, et contenir uniquement des caractères alphanumériques ou un tiret"),
			'Configuration::documentInvoices.consistency' => fn($e) => s("Par souci de cohérence avec les factures existantes, le numéro ne peut pas être inférieur à {value}", $e['invoicePrefixMin'] + 1),
			'Configuration::invoiceDueMonth.consistency' => s("Vous devez préciser la date d'échéance par rapport à la date de facturation"),
			'Configuration::saleClosing.check' => s("La clôture automatique peut être configurée entre 7 et 90 jours"),
			'Configuration::vatNumber.check' => fn() => \farm\AlertUi::getErrorVatNumber($options[0]),
			'Configuration::vatNumber.country' => s("Vous ne pouvez pas saisir de numéro de TVA intracommunautaire si votre ferme n'est ni en France, ni en Belgique."),

			'Farm::disabled' => s("Vous avez désactivé cette fonctionnalité sur votre ferme."),
			'Farm::demo.delete' => s("Vous ne pouvez pas supprimer la démo !"),
			'Farm::legalEmail.empty' => s("Merci de renseigner l'adresse e-mail de la ferme !"),
			'Farm::name.check' => s("Merci de renseigner le nom de la ferme !"),
			'Farm::siret.empty' => s("Un numéro de SIRET à 14 chiffres est obligatoire pour utiliser le service de facturation."),
			'Farm::siret.check' => self::getErrorSiret(),
			'Farm::cultivationPlace.check' => s("Veuillez sélectionner une ville dans le menu déroulant."),
			'Farm::defaultBedWidth.size' => s("La largeur de planche par défaut ne peut pas être inférieure à 5 cm."),
			'Farmer::demo.write' => s("Vous ne pouvez pas modifier l'équipe sur la démo !"),
			'Farmer::user.check' => s("Vous n'avez pas sélectionné d'utilisateur."),
			'Farmer::email.check' => s("Cette adresse e-mail est invalide."),
			'Farmer::email.duplicate' => s("Il y a déjà un utilisateur rattaché à votre ferme avec cette adresse e-mail..."),
			'Farmer::deleteGhost' => s("Vous ne pouvez pas supprimer un utilisateur créé spécifiquement pour la ferme."),
			'Farmer::deleteItself' => s("Vous ne pouvez pas vous sortir vous-même de la ferme."),
			'Farm::notLegal' => '<p>'.s("Nous avons besoin de quelques informations administratives de base à propos de votre ferme (numéro d'immatriculation, raison sociale...) pour accéder à cette page !").'</p><a href="/farm/farm:updateLegal?id='.$options['farm']['id'].'" class="btn '.($options['btn'] ?? 'btn-transparent').'">'.s("Compléter mes informations").'</a>',
			'Farm::notTax' => '<p>'.s("Vous devez vérifier le pays de votre entité avant de pouvoir accéder à cette page.").'</p>',

			'Action::deleteMandatory' => s("Cette intervention ne peut pas être supprimée car elle est indispensable au bon fonctionnement du site."),
			'Action::deleteUsed' => s("Vous ne pouvez pas supprimer une action qui est déjà utilisée sur les itinéraires techniques ou les séries..."),

			'Category::deleteMandatory' => s("Cette catégorie ne peut pas être supprimée car elle est indispensable au bon fonctionnement du site."),
			'Category::deleteUsed' => s("Vous ne pouvez pas supprimer une catégorie à laquelle sont toujours affectées des interventions..."),
			'Category::limitReached' => s("Vous avez atteint la limite de catégories que vous pouvez créer !"),

			'Supplier::deleteUsed' => s("Vous ne pouvez pas supprimer ce fournisseur de semences qui est actuellement utilisé sur un ou plusieurs variétés..."),

			'Tool::name.duplicate' => s("Vous avez déjà créé un élément de même nom."),
			'Tool::routineValue.tray' => s("Vous devez indiquer un nombre de mottes par plateau."),

			'Invite::email.duplicate' => s("Une invitation a déjà été lancée pour cette adresse e-mail..."),
			'Invite::email.duplicateCustomer' => s("Cette adresse e-mail est déjà utilisée pour un autre client de votre ferme..."),

			'Accounting::invalidDatesForFec' => s("Vérifiez la période sélectionnée"),

			'Survey::farm.duplicate' => s("Vous avez déjà répondu pour cette ferme"),

			default => null

		};

	}

	public static function getErrorSiret(): string {
		return s("Le numéro de SIRET doit contenir exactement 14 chiffres.");
	}

	public static function getErrorVatNumber(string $country): string {
		return match($country) {
			'FR' => s("Le numéro de TVA intracommunautaire pour une entité française doit commencer par FR et contenir 11 chiffres"),
			'BE' => s("Le numéro de TVA intracommunautaire pour une entité belge doit commencer par BE et contenir 10 chiffres"),
		};
	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Configuration::updated' => s("La configuration a bien été mis à jour."),

			'Action::created' => s("L'intervention a bien été créée."),
			'Action::updated' => s("L'intervention a bien été mise à jour."),
			'Action::updatedActive' => s("L'intervention a bien été réactivée."),
			'Action::updatedInactive' => s("L'intervention a bien été désactivée."),
			'Action::deleted' => s("L'intervention a bien été supprimée."),

			'Method::created' => s("La méthode a bien été créée."),
			'Method::deleted' => s("La méthode a bien été supprimée."),

			'Category::created' => s("La catégorie a bien été créée."),
			'Category::updated' => s("La catégorie a bien été mise à jour."),
			'Category::deleted' => s("La catégorie a bien été supprimée."),

			'Tool::created' => s("L'élément a bien été créé."),
			'Tool::updated' => s("L'élément a bien été mis à jour."),
			'Tool::deleted' => s("L'élément a bien été supprimé."),

			'Supplier::created' => s("Le fournisseur a bien été créé."),
			'Supplier::updated' => s("Le fournisseur a bien été mis à jour."),
			'Supplier::deleted' => s("Le fournisseur a bien été supprimé."),

			'Farm::updated' => s("La ferme a bien été mise à jour !"),
			'Farm::updatedProduction' => s("La configuration de la production a bien été mise à jour !"),
			'Farm::updatedLegal' => s("Les informations de votre ferme ont bien été mises à jour !"),
			'Farm::updatedEmail' => s("La ferme a bien été mise à jour !"),
			'Farm::closed' => s("La ferme a bien été supprimée !"),

			'Farmer::userCreated' => s("L'utilisateur a bien été créé et peut désormais être ajouté dans l'équipe de la ferme !"),
			'Farmer::userUpdated' => s("L'utilisateur a bien été mis à jour !"),
			'Farmer::userDeleted' => s("L'utilisateur a bien été supprimé !"),
			'Farmer::created' => s("L'utilisateur a bien été ajouté à l'équipe de la ferme !"),
			'Farmer::deleted' => s("L'utilisateur a bien été retiré de l'équipe de la ferme !"),
			'Farmer::welcome' => s("Vous êtes désormais considéré comme producteur / productrice sur {siteName} !"),

			'Invite::customerCreated' => s("L'invitation a bien été créée !"),
			'Invite::extended' => s("L'invitation a bien été prolongée et un e-mail avec un nouveau lien a été envoyé à la personne !"),
			'Invite::deleted' => s("L'invitation à rejoindre la ferme a bien été supprimée !"),

			default => null

		};


	}

}
?>
