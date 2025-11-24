<?php
namespace selling;

// Lors du réimport de FPDI, modifier l'autoload.php pour rajouter l'argument prepend à true dans spl_autoload_register
require_once __DIR__.'/FPDI-2.6.3/src/autoload.php';
require_once __DIR__.'/FPDF-1.8.6/fpdf.php';
require_once __DIR__.'/factur-x-2.3.1/src/Fpdi/FdpiFacturx.php';
require_once __DIR__.'/factur-x-2.3.1/src/Utils/ProfileHandler.php';
require_once __DIR__.'/factur-x-2.3.1/src/XsdValidator.php';
require_once __DIR__.'/factur-x-2.3.1/src/Reader.php';
require_once __DIR__.'/factur-x-2.3.1/src/Writer.php';

class FacturXLib {

	public static function generateInvoiceXml(Invoice $eInvoice): string {

		// minimum : 'urn:factur-x.eu:1p0:minimum'
		// basic wl : 'urn:factur-x.eu:1p0:basicwl'
		$dataProfile = 'urn:factur-x.eu:1p0:basicwl';

		if($eInvoice->isCreditNote()) {
			$typeCode = '381';
		} else {
			$typeCode = '380';
		}

		$testChorusPro = FALSE;
		$testSuperPDP = TRUE;

		// SuperPDP attend un SIREN (scheme 0002) mais ChorusPro attend un SIRET (0009)
		// => On ne modifie pas pour le moment pour éviter que l'envoi des factures sur ChorusPro échoue.
		$sellerDUNS = '281818298'; // JdT
		$sellerVat = $eInvoice['farm']['configuration']['invoiceVat'];
		if(LIME_ENV === 'dev') {

			if($testChorusPro) {
				$sellerSiret = '32046246515203';
			} else if($testSuperPDP) {
				$sellerSiret = '00000000200001';
				$sellerVat = 'FR18000000002';
			} else {
				$sellerSiret = str_replace(' ', '', $eInvoice['farm']['siret'] ?? '');
			}
		} else {
			$sellerSiret = str_replace(' ', '', $eInvoice['farm']['siret'] ?? '');
		}
		$sellerSiren = mb_substr($sellerSiret, 0, 9);

		$buyerVat = $eInvoice['customer']['invoiceVat'];
		if(LIME_ENV === 'dev') {
			if($testChorusPro) {
				$buyerSiret = '33345262604899';
			} else if($testSuperPDP) {
				$buyerVat = 'FR15000000001';
				$buyerSiret = '00000000100001';
			} else {
				$buyerSiret = str_replace(' ', '', $eInvoice['customer']['siret'] ?? '');
			}
		} else {
			$buyerSiret = str_replace(' ', '', $eInvoice['customer']['siret'] ?? '');
		}
		$buyerSiren = mb_substr($buyerSiret, 0, 9);

		$invoiceId = $eInvoice['name'];
		if(LIME_ENV === 'dev') {
			$invoiceId .= '-'.time();
			$invoiceId = mb_substr($invoiceId, 0, 20);
		}

		$xml = '<?xml version="1.0" encoding="utf-8"?>
<rsm:CrossIndustryInvoice xmlns:qdt="urn:un:unece:uncefact:data:standard:QualifiedDataType:100"
xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100"
xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100"
xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<rsm:ExchangedDocumentContext><!--BG-2-->
		<ram:GuidelineSpecifiedDocumentContextParameter><!--BT-24-00-->
			<ram:ID>'.$dataProfile.'</ram:ID><!--BT-24-->
		</ram:GuidelineSpecifiedDocumentContextParameter>
	</rsm:ExchangedDocumentContext>
	<rsm:ExchangedDocument>
		<ram:ID>'.encode($invoiceId).'</ram:ID><!--BT-1-->
		<ram:TypeCode>'.$typeCode.'</ram:TypeCode><!--BT-3-->
		<ram:IssueDateTime><!--BT-2-00-->
			<udt:DateTimeString format="102">'.date('Ymd', strtotime($eInvoice['date'])).'</udt:DateTimeString>
		</ram:IssueDateTime>
		<ram:IncludedNote><!--BG-1-->
			<ram:Content></ram:Content><!--BT-22-->
			<ram:SubjectCode>AAB</ram:SubjectCode><!--BT-21 AAB = escompte-->
		</ram:IncludedNote>
		<ram:IncludedNote><!--BG-1-->
			<ram:Content></ram:Content><!--BT-22-->
			<ram:SubjectCode>PMT</ram:SubjectCode><!--BT-21 PMT = frais de recouvrement-->
		</ram:IncludedNote>
		<ram:IncludedNote><!--BG-1-->
			<ram:Content></ram:Content><!--BT-22-->
			<ram:SubjectCode>PMD</ram:SubjectCode><!--BT-21 PMD = pénalité de retard-->
		</ram:IncludedNote>
	</rsm:ExchangedDocument>
	<rsm:SupplyChainTradeTransaction><!--BG-25-00-->
		<ram:ApplicableHeaderTradeAgreement><!--BT-10-00-->
			<ram:SellerTradeParty><!--BG-4-->
				<ram:GlobalID schemeID="0002">'.encode($sellerSiren).'</ram:GlobalID><!--BT-29-->
				<ram:Name>'.encode($eInvoice['farm']['legalName']).'</ram:Name><!--BT-24-->
				<ram:SpecifiedLegalOrganization><!--BT-30-->
					<ram:ID schemeID="0009">'.encode($sellerSiret).'</ram:ID>'
			./*
 passe sur SUPER PDP : <ram:ID schemeID="0002">'.encode($sellerSiren).'</ram:ID>
 passe sur Chorus Pro : <ram:ID schemeID="0009">'.encode($sellerSiret).'</ram:ID>
 */'
				</ram:SpecifiedLegalOrganization>
				<ram:PostalTradeAddress><!--BG-5-->
					<ram:CountryID>FR</ram:CountryID><!--BT-40-->
				</ram:PostalTradeAddress>
				<ram:URIUniversalCommunication><!--BT-34-00-->
					<ram:URIID schemeID="9957">'.encode($sellerVat).'</ram:URIID><!--BT-34-->
				</ram:URIUniversalCommunication>
				'.($sellerVat !== NULL ? '
				<ram:SpecifiedTaxRegistration><!--BT-31-00-->
					<ram:ID schemeID="VA">'.encode($sellerVat).'</ram:ID><!--BT-31-->
				</ram:SpecifiedTaxRegistration>' : '').'
			</ram:SellerTradeParty>
			<ram:BuyerTradeParty><!--BG-7-->
				<ram:Name>'.encode($eInvoice['customer']['legalName'] ?? $eInvoice['customer']['name']).'</ram:Name><!--BT-44-->
				'.($buyerSiret !== NULL ? '
				<ram:SpecifiedLegalOrganization><!--BT-47-00-->
					<ram:ID schemeID="0009">'.encode($buyerSiret).'</ram:ID>
				</ram:SpecifiedLegalOrganization>' : '').'
				<ram:PostalTradeAddress><!--BG-8-->
					<ram:CountryID>FR</ram:CountryID><!--BT-55-->
				</ram:PostalTradeAddress>
				<ram:URIUniversalCommunication><!--BT-49-00-->
					<ram:URIID schemeID="9957">'.encode($buyerVat).'</ram:URIID><!--BT-49-->
				</ram:URIUniversalCommunication>
			</ram:BuyerTradeParty>
		</ram:ApplicableHeaderTradeAgreement>
		<ram:ApplicableHeaderTradeDelivery/><!--BG-13-00-->
		<ram:ApplicableHeaderTradeSettlement><!--BG-19-00-->
			<ram:InvoiceCurrencyCode>EUR</ram:InvoiceCurrencyCode><!--BT-5-->';
			foreach($eInvoice['vatByRate'] as $vatByRate) {
				$xml .= '
				<ram:ApplicableTradeTax><!--BG-23-->
					<ram:CalculatedAmount>'.$vatByRate['vat'].'</ram:CalculatedAmount>
					<ram:TypeCode>VAT</ram:TypeCode>
					<ram:BasisAmount>'.$vatByRate['amount'].'</ram:BasisAmount>
					<ram:CategoryCode>S</ram:CategoryCode>
					<ram:RateApplicablePercent>'.$vatByRate['vatRate'].'</ram:RateApplicablePercent>
				</ram:ApplicableTradeTax>
			';
			}
		$xml .= '
			<ram:SpecifiedTradePaymentTerms><!--BT-20 : Payment terms-->
				<ram:Description>
					'.encode($eInvoice['farm']['configuration']['invoicePaymentCondition'] ?? '').'
				</ram:Description>
			</ram:SpecifiedTradePaymentTerms>
			<ram:SpecifiedTradeSettlementHeaderMonetarySummation><!--BG-22-->
        <ram:LineTotalAmount>'.$eInvoice['priceExcludingVat'].'</ram:LineTotalAmount><!--BT-106-->
				<ram:TaxBasisTotalAmount>'.$eInvoice['priceExcludingVat'].'</ram:TaxBasisTotalAmount><!--BT-109-->
				<ram:TaxTotalAmount currencyID="EUR">'.$eInvoice['vat'].'</ram:TaxTotalAmount><!--BT-110-->
				<ram:GrandTotalAmount>'.$eInvoice['priceIncludingVat'].'</ram:GrandTotalAmount><!--BT-112-->
				<ram:DuePayableAmount>'.$eInvoice['priceIncludingVat'].'</ram:DuePayableAmount><!--BT-115-->
			</ram:SpecifiedTradeSettlementHeaderMonetarySummation>
		</ram:ApplicableHeaderTradeSettlement>
	</rsm:SupplyChainTradeTransaction>
</rsm:CrossIndustryInvoice>';

		return $xml;
	}

	/**
	 * Note : il faut que le fichier PDF soit à un format <= 1.4.
	 * Transformation possible via cette commande : gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -o output.pdf input.pdf
	 * input.pdf et output.pdf doivent être différents
	 */
	public static function generate(Invoice $eInvoice, string $pdfContent): string {

		$eInvoice->expects(['name', 'date', 'priceExcludingVat', 'vat', 'priceIncludingVat', 'farm' => ['id', 'siret'], 'customer' => ['name']]);

		$eInvoice['farm']['configuration'] =  \selling\ConfigurationLib::getByFarm($eInvoice['farm']);

		$originalInvoiceFilepath = '/tmp/'.$eInvoice['id'].'-1.7.pdf';
		file_put_contents($originalInvoiceFilepath, $pdfContent);
		$transformedInvoiceFilepath = '/tmp/'.$eInvoice['id'].'-1.4.pdf';

		$writer = new \Atgp\FacturX\Writer();

		exec('gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -o '.$transformedInvoiceFilepath.' '.$originalInvoiceFilepath);

		$pdfContent = file_get_contents($transformedInvoiceFilepath);

		$xmlContent = self::generateInvoiceXml($eInvoice);

		unlink($originalInvoiceFilepath);
		unlink($transformedInvoiceFilepath);

		return $writer->generate($pdfContent, $xmlContent);

	}

}
