<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Abonnements de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlSettingsAccounting($data->eFarm);

	$t->mainTitle = new \company\SubscriptionUi()->getManageTitle($data->eFarm);
	$t->mainTitleClass = 'hide-lateral-down';

	echo \company\SubscriptionUi::getCurrent($data->eFarm);
	echo new \company\SubscriptionUi()->getPlans($data->eFarm);
	echo \company\SubscriptionUi::getHistory($data->cSubscriptionHistory);

});
