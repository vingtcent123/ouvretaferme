<?php
new AdaptativeView('/livre-des-recettes', function($data, FarmTemplate $t) {

	$t->title = s("Livre des recettes de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/livre-des-recettes';

	$t->nav = 'receipts';

	$t->mainTitle = new \receipts\BookUi()->getHeader($data->eBook);

	if($data->eBook->empty()) {

		echo new \receipts\BookUi()->create();

	} else {


		if($data->eBook['operations'] > 0) {

			echo new \receipts\LineUi()->getChoice($data->eBook, $data->cCashflow, $data->cInvoice, $data->cSale);
			echo new \receipts\LineUi()->getSearch($data->eBook, $data->search);
			echo new \receipts\LineUi()->getList($data->eBook, $data->ccLine, $data->search, $data->page);

		} else {

			echo new \receipts\LineUi()->start($data->eBook);

		}

	}

});

