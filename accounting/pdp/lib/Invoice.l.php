<?php
namespace pdp;

Class InvoiceLib {

	public static function synchronize(string $accessToken) {

		$eInvoiceLast = \invoicing\Invoice::model()
			->select('id')
			->sort(['createdAt' => SORT_DESC])
			->get();

		$body = [];

		if($eInvoiceLast->notEmpty()) {
			$body['starting_after_id'] = $eInvoiceLast['id'];
		}

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$accessToken,
				'Content-Type: application/json',
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();
		$data = json_decode($curl->exec(PdpSetting::SUPER_PDP_API_URL.'invoices', http_build_query($body), 'GET', $options), TRUE);

		if($data === NULL) {
			return;
		}

		$cCompany = new \Collection();

		$farmIdentifier = AddressLib::getSchemeIdentifier();

		\invoicing\Invoice::model()->beginTransaction();

		foreach($data['data'] as $invoice) {

			$invoiceDetails = self::getOne($accessToken, $invoice['id']);
			if($invoiceDetails === NULL) {
				$status = \invoicing\Invoice::ERROR;
			} else {
				$status = \invoicing\Invoice::SYNCHRONIZED;
			}

			if($cCompany->offsetExists($invoice['company_id']) === FALSE) {

				$eCompany = CompanyLib::getById($invoice['company_id']);
				$cCompany->offsetSet($invoice['company_id'], $eCompany);

			} else {

				$cCompany->offsetGet($invoice['company_id']);

			}

			if($eCompany->empty()) {
				continue;
			}

			if(isset($invoiceDetails['en_invoice']['vat_break_down'])) {
				$vatByRate = [];
				foreach($invoiceDetails['en_invoice']['vat_break_down'] as $vatBreakdown) {
					$vatByRate[] = [
						'vat' => (float)$vatBreakdown['vat_category_tax_amount'],
						'amount' => (float)$vatBreakdown['vat_category_taxable_amount'],
						'vatRate' => (float)$vatBreakdown['vat_category_rate'],
					];
				}
			} else {
				$vatByRate = [];
			}

			// Enregistrer le buyer et le seller
			$en_invoice = $invoiceDetails['en_invoice'];

			$buyer = $en_invoice['buyer'];
			$eThirdPartyBuyer = self::buildThirdParty($buyer, $farmIdentifier);

			$seller = $en_invoice['seller'];
			$eThirdPartySeller = self::buildThirdParty($seller, $farmIdentifier);

			$eInvoice = new \invoicing\Invoice([
				'id' => $invoice['id'],
				'company' => $eCompany,
				'status' => $status,
				'direction' => $invoiceDetails['direction'] ?? NULL,
				'number' => $en_invoice['number'] ?? NULL,
				'issuedAt' => $en_invoice['issue_date'] ?? NULL,
				'createdAt' => date('Y-m-d H:i:s', strtotime($invoice['created_at'])),
				'paymentDueAt' => $en_invoice['payment_due_date'] ?? NULL,
				'amountExcludingVat' => (float)$en_invoice['totals']['total_without_vat'] ?? NULL,
				'amountIncludingVat' => (float)$en_invoice['totals']['total_with_vat'] ?? NULL,
				'vatByRate' => $vatByRate,
				'vat' => (float)$en_invoice['totals']['total_vat_amount']['value'] ?? 0.0,
				'buyer' => $eThirdPartyBuyer,
				'seller' => $eThirdPartySeller,
			]);

			\invoicing\Invoice::model()->insert($eInvoice);

			$lines = $en_invoice['lines'];

			foreach($lines as $line) {

				$eLine = new \invoicing\Line([
					'invoice' => $eInvoice,
					'identifier' => $line['identifier'],
					'name' => $line['item_information']['name'],
					'quantity' => (float)$line['invoiced_quantity'],
					'quantityCode' => $line['invoiced_quantity_code'],
					'price' => $line['net_amount'],
					'unitPrice' => (float)$line['price_details']['item_net_price'],
					'vatRate' => (float)$line['vat_information']['invoiced_item_vat_rate'],
					'vatCode' => $line['vat_information']['invoiced_item_vat_category_code'],
				]);

				\invoicing\Line::model()->insert($eLine);

			}

			foreach($invoiceDetails['events'] as $event) {

				$eEvent = new \invoicing\Event([
					'id' => $event['id'],
					'invoice' => $eInvoice,
					'createdAt' => date('Y-m-d H:i:s', strtotime($event['created_at'])),
					'statusCode' => $event['status_code'],
					'statusText' => $event['status_text'],
				]);

				\invoicing\Event::model()->insert($eEvent);

			}

		}

		\invoicing\Invoice::model()->commit();

	}

	private static function buildThirdParty(array $buyerSeller, string $farmIdentifier): \invoicing\ThirdParty {

		$eThirdParty = new \invoicing\ThirdParty([
			'name' => $buyerSeller['name'],
			'electronicAddress' => $buyerSeller['electronic_address']['scheme'].':'.$buyerSeller['electronic_address']['value'],
			'countryCode' => $buyerSeller['postal_address']['country_code'],
			'vatNumber' => $buyerSeller['vat_identifier'] ?? NULL,
			'legalIdentifier' => $buyerSeller['legal_registration_identifier']['scheme'].':'.$buyerSeller['legal_registration_identifier']['scheme'],
		]);

		if(isset($buyerSeller['identifiers'])) {
			$siren = array_find($buyerSeller['identifiers'], fn($identifier) => $identifier['scheme'] === $farmIdentifier);
			if($siren) {
				$eThirdParty['siren'] = $siren['value'];
			}
		}

		\invoicing\ThirdParty::model()->option('add-replace')->insert($eThirdParty);
		$eThirdParty = \invoicing\ThirdPartyLib::getByElectronicAddress($eThirdParty['electronicAddress']);

		return $eThirdParty;

	}

	private static function getOne(string $accessToken, int $id): ?array {

		return CurlLib::send($accessToken, PdpSetting::SUPER_PDP_API_URL.'invoices/'.$id, [], 'GET');

	}
}
