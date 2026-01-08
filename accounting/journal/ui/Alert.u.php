<?php
namespace journal;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Operation::accountLabel.check' => s("Sélectionnez un numéro de compte proposé."),

			'Operation::allocate.accountsCheck' => s("Veuillez sélectionner au moins un numéro de compte."),
			'Operation::allocate.noOperation' => s("Aucune opération n'a pu être enregistrée."),

			'Operation::paymentMethod.empty' => s("Le moyen de paiement est nécessaire."),

			'Operation::description.check' => s("Veuillez saisir un libellé"),

			'Operation::cashflowRequiredForAttach' => s("Veuillez choisir une opération bancaire"),
			'Operation::operationsRequiredForAttach' => s("Veuillez choisir une écriture comptable"),
			'Operation::thirdPartyRequiredForAttach' => s("Veuillez choisir un tiers"),

			'thirdParty.empty' => s("Choisissez un tiers pour ce paiement."),

			'Operation::date.check' => s("La date doit correspondre à l'exercice comptable actuellement ouvert."),
			'Operation::account.check' => s("N'oubliez pas de choisir un numéro de compte !"),
			'Operation::account.notExists' => s("Le compte n'existe pas"),
			'Operation::accountLabel.inconsistency' => s("Le numéro de compte doit commencer par les mêmes chiffres que le compte."),
			'Operation::accountLabel.format' => s("Le numéro de compte doit être composé exactement de 8 chiffres."),

			'Operation::invoice.incorrectType' => s("Le fichier n'est pas reconnu comme une facture. Vous pouvez effectuer la saisie manuellement ou réessayer."),
			'Operation::invoice.unknownExtension' => s("Le format du fichier n'est pas reconnu, veuillez essayer avec un autre fichier ou faire une saisie manuelle."),

			'Operation::document.empty' => s("Le nom de la pièce comptable est nécessaire pour enregistrer le document dans votre espace de stockage."),
			'Operation::attach.check' => s("Il n'y a pas d'opération à rattacher ?"),

			'Operation::FinancialYear.notUpdatable' => s("Il n'est plus possible d'écrire dans cet exercice comptable."),

			'Operation::selectedJournalCodeInconsistency' => s("Un problème technique est survenu avec le journal choisi. Veuillez rafraîchir la page et recommencer."),

			'Operation::typeProduitCharge.inconsistent' => s("Il n'est pas possible d'avoir en même temps une écriture de charge et une écriture de produit. Réalisez deux écritures séparément."),
			'Operation::thirdPartys.inconsistent' => s("Il n'est pas possible de créer des écritures pour plusieurs tiers différents, réalisez des écritures séparément par tiers."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Operation::created' => s("L'écriture a bien été enregistrée."),
			'Operation::createdCreateAsset' => s("L'écriture a bien été enregistrée. Souhaitez-vous à présent créer la fiche d'immobilisation ?"),
			'Operation::createdSeveral' => s("Les écritures ont bien été enregistrées."),
			'Operation::createdSeveralCreateAsset' => s("Les écritures ont bien été enregistrées. Souhaitez-vous à présent créer la fiche d'immobilisation ?"),
			'Operation::update' => s("L'écriture a bien été modifiée."),
			'Operation::updated' => s("L'écriture a bien été modifiée."),
			'Operation::updatedSeveral' => s("Les écritures ont bien été modifiées."),
			'Operation::deleted' => s("L'écriture a bien été supprimée."),
			'Operations::attached' => s("Les écritures ont bien été rattachées."),
			'Operation::attached' => s("L'écriture a bien été rattachée."),

			'Operations::updated' => s("Les opérations ont été modifiées."),

			'VatDeclaration:created' => s("La déclaration de TVA a bien été créée."),

			'Deferral::charge.created' => s("La charge constatée d'avance a bien été enregistrée."),
			'Deferral::product.created' => s("Le produit constaté d'avance a bien été enregistré."),
			'Deferral::charge.deleted' => s("La charge constatée d'avance a bien été supprimée."),
			'Deferral::product.deleted' => s("Le produit constaté d'avance a bien été supprimé."),

			'Stock::created' => s("Le stock a bien été enregistré."),
			'Stock::deleted' => s("Le stock a bien été supprimé."),
			'Stock::set' => s("Le stock a bien été enregistré pour cet exercice fiscal."),
			'Stock::reset' => s("Le stock a bien été enregistré à 0 à la fin de cet exercice fiscal."),
			'Stock::renew' => s("Le stock a bien été enregistré identique à l'exercice précédent pour cet exercice fiscal."),

			'JournalCode::created' => s("Le journal a bien été créé."),
			'JournalCode::deleted' => s("Le journal a bien été supprimé."),
			'JournalCode::accountsUpdated' => s("Les numéros de compte de ce journal ont bien été modifiés."),

			default => null

		};


	}

}
?>
