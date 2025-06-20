<?php
new Page(function($data) {

	$data->eFarm = \farm\FarmLib::getById(REQUEST('farm'))->validate('canManage');

	\user\ConnectionLib::checkLogged();

})
	->get('manage', function($data) {

		$data->cSubscriptionHistory = \company\SubscriptionLib::getHistory($data->eFarm);

		throw new ViewAction($data);

	})
	->post('subscribe', function($data) {

		$type = POST('type');

		$fw = new FailWatch();

		$message = \company\SubscriptionLib::subscribe($data->eFarm, $type);

		if($fw->ok()) {
			throw new RedirectAction(\company\CompanyUi::url($data->eFarm).'/subscription:manage?success=company:Subscription::'.$message);
		} else {
			throw new RedirectAction(\company\CompanyUi::url($data->eFarm).'/subscription:manage?error='.$fw->getLast());
		}

	})
	->post('subscribePack', function($data) {

		$fw = new FailWatch();

		\company\SubscriptionLib::subscribePack($data->eFarm);

		if($fw->ok()) {
			throw new RedirectAction(\company\CompanyUi::url($data->eFarm).'/subscription:manage?success=company:Subscription::pack');
		} else {
			throw new RedirectAction(\company\CompanyUi::url($data->eFarm).'/subscription:manage?error='.$fw->getLast());
		}

	});

?>
