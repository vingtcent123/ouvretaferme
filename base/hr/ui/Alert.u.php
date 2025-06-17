<?php
namespace hr;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Absence::to.consistency' => s("La date de fin ne peut pas être postérieure à la date de début"),
			'Presence::to.consistency' => s("La date de fin ne peut pas être postérieure à la date de début"),
			'Presence::to.present' => s("L'utilisateur est déjà présent au moins partiellement à la ferme à cette période"),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Absence::created' => s("L'absence a bien été ajoutée."),
			'Absence::updated' => s("L'absence a bien été mise à jour."),
			'Absence::deleted' => s("L'absence a bien été supprimée."),

			'Presence::created' => s("La présence a bien été ajoutée."),
			'Presence::updated' => s("La présence a bien été mise à jour."),
			'Presence::deleted' => s("La présence a bien été supprimée."),

			default => NULL

		};

	}

}
?>
