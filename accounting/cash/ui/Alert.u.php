<?php
namespace cash;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Cash::date.financialYear' => s("Aucun exercice comptable n'a été trouvé pour cette date. Veuillez vérifier votre saisie ou créer maintenant l'exercice comptable qui correspond à cette date.").'<br/><a href="'.\farm\FarmUi::urlConnected().'/account/financialYear/" target="_blank" class="btn btn-danger btn-xs" style="margin-top: 0.5rem">'.s("Configurer mes exercices comptables").'</a>',
			'Cash::date.check' => s("Indiquez la date de l'opération"),
			'Cash::date.past' => s("La date de l'opération ne peut pas être antérieure à la dernière opération de caisse validée"),
			'Cash::date.future' => s("La date de l'opération ne peut pas être dans le futur"),
			'Cash::account.empty' => s("Vous n'avez pas indiqué de numéro de compte"),
			'Cash::description.empty' => s("Vous n'avez pas indiqué le motif de l'opération"),
			'Cash::amountIncludingVat.check' => s("Vous devez saisir un montant positif ou nul"),

			default => null

		};

	}

	public static function getSuccess(string $fqn, array $options = []): ?string {

		return match($fqn) {

			'Cash::created' => s("L'opération a bien été ajoutée"),
			'Cash::updated' => s("L'opération a bien été mise à jour"),
			'Cash::deleted' => s("L'opération a bien été supprimée"),

			'Register::created' => s("Le journal de caisse a bien été configuré"),
			'Register::deleted' => s("Le journal de caisse a bien été supprimé"),

			default => null

		};


	}

}
?>
