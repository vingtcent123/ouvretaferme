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

			'VatDeclaration:created' => s("La déclaration de TVA a bien été créée."),

			'DeferredCharge::saved' => s("La charge constatée d'avance a bien été enregistrée."),
			'DeferredCharge::deleted' => s("La charge constatée d'avance a bien été supprimée."),

			'AccruedIncome::created' => s("Le produit à recevoir a bien été enregistré."),
			'AccruedIncome::deleted' => s("Le produit à recevoir a bien été supprimé."),

			default => null

		};


	}

}
?>
