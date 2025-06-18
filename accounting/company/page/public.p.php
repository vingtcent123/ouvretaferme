<?php
new \company\CompanyPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
	}
)
	->getCreateElement(fn($data) => new \company\Company([
		'owner' => \user\ConnectionLib::getOnline()
	]))
	->create(fn($data) =>throw new ViewAction($data))
	->doCreate(function($data) {
		throw new RedirectAction(\company\CompanyUi::urlSettings($data->e).'?success=company:Company::created&firstTime=1');
	});
