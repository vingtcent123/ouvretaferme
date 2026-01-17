<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \account\FinancialYearUi()->create($data->eFarm, $data->e);

});

new AdaptativeView('open', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Créer le bilan d'ouverture");
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/financialYear/:open';

	$t->mainTitle = new \account\FinancialYearUi()->getOpenTitle($data->eFarm);

	if($data->eFinancialYearPrevious->notEmpty() and $data->eFinancialYearPrevious->isClosed() === FALSE) {

		echo '<div class="util-block-info">';
			echo '<h4>';
				echo s("Attention au principe de séparation des exercices");
			echo '</h4>';
				echo '<p>'.s("Réalisez d'abord la clôture de l'exercice {previousYear} avant de réaliser l'ouverture de l'exercice {currentYear}.", ['previousYear' => $data->eFinancialYearPrevious->getLabel(), 'currentYear' => $data->e->getLabel()]).'</p>';
				echo '<a class="btn btn-transparent" href="'.\company\CompanyUi::urlFarm($data->eFarm, $data->eFinancialYearPrevious).'/etats-financiers/">'.s("Je retourne sur l'exercice {value}", $data->eFinancialYearPrevious->getLabel()).'</a>';
		echo '</div>';

	} else if($data->eFinancialYearPrevious->empty()) {

		echo '<div class="util-info">';
			echo '<p>'.s("Comme il n'y a pas d'exercice précédent, le bilan d'ouverture ouvrira cet exercice sans report à nouveau. Si vous avez besoin que des reports à nouveau soient enregistrés, vous pouvez : ").'</p>';
			echo '<ul>';
				echo '<li>'.s("Créer le précédent exercice et importer le fichier FEC").'</li>';
				echo '<li>'.s("Ou enregistrer manuellement toutes les écritures de report à nouveau dans l'exercice.").'</li>';
			echo '</ul>';
		echo '</div>';

		$form = new \util\FormUi();

		echo $form->openAjax(\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/:doOpen');

			echo $form->hidden('id', $data->e['id']);
			echo $form->submit(s("Ouvrir l'exercice sans écriture de report à nouveau"));

		echo $form->close();

	} else {

		echo new \account\FinancialYearUi()->open(
			$data->eFarm,
			$data->e,
			$data->eFinancialYearPrevious,
			$data->cOperation,
			$data->cOperationResult,
			$data->cJournalCode,
			$data->ccOperationReversed,
		);

	}
});

new AdaptativeView('close', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Clôturer un exercice comptable");
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/financialYear/:close?id='.$data->e['id'];

	$t->mainTitle = new \account\FinancialYearUi()->getCloseTitle($data->eFarm);

	echo '<div class="util-warning">'.s("La fonctionnalité de clôture n'est pas encore éprouvée à 100%. N'utilisez pas les données de {siteName} pour vos déclarations à l'administration fiscale. Si vous détectez des différences / des problèmes, contactez-nous sur Discord. Merci !").'</div>';

	echo new \account\FinancialYearUi()->close(
		$data->eFarm,
		$data->e,
		$data->cDeferral,
		$data->cAssetGrant,
		$data->cAsset,
		$data->accountsToSettle,
	);

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \account\FinancialYearUi()->update($data->eFarm, $data->e);

});

?>
