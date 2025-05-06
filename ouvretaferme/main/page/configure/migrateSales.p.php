<?php
new Page()
	->cli('index', function($data) {

		$cSale = \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->where('paymentMethod IS NOT NULL')
			->getCollection();

		$cMethod = \payment\Method::model()
			->select(\payment\Method::getSelection())
			->where('fqn IS NOT NULL')
			->getCollection(NULL, NULL, 'fqn');

		\selling\Payment::model()->beginTransaction();

		$ePayment = new \selling\Payment(['method' => $cMethod[\payment\MethodLib::ONLINE_CARD]]);

		\selling\Payment::model()
			->select(['method'])
			->where('checkoutId IS NOT NULL')
			->update($ePayment);

		foreach($cSale as $eSale) {

			if($eSale['paymentMethod'] === 'online-card') {
				continue;
			}

			$method = match($eSale['paymentMethod']) {
				'card' => \payment\MethodLib::CARD,
				'check' => \payment\MethodLib::CHECK,
				'transfer' => \payment\MethodLib::TRANSFER,
				'cash' => \payment\MethodLib::CASH,
				default => NULL,
			};

			$eMethod = $cMethod[$method] ?? NULL;

			$ePayment = new \selling\Payment([
				'sale' => $eSale,
				'customer' => $eSale['customer'],
				'farm' => $eSale['farm'],
				'amountIncludingVat' => $eSale['priceIncludingVat'],
				'method' => $eMethod,
				'status' => \selling\Payment::SUCCESS,
			]);

			\selling\Payment::model()->insert($ePayment);

		}

		\selling\Payment::model()->commit();

	});
?>
