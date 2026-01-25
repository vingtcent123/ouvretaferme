<?php
new AdaptativeView('/commandes/particuliers', function($data, MainTemplate $t) {

	$t->title = s("Mes commandes");

	echo new \selling\OrderUi()->getSales($data->cSale, \selling\Customer::PRIVATE);

});

new AdaptativeView('/factures/particuliers', function($data, MainTemplate $t) {

	$t->title = s("Mes factures");

	echo new \selling\OrderUi()->getInvoices($data->cInvoice, \selling\Customer::PRIVATE);

});

new AdaptativeView('/commandes/professionnels/{farm}', function($data, MainTemplate $t) {

	$t->title = s("Mes commandes");
	$t->header = new \selling\OrderUi()->getFarmHeader($data->eCustomer['farm'], $data->eCustomer);

	echo new \selling\OrderUi()->getForPro($data->eCustomer, $data->cSale, $data->cInvoice);

});

new AdaptativeView('/commande/{id}', function($data, MainTemplate $t) {

	$t->title = s("Commande #{value}", $data->e['document']);

	$back = match($data->e['customer']['type']) {
		\selling\Customer::PRO => '<a href="/commandes/professionnels/'.$data->eFarm['id'].'" class="btn btn-outline-secondary">'.\Asset::icon('chevron-left').' '.s("Toutes les commandes").'</a>',
		\selling\Customer::PRIVATE => '<a href="/commandes/particuliers" class="btn btn-outline-secondary">'.\Asset::icon('chevron-left').' '.s("Toutes les commandes").'</a>'
	};

	$t->header = new \selling\OrderUi()->getFarmHeader($data->eFarm, $data->e['customer'], $back);

	echo new \selling\OrderUi()->displaySale($data->e);

	if($data->cItem->empty()) {
		echo '<div class="util-empty">'.s("Il n'y a aucun article dans cette commande.").'</div>';
	} else {

		echo '<div class="util-title">';
			echo '<h3>'.s("Articles commandés").'</h3>';
		echo '</div>';

		echo new \selling\OrderUi()->getItemsBySale($data->e, $data->cItem);
	}

});

?>
