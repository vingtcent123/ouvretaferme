<?php
new Page(function($data) {

	$data->eCompany = \company\CompanyLib::getById(REQUEST('company'))->validate('canManage');

	\user\ConnectionLib::checkLogged();

})
	->get('manage', function($data) {

		$data->cSubscriptionHistory = \company\SubscriptionLib::getHistory($data->eCompany);

		throw new ViewAction($data);

	})
	->post('subscribe', function($data) {

		$type = POST('type');

		$fw = new FailWatch();

		$message = \company\SubscriptionLib::subscribe($data->eCompany, $type);

		if($fw->ok()) {
			throw new RedirectAction(\company\CompanyUi::url($data->eCompany).'/subscription:manage?success=company:Subscription::'.$message);
		} else {
			throw new RedirectAction(\company\CompanyUi::url($data->eCompany).'/subscription:manage?error='.$fw->getLast());
		}

	})
	->post('subscribePack', function($data) {

		$fw = new FailWatch();

		\company\SubscriptionLib::subscribePack($data->eCompany);

		if($fw->ok()) {
			throw new RedirectAction(\company\CompanyUi::url($data->eCompany).'/subscription:manage?success=company:Subscription::pack');
		} else {
			throw new RedirectAction(\company\CompanyUi::url($data->eCompany).'/subscription:manage?error='.$fw->getLast());
		}

	});

?>
