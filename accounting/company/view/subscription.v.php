<?php
new AdaptativeView('manage', function($data, CompanyTemplate $t) {

	$t->title = s("Les abonnements de {value}", $data->eFarm['name']);
	$t->tab = 'settings';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eFarm);

	$t->mainTitle = new \company\SubscriptionUi()->getManageTitle($data->eFarm);

	echo \company\SubscriptionUi::getCurrent($data->eFarm);
	echo new \company\SubscriptionUi()->getPlans($data->eFarm);
	echo \company\SubscriptionUi::getHistory($data->cSubscriptionHistory);

});
