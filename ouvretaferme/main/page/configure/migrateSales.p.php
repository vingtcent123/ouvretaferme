<?php
new Page()
	->cli('index', function($data) {

		$cSale = \selling\Sale::model()
			->select(['id', 'oldPaymentMethod' => new Sql('oldPaymentMethod'), 'paymentStatus', 'preparationStatus', 'marketParent', 'customer', 'farm', 'priceIncludingVat'])
			->getCollection();

		$cMethod = \payment\Method::model()
			->select(\payment\Method::getSelection())
			->where('fqn IS NOT NULL')
			->getCollection(NULL, NULL, 'fqn');

		\selling\Payment::model()->beginTransaction();

		foreach($cSale as $eSale) {

			$method = match($eSale['oldPaymentMethod']) {
				'card' => \payment\MethodLib::CARD,
				'check' => \payment\MethodLib::CHECK,
				'transfer' => \payment\MethodLib::TRANSFER,
				'cash' => \payment\MethodLib::CASH,
				'online-card' => \payment\MethodLib::ONLINE_CARD,
				default => NULL,
			};

			$eMethod = $cMethod[$method] ?? new \payment\Method();

			if(
				// Marché = OK
				$eSale->isMarketSale()
				or $eSale['paymentStatus'] === \selling\Sale::PAID
				or $eSale['preparationStatus'] === \selling\Sale::DELIVERED
			) {
				$status = \selling\Payment::SUCCESS;
			} else {
				$status = \selling\Payment::INITIALIZED;
			}
			$ePayment = new \selling\Payment([
				'sale' => $eSale,
				'customer' => $eSale['customer'],
				'farm' => $eSale['farm'],
				'amountIncludingVat' => $eSale['priceIncludingVat'],
				'method' => $eMethod,
				'status' => $status,
			]);

			if($eSale['oldPaymentMethod'] === 'online-card') {

				\selling\Payment::model()
					->select(['amountIncludingVat', 'method'])
					->whereSale($eSale)
					->whereCustomer($eSale['customer'])
					->update($ePayment);

					$eSale['onlinePaymentStatus'] = match($ePayment['status']) {
						\selling\Payment::SUCCESS => \selling\Sale::SUCCESS,
						\selling\Payment::FAILURE => \selling\Sale::FAILURE,
						default => \selling\Sale::PENDING,
					};

			} else {

				\selling\Payment::model()->insert($ePayment);
				$eSale['onlinePaymentStatus'] = NULL;

			}

			if($eSale->isMarketSale()) {

				$eSale['paymentStatus'] = \selling\Sale::PAID;

			} else if($eSale['paymentStatus'] === NULL and $eMethod->notEmpty()) {

				$eSale['paymentStatus'] = \selling\Sale::NOT_PAID;

			}

			$eSale['paymentMethod'] = $eMethod;
			\selling\SaleLib::update($eSale, ['paymentMethod', 'paymentStatus', 'onlinePaymentStatus']);

		}

		\selling\Payment::model()->commit();

	});
?>
