<?php
namespace pdp;

class InvoiceLib {

	public static function send(\selling\Invoice $eInvoice): void {

		$accessToken = ConnectionLib::getValidToken();

		$eFarm = \farm\FarmLib::getById($eInvoice['farm']['id'], \farm\Farm::getSelection() + ['legalCountry' => ['code']]);

		$eInvoice = \selling\InvoiceLib::getById($eInvoice['id'], [
			'id', 'date', 'dueDate',
			'number', 'vat', 'vatByRate', 'priceExcludingVat', 'priceIncludingVat', 'sales',
			'customer' => ['legalName', 'siret', 'vatNumber', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'invoiceCountry' => ['code']],
		]);

		$eInvoice['cSale'] = \selling\SaleLib::getByIds($eInvoice['sales'], [
			'id', 'deliveredAt',
			'cItem' => \selling\Item::model()
				->select(\selling\Item::getSelection() + ['product' => 'reference'])
				->delegateCollection('sale')
    ]);

		$content = \selling\UblLib::generate($eFarm, $eInvoice);

		try {

			self::sendToPdp($accessToken, $content);

			\account\LogLib::save('sendInvoice', 'Superpdp', ['invoice' => $eInvoice['id']]);

		} catch(\Exception $e) {

			trigger_error('Unable to send invoice '.$eInvoice['id'].' to SuperPDP : '.$e->getMessage());

		}

	}

	// Note : les événements sont synchronisés séparément
	public static function synchronize(\farm\Farm $eFarm, string $accessToken) {

		$eInvoiceLast = \invoicing\Invoice::model()
			->select('id')
			->sort(['createdAt' => SORT_DESC])
			->get();

		$hasAfter = TRUE;

		$body = [];

		if($eInvoiceLast->notEmpty()) {
			$lastId = $eInvoiceLast['id'];
		} else {
			$lastId = NULL;
		}

		while($hasAfter === TRUE) {

			if($lastId !== NULL) {
				$body['starting_after_id'] = $lastId;
			}

			$data = CurlLib::send($accessToken, PdpSetting::SUPER_PDP_API_URL.'invoices', http_build_query($body), 'GET');
			$hasAfter = ($data['has_after'] ?? FALSE);

			if($data === NULL) {
				return;
			}

			$cCompany = new \Collection();

			$farmIdentifier = AddressLib::getSchemeIdentifier($eFarm);

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

			}

			$lastId = $invoice['id'];

			\invoicing\Invoice::model()->commit();

		}

	}

	private static function buildThirdParty(array $thirdParty, string $farmIdentifier): \invoicing\ThirdParty {

		$electronicAddress = $thirdParty['electronic_address']['scheme'].':'.$thirdParty['electronic_address']['value'];

		$eThirdParty = \invoicing\ThirdPartyLib::getByElectronicAddress($electronicAddress);

		if($eThirdParty->notEmpty()) {
			return $eThirdParty;
		}

		$eThirdParty = new \invoicing\ThirdParty([
			'name' => $thirdParty['name'],
			'electronicAddress' => $thirdParty['electronic_address']['scheme'].':'.$thirdParty['electronic_address']['value'],
			'countryCode' => $thirdParty['postal_address']['country_code'],
			'vatNumber' => $thirdParty['vat_identifier'] ?? NULL,
			'legalIdentifier' => $thirdParty['legal_registration_identifier']['scheme'].':'.$thirdParty['legal_registration_identifier']['scheme'],
		]);

		if(isset($thirdParty['identifiers'])) {
			$siren = array_find($thirdParty['identifiers'], fn($identifier) => $identifier['scheme'] === $farmIdentifier);
			if($siren) {
				$eThirdParty['siren'] = $siren['value'];
			}
		}

		\invoicing\ThirdParty::model()->insert($eThirdParty);
		$eThirdParty = \invoicing\ThirdPartyLib::getByElectronicAddress($eThirdParty['electronicAddress']);

		return $eThirdParty;

	}

	private static function getOne(string $accessToken, int $id): ?array {

		return CurlLib::send($accessToken, PdpSetting::SUPER_PDP_API_URL.'invoices/'.$id, [], 'GET');

	}

	private static function sendToPdp(string $accessToken, string $content): void {

		$options = [
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer '.$accessToken,
			],
			CURLOPT_RETURNTRANSFER => TRUE,
		];

		$curl = new \util\CurlLib();

		$data = json_decode($curl->exec(PdpSetting::SUPER_PDP_API_URL.'invoices', $content, 'POST', $options), TRUE);

		if(($data['http_status_code'] ?? NULL) === 400) {
			throw new \Exception($data['message']);
		}

	}
}
