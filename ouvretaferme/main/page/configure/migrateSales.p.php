<?php
new Page()
	->cli('index', function($data) {

		$cSale = \selling\Sale::model()
			->select(\selling\Sale::getSelection() + ['paymentMethod'])
			->getCollection();

		$cMethod = \payment\Method::model()
			->select(\payment\Method::getSelection())
			->where('fqn IS NOT NULL')
			->getCollection(NULL, NULL, 'fqn');

		\selling\Payment::model()->beginTransaction();

		foreach($cSale as $eSale) {

			$method = match($eSale['paymentMethod']) {
				'card' => \payment\MethodLib::CARD,
				'check' => \payment\MethodLib::CHECK,
				'transfer' => \payment\MethodLib::TRANSFER,
				'cash' => \payment\MethodLib::CASH,
				'online-card' => \payment\MethodLib::ONLINE_CARD,
				default => NULL,
			};

			$eMethod = $cMethod[$method] ?? new \payment\Method();

			$ePayment = new \selling\Payment([
				'sale' => $eSale,
				'customer' => $eSale['customer'],
				'farm' => $eSale['farm'],
				'amountIncludingVat' => $eSale['priceIncludingVat'],
				'method' => $eMethod,
				'status' => \selling\Payment::SUCCESS,
			]);

			if($eSale['paymentMethod'] === 'online-card') {

				\selling\Payment::model()
					->select(['amountIncludingVat', 'method'])
					->whereSale($eSale)
					->whereCustomer($eSale['customer'])
					->update($ePayment);

			} else {

				\selling\Payment::model()->insert($ePayment);

			}

		}

		\selling\Payment::model()->commit();

	});
?>
