<?php
namespace selling;

class UblLib {

	public static function generate(\farm\Farm $eFarm, Invoice $eInvoice): string {

		list($dateMin, $dateMax) = self::getInvoicePeriod($eInvoice);

		/* vraies valeurs de production */
		$sellerSiret = $eFarm['siret'];
		$sellerSiren = $eFarm->siren();
		$buyerSiren = mb_substr($eInvoice['customer']['siret'], 0, 9);
		$buyerSiret = $eInvoice['customer']['siret'];

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
    <cbc:Note>#PMD#Tout retard de paiement engendre une pénalité exigible à compter de la date d\'échéance, calculée sur la base de trois fois le taux d\'intérêt légal.</cbc:Note><!--BT-21 PMD (TODO)-->
    <cbc:Note>#PMT#Indemnité forfaitaire pour frais de recouvrement en cas de retard de paiement : 40 €.</cbc:Note><!--BT-21 PMT (TODO)-->
    <cbc:Note>#AAB#Les règlements reçus avant la date d\'échéance ne donneront pas lieu à escompte.</cbc:Note><!--BT-21 AAB (TODO)-->
    <cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode><!--BT-5-->
    <cac:InvoicePeriod><!--BG-14-->
        <cbc:StartDate>'.$dateMin.'</cbc:StartDate><!--BT-73-->
        <cbc:EndDate>'.$dateMax.'</cbc:EndDate><!--BT-74-->
        <cbc:DescriptionCode>'.self::getVatChargeability($eFarm, $eInvoice).'</cbc:DescriptionCode><!--BT-8-->
    </cac:InvoicePeriod>
    <cac:OrderReference>
        <cbc:ID>PO202525478</cbc:ID><!--BT-13 (TODO)-->
    </cac:OrderReference>
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
    </cac:AccountingCustomerParty>
    <cac:TaxTotal><!--BT-110-00-->
        <cbc:TaxAmount currencyID="EUR">'.$eInvoice['vat'].'</cbc:TaxAmount><!--BT-110-->';
				foreach($eInvoice['vatByRate'] as $vatByRate) {
        $xml .= '
          <cac:TaxSubtotal><!--BG-23-->
            <cbc:TaxableAmount currencyID="EUR">'.$vatByRate['amount'].'</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID="EUR">'.$vatByRate['vat'].'</cbc:TaxAmount>
            <cac:TaxCategory>
                <cbc:ID>'.self::getVatCode($vatByRate).'</cbc:ID><!--BT-118-->
                <cbc:Percent>'.$vatByRate['vatRate'].'</cbc:Percent><!--BT-119-->
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
        <cbc:TaxInclusiveAmount currencyID="EUR">'.$eInvoice['priceIncludingVat'].'</cbc:TaxInclusiveAmount><!--BT-112-->
        <cbc:PayableAmount currencyID="EUR">'.$eInvoice['priceIncludingVat'].'</cbc:PayableAmount><!--BT-115-->
    </cac:LegalMonetaryTotal>';
		$lineNumber = 1;
		foreach($eInvoice['cSale'] as $eSale) {
			foreach($eSale['cItem'] as $eItem) {
        $xml .= '
    <cac:InvoiceLine><!--BG-25-->
        <cbc:ID>'.$lineNumber.'</cbc:ID><!--BT-126-->
        <cbc:InvoicedQuantity unitCode="'.self::getUnitCode($eItem['unit']).'">'.$eItem['number'].'</cbc:InvoicedQuantity><!--BT-129--><!--BT-130-->
        <cbc:LineExtensionAmount currencyID="EUR">'.$eItem['price'].'</cbc:LineExtensionAmount><!--BT-131-->
        <cac:InvoicePeriod><!--BG-26-->
          <cbc:StartDate>'.$eSale['deliveredAt'].'</cbc:StartDate><!--BT-134-->
          <cbc:EndDate>'.$eSale['deliveredAt'].'</cbc:EndDate><!--BT-135-->
        </cac:InvoicePeriod>
        <cac:Item>
            <cbc:Name>'.$eItem['name'].'</cbc:Name><!--BT-153-->
            <cac:ClassifiedTaxCategory><!--BG-30-->
                <cbc:ID>'.self::getVatCode($eItem['vatRate']).'</cbc:ID>
                <cbc:Percent>'.$eItem['vatRate'].'</cbc:Percent>
                <cac:TaxScheme>
                    <cbc:ID>VAT</cbc:ID>
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
		}
    $xml .= '
</Invoice>
';

		return $xml;

	}

	private static function getUnitCode(Unit $eUnit): string { // TODO
		return 'C62'; // ONE
	}
	private static function sumAllowance(Invoice $eInvoice): float { // TODO BT-107 (toutes les remises par item et somme pour la facture)

		$sum = 0;

		foreach($eInvoice['cSale'] as $eSale) {
			foreach($eSale['cItem'] as $eItem) {
				$sum += $eItem['price'];
			}
		}

		return $sum;

	}

	private static function sumPriceExcludingVat(Invoice $eInvoice): float {

		$sum = 0;

		foreach($eInvoice['cSale'] as $eSale) {
			foreach($eSale['cItem'] as $eItem) {
				$sum += $eItem['price'];
			}
		}

		return $sum;

	}

	private static function getVatCode(array|float $vatByRate): string { // TODO

		if(
			(is_array($vatByRate) and $vatByRate['vatRate'] === 0) or
			(is_float($vatByRate) and $vatByRate === 0.0)
		){
			return 'Z';
		}

		return 'S';

	}

	private static function getVatChargeability(\farm\Farm $eFarm, Invoice $eInvoice): int {

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
