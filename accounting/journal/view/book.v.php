<?php
new AdaptativeView('/journal/grand-livre', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'book';

	$t->title = s("Le Grand livre de {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/grand-livre';

	$t->mainTitle = new \journal\BookUi()->getBookTitle($data->eFarm);

	echo new \journal\BookUi()->getSearch($data->search, $data->eFarm['eFinancialYear']);

	if($data->cOperation->empty() and $data->search->empty(['id', 'financialYear'])) {

		echo '<div class="util-empty">'.s("La Grand livre n'est pas disponible car il n'y a aucune écriture pour cet exercice comptable.").'</div>';
		echo '<a class="btn btn-primary" href="'.\company\CompanyUi::urlJournal($data->eFarm).'/livre-journal">'.s("Créer ma première écriture comptable dans le journal").'</a>';

	} else {

		echo new \journal\BookUi()->getBook($data->eFarm, $data->cOperation, $data->eFarm['eFinancialYear'], $data->search);

	}


});
