<?php
namespace receipts;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Line::date.financialYear' => s("Aucun exercice comptable n'a été trouvé pour cette date. Veuillez vérifier votre saisie ou créer maintenant l'exercice comptable qui correspond à cette date.").'<br/><a href="'.\farm\FarmUi::urlConnected().'/account/financialYear/" target="_blank" class="btn btn-danger btn-xs" style="margin-top: 0.5rem">'.s("Configurer mes exercices comptables").'</a>',
			'Line::date.check' => s("Indiquez la date de l'opération"),
			'Line::date.past' => s("La date de l'opération ne peut pas être antérieure à la dernière opération de caisse validée"),
			'Line::date.future' => s("La date de l'opération ne peut pas être dans le futur"),
			'Line::date.yea' => s("Votre livre des recettes ne peut pas démarrer avant le {value}", \util\DateUi::numeric(date('Y-01-01'))),
			'Line::account.empty' => s("Vous n'avez pas indiqué de numéro de compte"),
			'Line::description.empty' => s("Vous n'avez pas indiqué le motif de l'opération"),
			'Line::amountIncludingVat.check' => s("Vous devez saisir un montant"),
			'Line::amountExcludingVat.empty' => s("Vous devez saisir un montant"),
			'Line::vat.empty' => s("Vous devez saisir un montant de TVA"),
			'Line::vatRate.empty' => s("Vous devez saisir un taux de TVA"),
			'Line::amountConsistency' => s("Le total HT + TVA doit être égal au total TTC"),
			'Line::balance.negative' => s("Le nouveau solde ne peut pas être négatif"),

			default => null

		};

	}

	public static function getSuccess(string $fqn, array $options = []): ?string {

		return match($fqn) {

			'Line::created' => s("L'opération a bien été ajoutée"),
			'Line::updated' => s("L'opération a bien été mise à jour"),
			'Line::updatedBalance' => s("Le solde du livre des recettes a bien été mis à jour"),
			'Line::validated' => s("Les opérations ont bien été validées"),
			'Line::deleted' => s("L'opération a bien été supprimée"),
			'Line::imported' => s("L'opération a bien été importée dans les opérations à valider"),

			'Book::created' => s("Le livre des recettes a bien été créé"),
			'Book::updatedClosed' => s("La clôture du livre des recettes a bien été réalisée"),
			'Book::deleted' => s("Le livre des recettes a bien été supprimé"),

			default => null

		};


	}

}
?>
