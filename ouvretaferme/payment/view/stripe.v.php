<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Configurer un compte Stripe pour le paiement en ligne");
	$t->tab = 'settings';
	$t->subNav = new \farm\FarmUi()->getSettingsSubNav($data->eFarm);

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettings($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Paiement en ligne");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \payment\StripeFarmUi()->getManage($data->eFarm, $data->eStripeFarm);

});

?>