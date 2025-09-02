<?php
new Page()
	->get('/donner', function($data) {

		if(!Setting::get('association\isDonnerPageActive')) {
			throw new RedirectAction(Setting::get('association\url').'/nous-soutenir');
		}

		$data->eHistory = new \association\History();

		if(get_exists('donation')) {

			$eFarmOtf = \farm\FarmLib::getById(Setting::get('association\farm'));
			$eCustomer = \selling\Customer::model()
				->select(\selling\Customer::getSelection())
				->whereInvoiceEmail(GET('email'))
				->whereId(GET('customer'))
				->whereFarm($eFarmOtf)
				->get();

			if($eCustomer->notEmpty()) {

				$data->eHistory = \association\History::model()
					->select(\association\History::getSelection())
					->whereCustomer($eCustomer)
					->whereStatus(\association\History::VALID)
					->sort(['updatedAt' => SORT_DESC])
					->get();

				$data->eHistory['customer'] = $eCustomer;

			} else {

				$data->eCustomer = new \selling\Customer();

			}
			throw new ViewAction($data, ':thankYou');

		}

		$data->eUser = \user\ConnectionLib::getOnline();

		throw new ViewAction($data);

	});

new Page()
	->post('doCreatePayment', function($data) {

		$fw = new FailWatch();

		$url = \association\MembershipLib::createPayment(new \farm\Farm(), \association\History::DONATION);

		$fw->validate();

		if($fw->ok()) {
			throw new RedirectAction($url);
		}

	});
