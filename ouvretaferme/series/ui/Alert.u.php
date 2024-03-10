<?php
namespace series;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Series::plantsCheck' => s("Veuillez sélectionner au moins une espèce !"),
			'Series::perennialLifetime.consistency' => s("La durée de vie de la culture ne peut pas être réduite autant car elle a été mise en place depuis plus longtemps"),
			'Series::copyActions.check' => s("Merci de sélectionner au moins une intervention !"),
			'Series::duplicatePlaceConsistency' => s("Il n'est pas possible de continuer cette culture cette saison car un ou plusieurs emplacements qu'elle utilise (parcelle, bloc ou planche) ne sont pas utilisables cette année. Veuillez vérifier votre plan d'assolement !"),
			'Series::series.check' => s("Merci de sélectionner au moins une série"),

			'Cultivation::plant.check' => s("Vous n'avez pas choisi d'espèce !"),
			'Cultivation::plant.unused' => s("Vous ne pouvez pas choisir cette espèce car elle est déjà utilisée par ailleurs dans cette série !"),
			'Cultivation::plant.duplicate' => s("Vous avez déjà ajouté cette espèce à la série !"),
			'Cultivation::canNotDelete' => s("Il doit rester au moins une production sur la série."),
			'Cultivation::cultivations.check' => s("Merci de sélectionner au moins une série"),
			'Cultivation::variety.duplicate' => s("Vous avez indiqué des variétés en doublon pour une culture."),

			'Place::bedsDuplicate' => s("Vous avez sélectionné des emplacements en doublon."),
			'Place::bedsCheck' => s("Veuillez sélectionner au moins un emplacement pour cette série !"),
			'Place::bedsSize' => s("Vous avez choisi une utilisation incorrecte sur certains emplacements !"),

			'Repeat::stop.future' => s("La fin de répétition de l'intervention doit être dans le futur"),
			'Repeat::stop.series' => s("Vous devez indiquer une fin de répétition qui corresponde à l'année de la saison de la série, l'année précédente ou l'année suivante"),

			'Task::cultivation.check' => s("Vous n'avez pas saisi de production pour cette intervention"),
			'Task::plannedWeek.check' => s("Merci d'indiquer une semaine cohérente"),
			'Task::harvest.check' => s("La récolte ne peut pas être négative"),
			'Task::harvestMore.negative' => s("La récolte totale ne peut pas être négative sur une intervention"),
			'Task::harvestDates.check' => s("Merci de saisir une date de récolte valable"),
			'Task::harvestDates.negative' => s("La récolte ne peut pas être négative sur une date donnée"),
			'Task::tasks.check' => s("Merci de sélectionner au moins une intervention"),
			'Task::tasks.unit' => s("Il n'est pas possible de faire une récolte groupée car toutes les interventions n'ont pas la même unité de récolte"),
			'Task::tasks.area' => s("Il n'est possible de choisir une répartition par surface car vous n'avez pas saisi l'espace occupé pour au moins une des cultures"),
			'Task::tasks.density' => s("Il n'est possible de choisir une répartition par nombre de plants car vous n'avez pas saisi la densité pour au moins une des cultures"),
			'Task::tasks.notSeries' => s("Il n'est possible de choisir cette répartition car certaines interventions ne sont pas liées à des séries"),
			'Task::distribution.harvestZero' => s("Il n'est possible de choisir cette répartition car il n'y a aucune récolte pour ce jour de travail"),

			'Timesheet::user.check' => s("Vous ne pouvez pas gérer le temps de travail de cet utilisateur !"),
			'Timesheet::time.negative' => s("Le temps de travail d'un utilisateur ne peut pas être négatif sur une journée"),
			'Timesheet::duplicate' => s("Cet utilisateur a déjà été affecté à cette tâche"),
			default => NULL

		};


	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Cultivation::deleted' => s("La plante a bien été supprimée de la série."),

			'Task::cultivationUpdated' => s("La tâche a bien été affectée à sa nouvelle série !"),
			'Task::deleted' => s("La tâche a bien été supprimée !"),

			'Series::created' => s("La série a bien été créée !"),
			'Series::duplicated' => s("La série a bien été dupliquée ici !"),
			'Series::deleted' => s("La série a bien été supprimée."),

			default => NULL

		};

	}

}
?>
