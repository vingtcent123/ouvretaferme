<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Configurer un compte Stripe pour le paiement en ligne");
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm, s("Paiement en ligne"));

	echo (new \payment\StripeFarmUi())->display($data->eFarm, $data->eStripeFarm);

});

?>