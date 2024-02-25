<?php
namespace map;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Plot::seasonLast.consistency' => s("Merci d'être cohérent entre la saison de début et la saison de fin."),
			'Plot::greenhouse' => s("Vous ne pouvez pas supprimer un bloc sur lequel se trouve un abri."),
			'Plot::deleteUsed' => s("Vous ne pouvez pas supprimer un bloc qui a déjà été cultivé. Vous devez soit modifier les dates d'exploitation du bloc, soit supprimer préalablement les séries qui s'y trouvent."),

			'Zone::seasonLast.consistency' => s("Merci d'être cohérent entre la saison de début et la saison de fin."),
			'Zone::greenhouse' => s("Vous ne pouvez pas supprimer une parcelle sur laquelle se trouve un abri."),
			'Zone::deleteUsed' => s("Vous ne pouvez pas supprimer une parcelle qui a déjà été cultivée. Vous devez soit modifier les dates d'exploitation de la parcelle, soit supprimer préalablement les séries qui s'y trouvent."),

			'Bed::names.size' => s("Le nom des planches doit faire doit entre 1 et {value} lettres.", BedUi::p('name')->range[1]),
			'Bed::names.check' => s("Vous devez donner un nom à chaque planche."),
			'Bed::names.different' => s("Vous devez donner un nom différent à chaque planche."),
			'Bed::names.duplicate' => fn($names) => p("Le nom {value} est déjà utilisé par une autre planche de votre ferme.", "Les noms {value} sont déjà utilisés par d'autres planches de votre ferme.", count($names), ['value' => implode(', ', $names)]),
			'Bed::names.duplicateAnonymous' => s("Un nom que vous avez choisi est déjà utilisé par une autre planche de votre ferme."),
			'Bed::ids.check' => s("Veuillez sélectionner au moins une planche."),
			'Bed::seasonLast.consistency' => s("Merci d'être cohérent entre la saison de début et la saison de fin."),
			'Bed::deleteUsed' => s("Vous ne pouvez pas supprimer une planche qui a déjà cultivé. Vous devez soit modifier les dates d'exploitation de la planche, soit supprimer préalablement les séries qui s'y trouvent."),
			'Bed::canNotDraw' => s("Vous ne pouvez pas dessiner ces planches car le bloc ou la parcelle ne sont pas cartographiés."),

			'Draw::coordinates.check' => s("Vous n'avez pas tracé de ligne de départ des planches."),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Bed::created' => s("La planche a bien été ajoutée."),
			'Bed::createdCollection' => s("Les planches ont bien été ajoutées."),

			'Draw::created' => s("La ligne de départ des planches a bien été enregistrée."),
			'Draw::deleted' => s("Les planches que vous avez sélectionnées ne sont désormais plus affichées sur la carte."),

			'Zone::created' => s("La parcelle a bien été ajoutée."),
			'Zone::updated' => s("La parcelle a bien été mise à jour."),
			'Zone::deleted' => s("La parcelle a bien été supprimée."),

			'Plot::created' => s("Le bloc a bien été ajouté."),
			'Plot::updated' => s("Le bloc a bien été mis à jour."),
			'Plot::deleted' => s("Le bloc a bien été supprimé."),

			'Greenhouse.created' => s("L'abri a bien été ajouté."),
			'Greenhouse.updated' => s("L'abri a bien été mis à jour."),
			'Greenhouse.deleted' => s("L'abri a bien été supprimé."),
			default => NULL

		};

	}

}
?>
