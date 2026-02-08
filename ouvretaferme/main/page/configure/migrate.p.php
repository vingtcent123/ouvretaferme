<?php
new Page()
	->cli('index', function($data) {

		$c = \selling\Sale::model()
			->select('id', 'paymentStatus')
			->whereProfile('NOT IN', [\selling\Sale::COMPOSITION, \selling\Sale::MARKET])
			->sort([
				'id' => SORT_ASC
			])
			->getCollection();

		foreach($c as $e) {

			if(
				$e['paymentStatus'] === NULL and
				\selling\Payment::model()
					->whereSale($e)
					->exists() === FALSE
			) {
				continue;
			}


			if($e['paymentStatus'] === \selling\Sale::NOT_PAID) {

				if(
					\selling\Payment::model()
						->whereSale($e)
						->whereStatus(\selling\Payment::NOT_PAID)
						->exists() and
					\selling\Payment::model()
						->whereSale($e)
						->whereStatus('!=', \selling\Payment::NOT_PAID)
						->exists() === FALSE
				) {
					continue;
				} else {
					echo '!';
					continue;
				}

			}

			if(
				$e['paymentStatus'] === \selling\Sale::PAID and
				\selling\Payment::model()
					->whereStatus(\selling\Payment::PAID)
					->whereSale($e)
					->exists()
			) {

				\selling\Sale::model()
					->select(\selling\Sale::getSelection())
					->get($e);

				\selling\PaymentTransactionLib::recalculate($e);
			} else {
				echo $e['id'];
			}

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