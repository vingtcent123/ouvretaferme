<?php
namespace company;

Class MindeeLib {

	const API_URL = 'https://api.mindee.net/v1/products/mindee/invoices/v4/predict';

	const TYPE_INVOICE = 'INVOICE';
	const TYPE_CREDIT_NOTE = 'CREDIT NOTE';
	const TYPE_SUPPLIER_COMPANY_REGISTRATION_VAT_NUMBER = 'VAT NUMBER';
	const TYPE_SUPPLIER_COMPANY_REGISTRATION_SIREN = 'SIREN';
	const TYPE_SUPPLIER_COMPANY_REGISTRATION_SIRET = 'SIRET';

	public static function getInvoiceData(\farm\Farm $eFarm, string $filepath): array {

		if(LIME_ENV === 'dev') {

			$files = [
				'leroy-merlin.json',
				'prixtel.json',
				'loxam.json',
				'intermarche-photo.json',
				'pereira-1.json',
				'frab.json',
				'mon-irrigation.json',
				'lidl.json',
				'stripe.json',
				'plants-pro.json',
				'remy.json',
				'essembio-credit.json',
			];

			$randomFile = $files[mt_rand(0, count($files) - 1)];
			$randomFile = 'mon-irrigation.json';
			$data = json_decode(file_get_contents('/tmp/shared/'.$randomFile), TRUE);

			$prediction = $data['prediction'];

		} else {

			$options = [
				CURLOPT_HTTPHEADER => [
					'Authorization: Token '.\Setting::get('company\mindeeApiKey'),
					'Content-Type: multipart/form-data',
					CURLOPT_RETURNTRANSFER => true,
				],
			];

			$params = [
				'document' => new \CURLFile($filepath, 'pdf', 'invoice.pdf')
			];
			$curl = new \util\CurlLib();

			$data = json_decode($curl->exec(self::API_URL, $params, 'POST', $options), TRUE);

			if($data['api_request']['error']) {
				throw new \NotExpectedAction('Unable to read invoice : '.json_encode($data['api_request']['error']));
			}

			$prediction = $data['document']['inference']['prediction'];
		}

		$documentType = $prediction['document_type']['value'];
		if(in_array($documentType, [self::TYPE_INVOICE, self::TYPE_CREDIT_NOTE]) === FALSE) {
			\Fail::log('journal:Operation::invoice.incorrectType');
			return [];
		}

		$supplier = [
			'name' => $prediction['supplier_name']['value'] ?? NULL,
			'vatNumber' => array_find($prediction['supplier_company_registrations'], fn($numbers) => $numbers['type'] === self::TYPE_SUPPLIER_COMPANY_REGISTRATION_VAT_NUMBER)['value'] ?? NULL,
			'siren' => array_find($prediction['supplier_company_registrations'], fn($numbers) => $numbers['type'] === self::TYPE_SUPPLIER_COMPANY_REGISTRATION_SIREN)['value'] ?? NULL,
			'siret' => array_find($prediction['supplier_company_registrations'], fn($numbers) => $numbers['type'] === self::TYPE_SUPPLIER_COMPANY_REGISTRATION_SIRET)['value'] ?? NULL,
		];
		$customer = [
			'name' => $prediction['customer_name']['value'] ?? NULL,
			'vatNumber' => array_find($prediction['customer_company_registrations'], fn($numbers) => $numbers['type'] === self::TYPE_SUPPLIER_COMPANY_REGISTRATION_VAT_NUMBER)['value'] ?? NULL,
			'siren' => array_find($prediction['customer_company_registrations'], fn($numbers) => $numbers['type'] === self::TYPE_SUPPLIER_COMPANY_REGISTRATION_SIREN)['value'] ?? NULL,
			'siret' => array_find($prediction['customer_company_registrations'], fn($numbers) => $numbers['type'] === self::TYPE_SUPPLIER_COMPANY_REGISTRATION_SIRET)['value'] ?? NULL,
		];

		// Détermine le client / le fournisseur et le sens (débit/crédit)
		if(self::scoreCurrentFarm($eFarm, $supplier) < self::scoreCurrentFarm($eFarm, $customer)) {

			$thirdParty = $customer;
			$type = match($documentType) {
				self::TYPE_INVOICE => \journal\Operation::CREDIT,
				self::TYPE_CREDIT_NOTE => \journal\Operation::DEBIT,
			};

		} else {

			$thirdParty = $supplier;
			$type = match($documentType) {
				self::TYPE_INVOICE => \journal\Operation::DEBIT,
				self::TYPE_CREDIT_NOTE => \journal\Operation::CREDIT,
			};

		}

		$paymentDate = $prediction['payment_date']['value'];
		$date = $prediction['date']['value'];
		$invoiceNumber = $prediction['invoice_number']['value'];

		$shipping = [];
		foreach($prediction['line_items'] as $lineItem) {
			if(
				mb_strpos(mb_strtolower($lineItem['description']), 'livraison') !== FALSE
				or mb_strpos(mb_strtolower($lineItem['description']), 'transport') !== FALSE
			) {
				$amountShippingWithoutVat = round($lineItem['unit_price'] * $lineItem['quantity'], 2);
				$amountShippingVat = round($lineItem['tax_rate'] * $amountShippingWithoutVat / 100, 2);
				$shipping = [
					'vatRate' => $lineItem['tax_rate'],
					'amount' => $amountShippingWithoutVat,
					'amountVat' => $amountShippingVat,
					'amountIncludingVAT' => $lineItem['total_amount'] + $amountShippingVat,
				];
			}
		}

		$taxes = [];
		foreach($prediction['taxes'] as $tax) {

			$amount = $tax['base'] === NULL ? round($tax['value'] * 100 / $tax['rate'], 2) : $tax['base'];

			$taxes[] = [
				'vatRate' => $tax['rate'],
				'amountVat' => $tax['value'],
				'amount' => $amount,
				'amountIncludingVAT' => $amount + $tax['value'],
			];
		}

		if(count($taxes) > 0) {

			if(count($shipping) > 0) {

				$taxesWithoutShipping = first(array_filter($taxes, fn ($tax) => $tax['vatRate'] !== $shipping['vatRate'] || $tax['amount'] !== $shipping['amount']));

				$vatRate = $taxesWithoutShipping['vatRate'];
				if($shipping['vatRate'] === $taxesWithoutShipping['vatRate']) {

					$amountVat = $taxesWithoutShipping['amountVat'] - $shipping['amountVat'];
					$amount = $taxesWithoutShipping['amount'] - $shipping['amount'];
					$amountIncludingVAT = $taxesWithoutShipping['amountIncludingVAT'] - $shipping['amountIncludingVAT'];

				} else {

					$amountVat = $taxesWithoutShipping['amountVat'];
					$amount = $taxesWithoutShipping['amount'];
					$amountIncludingVAT = $taxesWithoutShipping['amountIncludingVAT'];

				}

				$totalPrices = [
					'vatRate' => $vatRate,
					'amountVat' => $amountVat,
					'amount' => $amount,
					'amountIncludingVAT' => $amountIncludingVAT,
				];

			} else {

				$totalPrices = [
					'vatRate' => $taxes[0]['vatRate'],
					'amountVat' => $taxes[0]['amountVat'],
					'amount' => $taxes[0]['amount'],
					'amountIncludingVAT' => $taxes[0]['amountIncludingVAT'],
				];

			}

		} else {

			$amount = $prediction['total_net']['value'];
			$amountVat = $prediction['total_tax']['value'];

			$totalPrices = [
				'vatRate' => round($amountVat / $amount, 4) * 100,
				'amountVat' => $amountVat,
				'amount' => $amount,
				'amountIncludingVAT' => $prediction['total_amount']['value'],
			];
		}


		return [
			'thirdParty' => $thirdParty,
			'type' => $type,
			'invoiceNumber' => $invoiceNumber,
			'paymentDate' => ($paymentDate !== NULL and \util\DateLib::isValid($paymentDate)) ? $paymentDate : NULL,
			'date' => ($date !== NULL and \util\DateLib::isValid($date)) ? $date : NULL,
			'prices' => $totalPrices,
			'shipping' => $shipping,
		];

	}

	private static function scoreCurrentFarm(\farm\Farm $eFarm, array $data): float {

		$farmVatNumber = $eFarm->getSelling('invoiceVat');
		$farmSiret = $eFarm['siret'];
		$farmLegalName = $eFarm['legalName'];

		if(
			$data['vatNumber'] === $farmVatNumber
			or $data['siret'] === $farmSiret
			or ($data['siren'] !== NULL and mb_substr($farmSiret, 0, mb_strlen($data['siren'])) === $data['siren'])
		) {
			return 0;
		}

		$lev = levenshtein($farmLegalName, $data['name']);

		return $lev;

	}

}
