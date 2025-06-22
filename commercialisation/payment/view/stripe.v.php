<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Configurer un compte Stripe pour le paiement en ligne");
	$t->nav = 'settings-commercialisation';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Paiement en ligne");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \payment\StripeFarmUi()->getManage($data->eFarm, $data->eStripeFarm);

});

?>