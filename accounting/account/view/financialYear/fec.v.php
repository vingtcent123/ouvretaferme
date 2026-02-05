<?php
new AdaptativeView('import', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("Importer un fichier FEC pour {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlAccount($data->eFarm, $data->eFarm['eFinancialYear']).'/financialYear/fec:import';

	$t->mainTitle = '<h1>'.'<a onclick="history.back();" class="h-back">'.\Asset::icon('arrow-left').'</a>'.s("Importer un fichier FEC").'</h1>';

	$canImport = $data->eFarm['cFinancialYear']->find(fn($e) => $e['nOperation'] === 0)->notEmpty();

	if($data->eImport->notEmpty()) {

		echo '<div class="flex-justify-space-between">';

			echo '<h3>'.s("Import en cours").'</h3>';

			if($data->eImport->acceptCancel()) {

				echo '<a class="btn btn-outline-danger ml-2" data-ajax="'.\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/fec:doCancel" post-status="'.\account\Import::CANCELLED.'" post-id="'.$data->eImport['id'].'" data-confirm="'.s("Confirmez-vous annuler cet import ?").'">'.Asset::icon('trash').' '.s("Annuler cet import").'</a>';

			}

		echo '</div>';

		echo '<div class="util-info">';
			echo match($data->eImport['status']) {
				\account\Import::CREATED => s("Un import est en cours. Vous pouvez suivre son évolution et, si nécessaire, effectuer les ajustements nécessaires pour le terminer."),
				\account\Import::FEEDBACK_REQUESTED => s("Un import est en cours. Une action de votre part est nécessaire pour continuer."),
				\account\Import::FEEDBACK_TO_TREAT => s("Un import est en cours. Le traitement suite à votre action va bientôt être effectué."),
				\account\Import::IN_PROGRESS => s("L'import est en cours de traitement."),
				\account\Import::WAITING => s("L'import est en attente de traitement."),
			};
		echo '</div>';

		echo new \account\ImportUi()->showImport($data->eFarm, $data->eImport, $data->cJournalCode, $data->cAccount, $data->cMethod);

		$import = '';

	} else if($canImport === FALSE) {

		$import = '<div class="util-info">';

			if($data->eFarm['cFinancialYear']->notEmpty()) {

				$import .= p(
					"L'import n'est possible que dans un exercice sans écriture comptable. Votre exercice contient déjà au moins une écriture comptable, il n'est pas éligible à l'import.",
					"L'import n'est possible que dans un exercice sans écriture comptable. Vos exercices contiennent déjà au moins une écriture comptable, ils ne sont pas éligibles pour un import.", $data->eFarm['cFinancialYear']->count());

			} else {

				$import .= s("<link>Créez votre premier exercice comptable</link> pour y importer votre fichier FEC !", ['link' => '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/:create">']);

			}

		$import .= '</div>';

	} else {

		$import = '<h3>'.s("Importer un fichier FEC").'</h3>';
		$import .= new \account\ImportUi()->create($data->eFarm);

	}

	echo $import;

	if($data->cImport->notEmpty()) {

		echo '<h3>'.s("Historique des imports").'</h3>';

		echo new \account\ImportUi()->history($data->cImport);

	}


});

new AdaptativeView('view', function($data, PanelTemplate $t) {

	return new \account\FecUi()->getView($data->eFarm, $data->fecInfo);

});

?>
