<?php
new AdaptativeView('/journal/grand-livre', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'book';

	$t->title = s("Le Grand livre de {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/grand-livre';

	$t->mainTitle = new \journal\BookUi()->getBookTitle($data->eFarm);

	echo new \journal\BookUi()->getSearch($data->search, $data->eFarm['eFinancialYear']);
	echo new \journal\BookUi()->getBook($data->eFarm, $data->cOperation, $data->eFarm['eFinancialYear'], $data->search);


});
