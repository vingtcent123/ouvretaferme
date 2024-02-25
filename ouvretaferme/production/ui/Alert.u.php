<?php
namespace production;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Sequence::perennialLifetime.check' => s("La durée de vie d'une culture ne peut pas excéder 100 ans !"),
			'Sequence::plantsList.check' => s("Veuillez sélectionner au moins une espèce !"),

			'Crop::plant.check' => s("Vous n'avez pas choisi d'espèce !"),
			'Crop::plant.duplicate' => s("Cette espèce est déjà utilisée dans l'itinéraire technique !"),
			'Crop::deleteOnly' => s("Vous ne pouvez pas supprimer la seule production de l'itinéraire technique !"),

			'Crop::variety.check' => s("Une erreur est survenue dans le traitement des variétés."),
			'Crop::variety.createEmpty' => s("Vous devez indiquer un nom pour une variété que vous souhaitez ajouter."),
			'Crop::variety.notExists' => s("La variété n'existe pas."),
			'Crop::variety.partZero' => s("Vous n'avez pas indiqué de répartition pour au moins une des variétés."),
			'Crop::variety.partsPercent' => s("La répartition des variétés doit couvrir 100 % de l'espace disponible !"),
			'Crop::variety.partsArea' => fn($area) => s("La répartition des variétés doit couvrir l'intégralité de la surface de culture de {area} m² !", ['area' => $area]),
			'Crop::variety.partsLength' => fn($length) => s("La répartition des variétés doit couvrir l'intégralité de la longueur de planches de {area} mL !", ['area' => $length]),
			'Crop::variety.duplicate' => s("Vous avez indiqué des variétés en doublon pour une culture."),

			'Flow::seasonOnly.empty' => s("Merci de donner une saison !"),
			'Flow::seasonStart.empty' => s("Merci de donner une saison de début !"),
			'Flow::seasonStop.consistency' => s("L'action ne peut pas se finir avant ou en même temps qu'elle ait débuté..."),
			'Flow::weekOnly.empty' => s("Merci d'indiquer une semaine de début !"),
			'Flow::weekStart.empty' => s("Merci d'indiquer une semaine de fin !"),
			'Flow::weekStop.empty' => s("Merci d'indiquer une semaine !"),
			'Flow::yearOnly.empty' => s("Merci d'indiquer une année de début !"),
			'Flow::yearStart.empty' => s("Merci d'indiquer une année de fin !"),
			'Flow::yearStop.empty' => s("Merci d'indiquer une année !"),
			'Flow::weekStop.consistency' => s("L'action ne peut pas se finir avant ou en même temps qu'elle ait débuté..."),
			'Flow::weekOnly.consistency0' => s("Une action en année N - 1 doit avoir lieu après la semaine 40..."),
			'Flow::weekStart.consistency0' => s("Une action en année N - 1 doit avoir lieu après la semaine 40..."),
			'Flow::weekStop.consistency0' => s("Une action en année N - 1 doit avoir lieu après la semaine 40..."),
			'Flow::weekOnly.consistency2' => s("Une action en année N + 1 doit avoir lieu avant la semaine 12..."),
			'Flow::weekStart.consistency2' => s("Une action en année N + 1 doit avoir lieu avant la semaine 12..."),
			'Flow::weekStop.consistency2' => s("Une action en année N + 1 doit avoir lieu avant la semaine 12..."),
			'Flow::weekTooSoonPerennial' => s("Vous ne pouvez pas avancer davantage cette intervention !"),
			'Flow::weekTooLatePerennial' => s("Vous ne pouvez pas décaler davantage cette intervention !"),
			'Flow::weekTooSoonAnnual' => s("Une intervention ne peut pas démarrer plus tôt que la semaine {value} de l'année N - 1 !", \Setting::get('minWeekN-1')),
			'Flow::weekTooSoonAnnualNeutral' => fn($season) => s("Une série pour la saison {season} ne peut pas démarrer plus tôt que la semaine {week} de l'année précédente !", ['season' => $season, 'week' => \Setting::get('minWeekN-1')]),
			'Flow::weekTooLateAnnual' => s("Une intervention ne peut pas finir plus tard que la semaine {value} de l'année N + 1 !", \Setting::get('maxWeekN+1')),

			'Flow::flows.check' => s("Merci de sélectionner au moins une intervention"),

			default => NULL


		};


	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Sequence::created' => s("L'itinéraire technique a bien été créé."),
			'Sequence::updated' => s("L'itinéraire technique a bien été mis à jour."),
			'Sequence::duplicated' => s("L'itinéraire technique a bien été dupliqué ici !"),
			'Sequence::deleted' => s("L'itinéraire technique a bien été supprimé."),
			'Crop::updated' => s("La plante a bien été mise à jour."),
			default => NULL

		};

	}

}
?>
