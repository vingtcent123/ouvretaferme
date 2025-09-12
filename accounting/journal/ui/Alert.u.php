<?php
namespace journal;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Operation::allocate.accountsCheck' => s("Veuillez sélectionner au moins une classe de compte."),
			'Operation::allocate.noOperation' => s("Aucune opération n'a pu être enregistrée."),

			'Operation::payment.noOperation' => s("Aucun paiement n'a pu être enregistré."),
			'Operation::payment.typeMissing' => s("Choisissez le type de paiement."),
			'Operation::paymentMethod.empty' => s("Le moyen de paiement est nécessaire."),

			'Operation::description.check' => s("Veuillez saisir un libellé"),

			'Operation::lettering.duplicate' => s("Ce code de lettrage est déjà utilisé."),

			'thirdParty.empty' => s("Choisissez un tiers pour ce paiement."),

			'Operation::date.check' => s("La date doit correspondre à l'exercice comptable actuellement ouvert."),
			'Operation::account.check' => s("N'oubliez pas de choisir une classe de compte !"),
			'Operation::accountLabel.inconsistency' => s("Le compte doit commencer par les mêmes chiffres que la classe de compte."),

			'Operation::invoice.incorrectType' => s("Le fichier n'est pas reconnu comme une facture. Vous pouvez effectuer la saisie manuellement ou réessayer."),
			'Operation::invoice.unknownExtension' => s("Le format du fichier n'est pas reconnu, veuillez essayer avec un autre fichier ou faire une saisie manuelle."),

			'Operation::document.empty' => s("Le nom de la pièce comptable est nécessaire pour enregistrer le document dans votre espace de stockage."),

			'Operation::FinancialYear.notUpdatable' => s("Il n'est plus possible d'écrire dans cet exercice comptable."),

			'Operation::selectedOperationInconsistency' => s("Les opérations sélectionnées ne sont pas cohérentes, veuillez rafraîchir la page et recommencer."),
			'Operation::selectedJournalCodeInconsistency' => s("Un problème technique est survenu avec le journal choisi. Veuillez rafraîchir la page et recommencer."),
			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Operation::payment.created' => s("Le paiement a bien été enregistré."),
			'Operation::payment.createdLettered' => s("Le paiement a bien été enregistré et lettré."),

			'Operation::created' => s("L'écriture a bien été enregistrée."),
			'Operation::createdSeveral' => s("Les écritures ont bien été enregistrées."),
			'Operation::deleted' => s("L'écriture a bien été supprimée."),

			'Operations::updated' => s("Les opérations ont été modifiées."),

			'VatDeclaration:created' => s("La déclaration de TVA a bien été créée."),

			'DeferralCharge::created' => s("La charge constatée d'avance a bien été enregistrée."),
			'DeferralProduct::created' => s("Le produit constaté d'avance a bien été enregistré."),
			'Deferral::deleted' => s("La suppression a bien été effectuée."),

			'Stock::created' => s("Le stock a bien été enregistré."),
			'Stock::deleted' => s("Le stock a bien été supprimé."),
			'Stock::set' => s("Le stock a bien été enregistré pour cet exercice fiscal."),
			'Stock::reset' => s("Le stock a bien été enregistré à 0 à la fin de cet exercice fiscal."),
			'Stock::renew' => s("Le stock a bien été enregistré identique à l'exercice précédent pour cet exercice fiscal."),

			default => null

		};


	}

}
?>
