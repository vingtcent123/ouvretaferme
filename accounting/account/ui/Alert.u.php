<?php
namespace account;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Account::class.duplicate' => s("Cet numéro de compte existe déjà."),
			'Account::class.unknown' => s("Le premier numéro de ce compte n'est pas dans le plan comptable. Le compte doit commencer par un chiffre de 1 à 7."),
			'Account::class.size' => s("Le numéro de compte doit contenir entre 4 et 8 chiffres."),
			'Account::class.numeric' => s("Le numéro de compte doit être composé de chiffres uniquement."),

			'FinancialYear::startDate.check' => s("Cette date est incluse dans un autre exercice."),
			'FinancialYear::endDate.check' => s("Cette date est incluse dans un autre exercice."),
			'FinancialYear::startDate.loseOperations' => s("En modifiant cette date, certaines écritures ne seront plus rattachées à un exercice existant."),
			'FinancialYear::endDate.loseOperations' => s("En modifiant cette date, certaines écritures ne seront plus rattachées à un exercice existant."),

			'ThirdParty::name.duplicate' => s("Ce tiers existe déjà, utilisez-le directement ?"),

			'Import::filename.incorrect' => s("Le nom de votre fichier FEC est incorrect. Il doit être de la forme : <i><b>siren</b>FEC<b>date</b>.txt</i> avec <b>siren</b> le numéro de siren de votre ferme et <b>date</b> la date de clôture ou de l'export."),
			'Import::header.incorrect' => s("Le format de votre fichier FEC est incorrect. Les entêtes de colonnes n'ont pas été détectées. <link>Lire plus d'informations sur la norme</link>.", ['link' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000027804775" target="_blank">']),
			'Import::header.missingCols' => s("Votre fichier FEC ne contient pas suffisamment de colonnes. Il devrait y en avoir 18 ou 21 en fonction de votre configuration. <link>Lire plus d'informations sur la norme</link>.", ['link' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000027804775" target="_blank">']),
			'Import::header.incorrectCol' => s("L'entête de votre fichier FEC ne répond pas à la norme. <link>Lire plus d'informations sur la norme</link>.", ['link' => '<a href="https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000027804775" target="_blank">']),
			'Import::updated.feedbackNeeded' => s("L'import ne peut pas être relancé : veuillez terminer la configuration."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Account::created' => s("Le numéro de compte personnalisé a bien été créé."),
			'Account::deleted' => s("Le numéro de compte personnalisé a bien été supprimé."),

			'FinancialYear::created' => s("L'exercice comptable a bien été créé."),
			'FinancialYear::updated' => s("L'exercice comptable a bien été mis à jour."),
			'FinancialYear::closed' => s("L'exercice comptable a bien été clôturé."),
			'FinancialYear::open' => s("Le bilan d'ouverture a bien été effectué."),
			'FinancialYear::reopen' => s("L'exercice comptable a bien été rouvert ! Faites bien attention..."),
			'FinancialYear::reclose' => s("L'exercice comptable a bien été refermé."),

			'ThirdParty::created' => s("Le tiers a bien été créé."),
			'ThirdParty::updated' => s("Le tiers a bien été mis à jour."),
			'ThirdParty::deleted' => s("Le tiers a bien été supprimé."),

			'Import::created' => s("L'import sera réalisé d'ici quelques instants ! L'état de l'import sera affiché dans le tableau des imports."),
			'Import::cancelled' => s("Cet import a bien été annulé !"),
			'Import::updated' => s("L'import va bientôt se relancer !"),

			default => null

		};


	}

}
?>
