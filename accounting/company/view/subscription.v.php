<?php
new AdaptativeView('manage', function($data, CompanyTemplate $t) {

	$t->title = s("Les abonnements de {value}", $data->eCompany['name']);
	$t->tab = 'settings';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eCompany);

	$t->mainTitle = new \company\SubscriptionUi()->getManageTitle($data->eCompany);

	echo \company\SubscriptionUi::getCurrent($data->eCompany);
	echo new \company\SubscriptionUi()->getPlans($data->eCompany);
	echo \company\SubscriptionUi::getHistory($data->cSubscriptionHistory);

});
