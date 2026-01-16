<?php
namespace asset;

Class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Asset::amount.check' => s("Le montant de cession est obligatoire"),
			'Asset::date.check' => s("La date de cession est obligatoire"),
			'Asset::status.check' => s("Le motif de cession est obligatoire"),
			'Asset::amortizableBase.checkValue' => s("La base amortissable ne peut être supérieure à la valeur de l'immobilisation"),
			'Asset::economicDuration.degressive' => s("La durée doit être au moins égale à 3 ans en cas d'amortissement dégressif"),
			'Asset::startDate.missing' => s("La date de mise en service est nécessaire pour les calculs des amortissements linéaires"),
			'Asset::startDate.inconsistency' => s("La date de mise en service doit être postérieure à la date d'acquisition."),
			'Asset::resumeDate.inconsistent' => s("La date de reprise doit être postérieure à la date d'acquisition"),
			'Asset::economicMode.incompatible' => s("Le numéro de compte sélectionné ne permet pas d'amortissement"),
			'Asset::fiscalMode.incompatible' => s("Le numéro de compte sélectionné ne permet pas d'amortissement"),
			'Asset::economicAmortization.inconsistent' => s("Le montant déjà amorti ne peut pas excéder le montant à amortir (Valeur acquisition - Valeur résiduelle)"),
			'Asset::Operation.alreadyLinked' => s("L'immobilisation doit être rattaché uniquement à des écritures comptables qui n'ont pas encore de fiche d'immobilisation. N'avez-vous pas fait une erreur ?"),
			'Asset::accountLabel.check' => s("Indiquez le même numéro de compte que l'écriture à laquelle cette immobilisation est rattachée."),
			'Asset::accountLabels.check' => s("Indiquez le même numéro de compte que les écritures auxquelles cette immobilisation est rattachée."),
			'Asset::account.check' => s("Indiquez le même compte que l'opération à laquelle cette immobilisation est rattachée."),
			'Asset::accounts.check' => s("Indiquez le même compte que les opérations auxquelles cette immobilisation est rattachée."),
			'Asset::fiscalDuration.range' => s("La durée d'amortissement fiscale doit respecter la fourchette de durées recommandées."),

			default => null,
		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Asset::asset.deleted' => s("L'immobilisation a bien été supprimée."),
			'Asset::asset.created' => s("L'immobilisation a bien été enregistrée"),
			'Asset::grant.created' => s("La subvention a bien été enregistrée"),
			'Asset::asset.updated' => s("L'immobilisation a bien été modifiée"),
			'Asset::grant.updated' => s("La subvention a bien été modifiée"),
			'Asset::disposed' => s("L'immobilisation a bien été cédée"),
			'Asset::attached' => s("L'immobilisation a bien été liée"),
			'Asset::unattached' => s("L'immobilisation et l'écriture ont bien été dissociées"),

			default => null,

		};


	}

}

?>
