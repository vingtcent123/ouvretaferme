<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Invoice::model()
			->select(\selling\Invoice::getSelection() + [
				'm' => new Sql('paymentMethod', '?int'),
			])
			->wherePaymentStatus('IN', [\selling\Invoice::PAID, \selling\Invoice::NOT_PAID])
			->getCollection();

		foreach($c as $e) {

			$m = \payment\MethodLib::getById($e['m']);

			if($e['paymentStatus'] === \selling\Invoice::NOT_PAID) {

				$p = new \selling\Payment([
					'status' => \selling\Invoice::NOT_PAID,
					'method' => $m,
					'paidAt' => NULL,
					'amountIncludingVat' => NULL
				]);

			} else {

				$p = new \selling\Payment([
					'status' => \selling\Invoice::PAID,
					'method' => $m,
					'paidAt' => $e['paidAt'],
					'amountIncludingVat' => $e['priceIncludingVat']
				]);

			}

			\selling\PaymentTransactionLib::replace($e, new Collection([$p]));

			echo '.';

		}

	});
	/*
	->cli('index', function($data) {

		$c = \selling\Customer::model()
			->select('id', 'farm')
			->whereNumber(NULL)
			->getCollection();

		foreach($c as $e) {

			$e['document'] = \farm\ConfigurationLib::getNextDocumentCustomers($e['farm']);
			$e['number'] = $e->calculateNumber();

			\selling\Customer::model()
				->select('document', 'number')
				->update($e);

		}

	});*/
?>