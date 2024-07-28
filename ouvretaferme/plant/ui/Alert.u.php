<?php
namespace plant;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Family::deleteUsed'=> s("Vous ne pouvez pas supprimer cette famille car des espèces y sont encore rattachées."),

			'Forecast::plant.duplicate' => s("Vous avez déjà ajouté une ligne dans le prévisionnel pour cette espèce et cette unité de vente."),
			'Forecast::proPart.consistency' => s("Le total de la répartition des ventes doit être de 100 %."),
			'Forecast::deleteUsed' => s("Vous ne pouvez pas supprimer du prévisionnel une espèce pour laquel des séries ont déjà été créées."),

			'Variety::name.duplicate' => s("Vous avez déjà créé une variété de ce nom pour cette espèce."),
			'Variety::deleteUsed'=> s("Vous ne pouvez pas supprimer cette variété car elle est utilisée dans une série ou un itinéraire technique."),

			'Size::name.duplicate' => s("Vous avez déjà créé un calibre de ce nom pour cette espèce."),
			'Size::deleteUsed'=> s("Vous ne pouvez pas supprimer ce calibre car il est utilisé dans une série."),

			'Plant::deleteUsed'=> s("Vous ne pouvez pas supprimer cette espèce car elle est encore utilisée dans une série, un itinéraire technique ou pour une variété."),
			'Plant::name.duplicate' => s("Vous avez déjà créé une espèce de ce nom."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Plant::created' => s("L'espèce a bien été créée."),
			'Plant::updated' => s("L'espèce a bien été mise à jour."),
			'Plant::updatedActive' => s("L'espèce a bien été réactivée."),
			'Plant::updatedInactive' => s("L'espèce a bien été désactivée."),
			'Plant::updated' => s("L'espèce a bien été mise à jour."),
			'Plant::deleted' => s("L'espèce a bien été supprimée."),

			'Forecast::created' => s("L'espèce a bien été ajoutée au prévisionnel."),
			'Forecast::updated' => s("Le prévisionnel a bien été mis à jour."),
			'Forecast::deleted' => s("L'espèce a bien été supprimée du prévisionnel."),
			
			'Family::updated' => s("La famille a bien été mise à jour."),
			'Family::deleted' => s("La famille a bien été supprimée."),
			
			'Variety::created' => s("La variété a bien été ajoutée !"),
			'Variety::updated' => s("La variété a bien été mise à jour."),
			'Variety::deleted' => s("La variété a bien été supprimée."),
			
			'Size::created' => s("Le calibre a bien été ajouté !"),
			'Size::updated' => s("Le calibre a bien été mis à jour."),
			'Size::deleted' => s("Le calibre a bien été supprimé."),
			
			default => null

		};

	}

}
?>
