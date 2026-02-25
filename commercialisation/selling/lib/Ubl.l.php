<?php
namespace selling;

class UblLib {

	public static function generate(\farm\Farm $eFarm, Invoice $eInvoice): string {

		[$dateMin, $dateMax] = self::getInvoicePeriod($eInvoice);

		/* vraies valeurs de production */
		$sellerSiret = $eFarm['siret'];
		$sellerSiren = $eFarm->siren();
		$sellerAddress = \pdp\AddressLib::get();

		$buyerSiren = mb_substr($eInvoice['customer']['siret'], 0, 9);
		$buyerSiret = $eInvoice['customer']['siret'];
		$buyerAddress = $eInvoice['customer']->getFullElectronicAddress();

		// adresses électroniques
		if(LIME_ENV === 'dev') {

			$sellerAddress = '315143296_104';
			$buyerAddress = '315143296_103';

			$sellerSiren = '000000001';
			$sellerSiret = '00000000100010';
			$buyerSiren = '000000002';
			$buyerSiret = '00000000200010';

		} else {

			$sellerAddress = '315143296_103';
			$buyerAddress = '315143296_104';

			$sellerSiren = '000000002';
			$sellerSiret = '00000000200020';
			$buyerSiren = '000000001';
			$buyerSiret = '00000000100020';
		}
		$discountSales = self::getDiscountSales($eInvoice);
		$discountItems = self::getDiscountItems($eInvoice);

		$xml = '<?xml version="1.0" encoding="utf-8" ?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"
	xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
	xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xmlns:ccts="urn:un:unece:uncefact:documentation:2"
	xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2"
	xmlns:udt="urn:oasis:names:specification:ubl:schema:xsd:UnqualifiedDataTypes-2"><!--BG-2-->
	<cbc:UBLVersionID>2.1</cbc:UBLVersionID>
	<cbc:CustomizationID>urn:cen.eu:en16931:2017</cbc:CustomizationID><!--BT-24-->
	<cbc:ProfileID>S1</cbc:ProfileID><!--BT-23-->
	<cbc:ID>'.encode($eInvoice['number']).'</cbc:ID><!--BT-1-->
	<cbc:IssueDate>'.$eInvoice['date'].'</cbc:IssueDate><!--BT-2-->
	<cbc:DueDate>'.$eInvoice['dueDate'].'</cbc:DueDate><!--BT-9-->
	<cbc:InvoiceTypeCode>'.self::getInvoiceTypeCode($eInvoice).'</cbc:InvoiceTypeCode><!--BT-3-->
	<cbc:Note>#PMD#'.$eFarm->getConf('invoiceLateFees').'</cbc:Note><!--BT-21 PMD-->
	<cbc:Note>#PMT#'.$eFarm->getConf('invoiceCollection').'</cbc:Note><!--BT-21 PMT-->
	<cbc:Note>#AAB#'.$eFarm->getConf('invoiceDiscount').'</cbc:Note><!--BT-21 AAB-->
	<cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode><!--BT-5-->
	<cac:InvoicePeriod><!--BG-14-->
		<cbc:StartDate>'.$dateMin.'</cbc:StartDate><!--BT-73-->
		<cbc:EndDate>'.$dateMax.'</cbc:EndDate><!--BT-74-->
		<cbc:DescriptionCode>'.self::getVatChargeability($eFarm).'</cbc:DescriptionCode><!--BT-8-->
	</cac:InvoicePeriod>
	<cac:AccountingSupplierParty><!--BG-4-->
		<cac:Party>
			<cbc:EndpointID schemeID="0225">'.$sellerAddress.'</cbc:EndpointID><!--BT-34-->
			<cac:PartyIdentification>
				<cbc:ID schemeID="0088">'.$sellerSiret.'</cbc:ID><!--BT-29-->
			</cac:PartyIdentification>
			<cac:PartyName>
				<cbc:Name>'.$eFarm['name'].'</cbc:Name><!--BT-28-->
			</cac:PartyName>
			<cac:PostalAddress><!--BG-5-->
				<cbc:StreetName>'.$eFarm['legalStreet1'].'</cbc:StreetName>
				<cbc:AdditionalStreetName>'.$eFarm['legalStreet2'].'</cbc:AdditionalStreetName>
				<cbc:CityName>'.$eFarm['legalCity'].'</cbc:CityName>
				<cbc:PostalZone>'.$eFarm['legalPostcode'].'</cbc:PostalZone>
				<cac:Country>
					<cbc:IdentificationCode>'.$eFarm['legalCountry']['code'].'</cbc:IdentificationCode><!--BT-40-->
				</cac:Country>
			</cac:PostalAddress>
			<cac:PartyTaxScheme>
				<cbc:CompanyID>'.$eFarm->getConf('vatNumber').'</cbc:CompanyID><!--BT-31-->
				<cac:TaxScheme>
					<cbc:ID>VAT</cbc:ID>
				</cac:TaxScheme>
			</cac:PartyTaxScheme>
			<cac:PartyLegalEntity>
				<cbc:RegistrationName>'.$eFarm['legalName'].'</cbc:RegistrationName><!--BT-27-->
				<cbc:CompanyID schemeID="0002">'.$sellerSiren.'</cbc:CompanyID><!--BT-30-->
			</cac:PartyLegalEntity>
		</cac:Party>
	</cac:AccountingSupplierParty>
	<cac:AccountingCustomerParty><!--BG-7-->
		<cac:Party>
			<cbc:EndpointID schemeID="0225">'.$buyerAddress.'</cbc:EndpointID><!--BT-49-->
			<cac:PartyIdentification>
				<cbc:ID schemeID="0088">'.$buyerSiret.'</cbc:ID><!--BT-46-->
			</cac:PartyIdentification>
			<cac:PostalAddress><!--BG-8-->
				<cbc:StreetName>'.$eInvoice['customer']['invoiceStreet1'].'</cbc:StreetName>
				<cbc:AdditionalStreetName>'.$eInvoice['customer']['invoiceStreet2'].'</cbc:AdditionalStreetName>
				<cbc:CityName>'.$eInvoice['customer']['invoiceCity'].'</cbc:CityName>
				<cbc:PostalZone>'.$eInvoice['customer']['invoicePostcode'].'</cbc:PostalZone>
				<cac:Country>
					<cbc:IdentificationCode>'.$eInvoice['customer']['invoiceCountry']['code'].'</cbc:IdentificationCode>
				</cac:Country>
			</cac:PostalAddress>
			<cac:PartyTaxScheme>
				<cbc:CompanyID>'.$eInvoice['customer']['vatNumber'].'</cbc:CompanyID><!--BT-48-->
				<cac:TaxScheme>
					<cbc:ID>VAT</cbc:ID>
				</cac:TaxScheme>
			</cac:PartyTaxScheme>
			<cac:PartyLegalEntity>
				<cbc:RegistrationName>'.$eInvoice['customer']['legalName'].'</cbc:RegistrationName><!--BT-44-->
				<cbc:CompanyID schemeID="0002">'.$buyerSiren.'</cbc:CompanyID><!--BT-47-->
			</cac:PartyLegalEntity>
		</cac:Party>
	</cac:AccountingCustomerParty>';
	if($discountItems !== 0.0) {
		foreach($eInvoice['cSale'] as $eSale) {
			foreach($eSale['cItem'] as $eItem) {
				if($eItem['priceInitial'] === NULL) {
					continue;
				}
				$itemAllowance = match($eSale['taxes']) {
					Sale::EXCLUDING => ($eItem['priceInitial'] - $eItem['price']),
					Sale::INCLUDING => ($eItem['priceInitial'] - $eItem['price']) / (1 + $eItem['vatRate']),
				};
				$itemInitialPrice = match($eSale['taxes']) {
					Sale::EXCLUDING => ($eItem['priceInitial']),
					Sale::INCLUDING => ($eItem['priceInitial']) / (1 + $eItem['vatRate']),
				};
				$xml .= '
	<cac:AllowanceCharge><!--BG-20-->
		<cbc:ChargeIndicator>false</cbc:ChargeIndicator>
		<cbc:AllowanceChargeReasonCode>95</cbc:AllowanceChargeReasonCode><!--BT-98-->
		<cbc:AllowanceChargeReason>'.s("Remise").'</cbc:AllowanceChargeReason><!--BT-97-->
		<cbc:Amount currencyID="EUR">'.$itemAllowance.'</cbc:Amount><!--BT-92-->
		<cbc:BaseAmount currencyID="EUR">'.$itemInitialPrice.'</cbc:BaseAmount><!--BT-93-->
		<cac:TaxCategory><!--BT-95-00-->
			<cbc:ID>S</cbc:ID><!--BT-95-->
			<cbc:Percent>'.$eItem['vatRate'].'</cbc:Percent><!--BT-96-->
			<cac:TaxScheme>
				<cbc:ID>VAT</cbc:ID>
			</cac:TaxScheme>
		</cac:TaxCategory>
	</cac:AllowanceCharge>';
			}
		}
	}
	if($discountSales !== 0.0) {
		foreach($eInvoice['cSale'] as $eSale) {
			foreach($eSale['vatByRate'] as $vatByRate) { // Prix remisé dans amount
				$reducedPrice = match($eSale['taxes']) {
					Sale::EXCLUDING => ($vatByRate['amount']),
					Sale::INCLUDING => round(($vatByRate['amount']) / (1 + $vatByRate['vatRate']), 2),
				};
				$initialPrice = round($reducedPrice / (1 - $eSale['discount'] / 100), 2);
				$allowance = $initialPrice - $reducedPrice;
				$xml .= '
	<cac:AllowanceCharge><!--BG-20-->
		<cbc:ChargeIndicator>false</cbc:ChargeIndicator>
		<cbc:AllowanceChargeReasonCode>95</cbc:AllowanceChargeReasonCode><!--BT-98-->
		<cbc:AllowanceChargeReason>'.s("Remise").'</cbc:AllowanceChargeReason><!--BT-97-->
		<cbc:Amount currencyID="EUR">'.$allowance.'</cbc:Amount><!--BT-92-->
		<cbc:BaseAmount currencyID="EUR">'.$initialPrice.'</cbc:BaseAmount><!--BT-93-->
		<cac:TaxCategory><!--BT-95-00-->
			<cbc:ID>S</cbc:ID><!--BT-95-->
			<cbc:Percent>'.$vatByRate['vatRate'].'</cbc:Percent><!--BT-96-->
			<cac:TaxScheme>
				<cbc:ID>VAT</cbc:ID>
			</cac:TaxScheme>
		</cac:TaxCategory>
	</cac:AllowanceCharge>';
			}
		}
	}
	$xml .= '
	<cac:TaxTotal><!--BT-110-00-->
		<cbc:TaxAmount currencyID="EUR">'.$eInvoice['vat'].'</cbc:TaxAmount><!--BT-110-->';
			foreach($eInvoice['vatByRate'] as $vatByRate) {
				$vatCode = self::getInvoiceVatCode($eInvoice, $vatByRate['vatRate']);
        $xml .= '
		<cac:TaxSubtotal><!--BG-23-->
			<cbc:TaxableAmount currencyID="EUR">'.$vatByRate['amount'].'</cbc:TaxableAmount><!--BT-116-->
			<cbc:TaxAmount currencyID="EUR">'.$vatByRate['vat'].'</cbc:TaxAmount><!--BT-117-->
			<cac:TaxCategory>
				<cbc:ID>'.$vatCode.'</cbc:ID><!--BT-118-->
				<cbc:Percent>'.$vatByRate['vatRate'].'</cbc:Percent><!--BT-119-->';
				if($vatCode === 'G') { // Exportation
					$xml .= '
				<cbc:TaxExemptionReasonCode>VATEX-EU-151</cbc:TaxExemptionReasonCode><!--BT-121-->
				<cbc:TaxExemptionReason>'.s("Export hors UE").'</cbc:TaxExemptionReason><!--BT-120-->';
				}
			$xml .= '
				<cac:TaxScheme>
					<cbc:ID>VAT</cbc:ID>
				</cac:TaxScheme>
			</cac:TaxCategory>
		</cac:TaxSubtotal>';
				}
		$xml .= '
	</cac:TaxTotal>
	<cac:LegalMonetaryTotal>
		<cbc:LineExtensionAmount currencyID="EUR">'.self::sumPriceExcludingVat($eInvoice).'</cbc:LineExtensionAmount><!--BT-106-->
		<cbc:TaxExclusiveAmount currencyID="EUR">'.$eInvoice['priceExcludingVat'].'</cbc:TaxExclusiveAmount><!--BT-109-->
		<cbc:TaxInclusiveAmount currencyID="EUR">'.$eInvoice['priceIncludingVat'].'</cbc:TaxInclusiveAmount><!--BT-112-->';
		if($discountItems !== 0.0 or $discountSales !== 0.0) {
			$xml .= '
		<cbc:AllowanceTotalAmount currencyID="EUR">'.round($discountItems + $discountSales, 2).'</cbc:AllowanceTotalAmount><!--BT-107-->';
		}
		$xml .= '
		<cbc:PayableAmount currencyID="EUR">'.$eInvoice['priceIncludingVat'].'</cbc:PayableAmount><!--BT-115-->
	</cac:LegalMonetaryTotal>';
	$lineNumber = 1;
	foreach($eInvoice['cSale'] as $eSale) {
		foreach($eSale['cItem'] as $eItem) {
			if($eItem['priceInitial'] !== NULL) {
				$price = match($eSale['taxes']) {
					Sale::EXCLUDING => $eItem['priceInitial'],
					Sale::INCLUDING => round($eItem['priceInitial'] / (1 + $eItem['vatRate']), 2),
				};
			} else {
				$price = match($eSale['taxes']) {
					Sale::EXCLUDING => $eItem['price'],
					Sale::INCLUDING => round($eItem['price'] / (1 + $eItem['vatRate']), 2),
				};
			}
      $xml .= '
	<cac:InvoiceLine><!--BG-25-->
		<cbc:ID>'.$lineNumber.'</cbc:ID><!--BT-126-->
		<cbc:InvoicedQuantity unitCode="'.self::getUnitCode($eItem['unit']).'">'.$eItem['number'].'</cbc:InvoicedQuantity><!--BT-129--><!--BT-130-->
		<cbc:LineExtensionAmount currencyID="EUR">'.$price.'</cbc:LineExtensionAmount><!--BT-131 (prix non remisé)-->
		<cac:InvoicePeriod><!--BG-26-->
			<cbc:StartDate>'.$eSale['deliveredAt'].'</cbc:StartDate><!--BT-134-->
			<cbc:EndDate>'.$eSale['deliveredAt'].'</cbc:EndDate><!--BT-135-->
		</cac:InvoicePeriod>
		<cac:Item>
			<cbc:Name>'.$eItem['name'].'</cbc:Name><!--BT-153-->
			<cac:ClassifiedTaxCategory><!--BG-30-->
				<cbc:ID>'.self::getVatCode($eItem['vatCode']).'</cbc:ID><!--BT-151-->
				<cbc:Percent>'.$eItem['vatRate'].'</cbc:Percent><!--BT-152-->
				<cac:TaxScheme><!--BT-151-1-->
					<cbc:ID>VAT</cbc:ID><!--BT-151-2-->
				</cac:TaxScheme>
			</cac:ClassifiedTaxCategory>
		</cac:Item>
		<cac:Price><!--BG-29-->
		<cbc:PriceAmount currencyID="EUR">'.$eItem['unitPrice'].'</cbc:PriceAmount><!--BT-146-->
		<cbc:BaseQuantity unitCode="'.self::getUnitCode($eItem['unit']).'">'.$eItem['number'].'</cbc:BaseQuantity>';
		$xml .= '
		</cac:Price>
	</cac:InvoiceLine>';
				$lineNumber++;
			}
		if($eSale['shipping'] !== NULL) {
      $xml .= '
	<cac:InvoiceLine><!--BG-25-->
		<cbc:ID>'.$lineNumber.'</cbc:ID><!--BT-126-->
		<cbc:InvoicedQuantity unitCode="C62">1</cbc:InvoicedQuantity><!--BT-129--><!--BT-130-->
		<cbc:LineExtensionAmount currencyID="EUR">'.$eSale['shippingExcludingVat'].'</cbc:LineExtensionAmount><!--BT-131 (prix non remisé)-->
		<cac:InvoicePeriod><!--BG-26-->
			<cbc:StartDate>'.$eSale['deliveredAt'].'</cbc:StartDate><!--BT-134-->
			<cbc:EndDate>'.$eSale['deliveredAt'].'</cbc:EndDate><!--BT-135-->
		</cac:InvoicePeriod>
		<cac:Item>
			<cbc:Name>'.s("Frais de livraison").'</cbc:Name><!--BT-153-->
			<cac:ClassifiedTaxCategory><!--BG-30-->
				<cbc:ID>'.self::getVatCode().'</cbc:ID><!--BT-151-->
				<cbc:Percent>'.$eSale['shippingVatRate'].'</cbc:Percent><!--BT-152-->
				<cac:TaxScheme><!--BT-151-1-->
					<cbc:ID>VAT</cbc:ID><!--BT-151-2-->
				</cac:TaxScheme>
			</cac:ClassifiedTaxCategory>
		</cac:Item>
		<cac:Price><!--BG-29-->
		<cbc:PriceAmount currencyID="EUR">'.$eSale['shippingExcludingVat'].'</cbc:PriceAmount><!--BT-146-->
		<cbc:BaseQuantity unitCode="C62">1</cbc:BaseQuantity>';
		$xml .= '
		</cac:Price>
	</cac:InvoiceLine>';
				$lineNumber++;

		}
		}
    $xml .= '
</Invoice>
';

		return $xml;

	}

	private static function getUnitCode(Unit $eUnit): string {

		return match($eUnit['fqn']) {
			'tray' => 'XPU',
			'box' => 'BB',
			'bunch' => 'XBH',
			'bottle' => 'XBO',
			'centiliter' => 'CLT',
			'parcel' => 'XPC',
			'package' => 'XPK',
			'gram' => 'GRM',
			'hour' => 'HUR',
			'kg' => 'KGM',
			'liter' => 'LTR',
			'milliliter' => 'MLT',
			'unit' => 'C62',
			'pot' => 'XPT',
			'bag' => 'XBG',
			'ton' => 'TNE',
			'gram-100' => 'CTG',
			'gram-250' => 'CTG',
			'gram-500' => 'CTG',
			default => 'C62', // ONE
		};
	}

	private static function getDiscountSales(Invoice $eInvoice): float {

		$discount = 0.0;

		foreach($eInvoice['cSale'] as $eSale) {

			if($eSale['discount'] !== NULL) { // Remise globale au niveau de la vente

				foreach($eSale['vatByRate'] as $vatByRate) {

					$discountByRate = $vatByRate['amount'] / (1 - $eSale['discount']/100) - $vatByRate['amount'];

					$discount += match($eSale['taxes']) {
						Sale::EXCLUDING => $discountByRate,
						Sale::INCLUDING => $discountByRate / (1 + $vatByRate['vatRate']),
					};

				}
			}

		}

		return round($discount, 2);

	}

	private static function getDiscountItems(Invoice $eInvoice): float {

		$discount = 0.0;

		foreach($eInvoice['cSale'] as $eSale) {

			foreach($eSale['cItem'] as $eItem) {

				if($eItem['priceInitial'] === NULL) {
					continue;
				}

				$discount += match($eSale['taxes']) {
					Sale::EXCLUDING => ($eItem['priceInitial'] - $eItem['price']),
					Sale::INCLUDING => ($eItem['priceInitial'] - $eItem['price']) / (1 + $eItem['vatRate']),
				};
			}

		}

		return round($discount, 2);

	}

	private static function sumPriceExcludingVat(Invoice $eInvoice): float {

		$sum = 0;

		foreach($eInvoice['cSale'] as $eSale) {
			foreach($eSale['cItem'] as $eItem) {

				$priceField = $eItem['priceInitial'] === NULL ? 'price' : 'priceInitial';

				$sum += match($eSale['taxes']) {
					Sale::EXCLUDING => $eItem[$priceField],
					Sale::INCLUDING => round($eItem[$priceField] / (1 + $eItem['vatRate'] ?? 0)),
				};

			}
			if($eSale['shipping']) {
				$sum += $eSale['shippingExcludingVat'];
			}
		}

		return $sum;

	}

	private static function getVatCode(?string $vatCode = NULL): string {

		if($vatCode === NULL) {
			return 'S'; // Cas standard par défaut
		}

		return match($vatCode) {
			Item::STANDARD => 'S',
			Item::ZERO => 'Z',
			Item::EXEMPT => 'E',
			Item::AUTOLIQUIDATION => 'AE',
			Item::INTRACOM_DELIVERY => 'K',
			Item::EXPORTATION => 'G',
			Item::OUT_OF_VAT => 'O',
			default => 'S',
		};

	}

	private static function getInvoiceVatCode(Invoice $eInvoice, float $vatRate): string {

		$vatCodes = [];
		foreach($eInvoice['cSale'] as $eSale) {
			foreach($eSale['cItem'] as $eItem) {
				if($eItem['vatRate'] !== $vatRate) {
					continue;
				}
				$vatCode = self::getVatCode($eItem['vatCode']);
				if(isset($vatCodes[$vatCode]) === FALSE) {
					$vatCodes[$vatCode] = 0;
				}
				$vatCodes[$vatCode]++;
			}
		}

		arsort($vatCodes);
		return first(array_keys($vatCodes));

	}

	private static function getVatChargeability(\farm\Farm $eFarm): int {

		if($eFarm->getConf('vatChargeability') === \farm\Configuration::DEBIT) {
			return 3;
		}

		return 35;

	}

	private static function getInvoicePeriod(Invoice $eInvoice): array {

		$eSaleFirst = new Sale();
		$eSaleLast = new Sale();

		foreach($eInvoice['cSale'] as $eSale) {

			if($eSaleFirst->empty() or $eSaleLast['deliveredAt'] > $eSale['deliveredAt']) {
				$eSaleFirst = $eSale;
			}

			if($eSaleLast->empty() or $eSaleLast['deliveredAt'] < $eSale['deliveredAt']) {
				$eSaleLast = $eSale;
			}

		}

		return [$eSaleFirst['deliveredAt'], $eSaleLast['deliveredAt']];

	}

	private static function getInvoiceTypeCode(Invoice $eInvoice): string {

		if($eInvoice->isCreditNote()) {
			return '381';
		}

		return '380';

	}

}
