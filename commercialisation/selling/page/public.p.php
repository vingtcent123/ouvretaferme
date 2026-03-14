<?php
new \selling\PaymentLinkPage()
	->read('/paiement', function($data) {

		if(
			$data->e->empty() or
			($data->e['source'] === \selling\PaymentLink::INVOICE and ($data->e['invoice']['id'] ?? NULL) !== GET('element', 'int')) or
			($data->e['source'] === \selling\PaymentLink::SALE and ($data->e['sale']['id'] ?? NULL) !== GET('element', 'int'))
		) {
			throw new NotExistsAction();
		}

		if($data->e['source']  === \selling\PaymentLink::INVOICE) {
			$eElement = $data->e['invoice'];
		} else if($data->e['source']  === \selling\PaymentLink::SALE) {
			$eElement = $data->e['sale'];
		} else {
			throw new NotExistsAction();
		}

		$data->eMethod = \payment\MethodLib::getByFqn($eElement['farm'], \payment\MethodLib::ONLINE_CARD);

		if(
			$eElement['cPayment']->empty() or
			$eElement['cPayment']->find(fn($e) => $e['method']->is($data->eMethod))->empty()
 		) {
			throw new NotExistsAction();
		}

		throw new ViewAction($data);

	});
