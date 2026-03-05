<?php
new Page()
	->get('/livre-des-recettes', function($data) {

		$data->eBook = \receipts\BookLib::getActive();

		if($data->eBook->empty()) {

			$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-receipts');
			$data->tipNavigation = 'inline';

		} else {

			if($data->eBook['operations'] > 0) {

				$data->page = GET('page', 'int');

				$data->search = new Search([
					'type' => GET('type'),
					'source' => GET('source'),
					'account' => GET('account'),
				]);

				$data->ccLine = \receipts\LineLib::getByBook($data->eBook, $data->page, $data->search);

				$data->cCashflow = \receipts\SuggestionLib::getForCashflow($data->eBook['paymentMethod'], $data->eBook['closedAt']);
				$data->cInvoice = \receipts\SuggestionLib::getForInvoice($data->eBook['paymentMethod'], $data->eBook['closedAt']);
				$data->cSale = \receipts\SuggestionLib::getForSale($data->eBook['paymentMethod'], $data->eBook['closedAt']);

			}

		}

		throw new ViewAction($data);

	});
?>
