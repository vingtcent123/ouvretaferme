<?php
new AdaptativeView('/commandes/particuliers', function($data, MainTemplate $t) {

	$t->title = s("Mes commandes");

	echo (new \selling\OrderUi())->getListForPrivate($data->cSale);

});

new AdaptativeView('/commandes/professionnels/{farm}', function($data, MainTemplate $t) {

	$t->title = s("Mes commandes");
	$t->header = (new \selling\OrderUi())->getFarmHeader($data->eCustomer['farm'], $data->eCustomer);

	echo (new \selling\OrderUi())->getListForPro($data->eCustomer, $data->cSale);

});

new AdaptativeView('/commande/{id}', function($data, MainTemplate $t) {

	$t->title = s("Commande #{value}", $data->e->getNumber());

	$back = match($data->e['customer']['type']) {
		\selling\Customer::PRO => '<a href="/commandes/professionnels/'.$data->eFarm['id'].'" class="btn btn-outline-secondary">'.\Asset::icon('chevron-left').' '.s("Toutes les commandes").'</a>',
		\selling\Customer::PRIVATE => '<a href="/commandes/particuliers" class="btn btn-outline-secondary">'.\Asset::icon('chevron-left').' '.s("Toutes les commandes").'</a>'
	};

	$t->header = (new \selling\OrderUi())->getFarmHeader($data->eFarm, $data->e['customer'], $back);

	echo (new \selling\OrderUi())->displaySale($data->e);

	if($data->cItem->empty()) {
		echo '<div class="util-info">'.s("Il n'y a aucun article dans cette commande.").'</div>';
	} else {
		echo (new \selling\OrderUi())->getItemsBySale($data->e, $data->cItem);
	}

});

?>
